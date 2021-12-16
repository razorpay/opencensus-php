<?php

namespace RZP\Reconciliator;

use Queue;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Models\Transaction;
use RZP\Models\Payment\Refund;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\Base\Constants;
use RZP\Reconciliator\RequestProcessor;
use RZP\Models\Batch\Processor\Reconciliation;
use RZP\Reconciliator\Base\Foundation\SubReconciliate;
use RZP\Reconciliator\Base\Foundation\ScroogeReconciliate;

class Service extends Base\Service
{
    /**
     * List of gateways where we are doing recon processing via non-batch.
     */
    const NON_BATCH_RECON_GATEWAYS = [
        RequestProcessor\Base::PAYTM,
        RequestProcessor\Base::PAYUMONEY,
    ];

    /**
     * List of gateways where we skips the batch summary slack post
     */
    const BATCH_SUMMARY_SKIP_GATEWAYS = [
        RequestProcessor\Base::VIRTUAL_ACC_KOTAK,
    ];

    /**
     * This limit is being used as default while fetching the cancelled billdesk
     * payments and corresponding refunds. The route get hit via cron.
     * This limit is needed as sometimes cron fails due to longer query time.
     */
    const BILLDESK_CANCELLED_TXN_FETCH_QUERY_LIMIT = 200;

    protected $core;

    protected $messenger;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
        $this->messenger = new Messenger;
    }

    public function initiateReconciliationProcess(array $input)
    {
        $this->traceReconRequest($input);

        try
        {
            $source = $this->getRequestSource($input);

            $summary = $this->processReconciliationRequest($input, $source);
        }
        catch (\Throwable $e)
        {
            if (($this->isManualRequest($input) === true) or
                ($this->isLambdaRequest() === true) or
                ($this->isCrawlerRequest($input) === true))
            {
                $this->trace->traceException(
                    $e, Trace::ERROR, TraceCode::RECON_ALERT);

                throw $e;
            }

            $this->trace->traceException(
                $e, Trace::DEBUG, TraceCode::RECON_ALERT);

            // We do not throw an exception as route is hit via Mailgun,
            // and Mailgun will attempt retrying, which we don't want.
            return [];
        }

        return $summary;
    }

    public function reconciliateCancelledTransactions($gateway, array $input = [])
    {
        $this->trace->info(
            TraceCode::RECONCILE_CANCELLED_TRANSACTIONS_REQUEST,
            [
                'gateway'   => $gateway,
                'input'     => $input,
            ]
        );

        if ($gateway !== Payment\Gateway::BILLDESK)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_GATEWAY,
                'gateway',
                $gateway);
        }

        // Limit for payment and refund transactions fetch query
        $paymentLimit = $input['payment_limit'] ?? self::BILLDESK_CANCELLED_TXN_FETCH_QUERY_LIMIT;

        $refundLimit = $input['refund_limit'] ?? self::BILLDESK_CANCELLED_TXN_FETCH_QUERY_LIMIT;

        $paymentTransactions = $this->repo->transaction->getCancelledBilldeskPaymentTransactions($paymentLimit);

        $refundTransactions = $this->repo->transaction->getCancelledBilldeskPaymentRefundTransactions($refundLimit);

        $allTransactions = [
            'payment' => $paymentTransactions,
            'refund'  => $refundTransactions
        ];

        $transactionCore = new Transaction\Core;

        // list of transaction IDs for which update recon failed
        $failures = [
            'payment'   => [],
            'refund'    => [],
        ];

        $successCount = $failureCount = [
            'payment'   => 0,
            'refund'    => 0,
        ];

        foreach ($allTransactions as $entityType => $transactions)
        {
            foreach ($transactions as $transaction)
            {
                $success = $transactionCore->updateReconciliationData($transaction);

                if ($success === true)
                {
                    $successCount[$entityType]++;
                }
                else
                {
                    $failures[$entityType] = $transaction->getId();
                    $failureCount[$entityType]++;
                }
            }
        }

        $data = [
            'gateway'       => $gateway,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'failures'      => $failures,
        ];

        $this->trace->info(
            TraceCode::RECONCILE_CANCELLED_TRANSACTIONS_RESPONSE,
            $data
        );

        return $data;
    }

    /**
     * @param array $response
     *
     * Compare the fields return by CPS service with the values we got in MIS file
     * If data mismatch, raise alert and don't overwrite the value.
     * Note : Pushing when only existing value is empty. There is
     * no sense in pushing when it matches, as it is already saved.
     * @param array $input
     */
    public function persistGatewayDataAfterCpsReconResponse(array $response, array $input)
    {
        $paymentId = $input['payment_id'];

        $misParams = $input['params'];

        $pushData = [];

        if (empty($response[$paymentId]) === false)
        {
            foreach (Constants::CPS_PARAMS as $field)
            {
                if (empty($misParams[$field]) === true)
                {
                    // MIS row does not have this field, so no sense in
                    // comparing with CPS response or sending it to CPS.
                    continue;
                }

                //
                // Overwrite the data in two cases :
                // 1. Existing CPS data is empty.
                // 2. For gateway_transaction_id mismatch, we want to
                //    replace the data, as confirmed by CPS team.
                //    Ref : https://razorpay.slack.com/archives/C847BUR61/p1578048952001800
                //
                if (empty($response[$paymentId][$field]) === true)
                {
                    $pushData[$field] = $misParams[$field];
                }
                else if (trim($response[$paymentId][$field]) !== $misParams[$field])
                {
                    // CPS data and MIS data both are non empty and we have mismatch.
                    //
                    // If the field is gateway_transaction_id, we simply overwrite.
                    if ($field === Constants::GATEWAY_TRANSACTION_ID)
                    {
                        $pushData[$field] = $misParams[$field];

                        // skip trace as this mismatch is expected.
                        continue;
                    }

                    // Trace alert
                    $this->trace->info(
                        TraceCode::RECON_MISMATCH,
                        [
                            'info_code'                 => InfoCode::CPS_PAYMENT_AUTH_DATA_MISMATCH,
                            'payment_id'                => $paymentId,
                            'field'                     => $field,
                            'db_reference_number'       => $response[$paymentId][$field],
                            'recon_reference_number'    => $misParams[$field],
                            'gateway'                   => $input['gateway'],
                            'batch_id'                  => $input['batch_id'],
                        ]
                    );
                }
            }
        }
        else
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'     => InfoCode::CPS_PAYMENT_AUTH_DATA_ABSENT,
                    'payment_id'    => $paymentId,
                    'gateway'       => $input['gateway'],
                    'batch_id'      => $input['batch_id'],
                ]);

            return;
        }

        if (empty($pushData) === true and (!((strtolower($input['gateway']) === Payment\Gateway::FULCRUM) and
                (empty($input[Reconciliation::IS_GATEWAY_CAPTURED_MISMATCH]) === false))))
        {
            // No param has been set to be saved/overwritten,
            // no meaning in pushing to queue.

            return;
        }

        $data = $pushData;
        $pushData = [];

        $pushData[Constants::ENTITY_TYPE] = Constants::GATEWAY;
        $pushData[Constants::GATEWAY] = $data;
        $pushData['payment_id'] = $paymentId;

        if ((strtolower($input['gateway']) === Payment\Gateway::FULCRUM) and
            (empty($input[Reconciliation::IS_GATEWAY_CAPTURED_MISMATCH]) === false)){
            $pushData[Constants::GATEWAY]['name'] = Payment\Gateway::FULCRUM;
            $pushData[Constants::GATEWAY]['gateway_captured'] = true;
        }

        $queueName = $this->app['config']->get('queue.payment_card_api_reconciliation.' . $this->mode);

        Queue::pushRaw(json_encode($pushData), $queueName);

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'info_code' => InfoCode::RECON_CPS_QUEUE_DISPATCH,
                'queue'     => $queueName,
                'payload'   => $pushData,
                'gateway'   => $input['gateway'],
                'batch_id'  => $input['batch_id'],
            ]
        );
    }

    // Deprecated. We now compare and update within the nbplus service itself
    public function persistGatewayDataAfterNbPlusReconResponse(array $response, array $input, $entity, $entityAttributes, $reconParams)
    {
        $paymentId = $input['payment_id'];

        $reconData = $input['recon_file_data'];

        $dataToUpdate = [];

        $responseData = $response['items'];

        if (empty($responseData[$paymentId]) === false)
        {
            foreach ($reconParams as $field)
            {
                if (empty($reconData[$field]) === true)
                {
                    continue;
                }

                if (empty($responseData[$paymentId][$field]) === true)
                {
                    if (in_array($field, $entityAttributes, true) === true)
                    {
                        $dataToUpdate[$field] = $reconData[$field];
                    }
                    else
                    {
                        $dataToUpdate['additional_data'][$field] = $reconData[$field];
                    }
                }
                else if (trim($responseData[$paymentId][$field]) !== $reconData[$field])
                {
                    $this->trace->info(
                        TraceCode::RECON_MISMATCH,
                        [
                            'info_code'                 => InfoCode::NBPLUS_DATA_MISMATCH,
                            'payment_id'                => $paymentId,
                            'field'                     => $field,
                            'gateway'                   => $input['gateway'],
                            'batch_id'                  => $input['batch_id'],
                        ]
                    );
                }
            }
        }
        else
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'     => InfoCode::NBPLUS_DATA_ABSENT,
                    'payment_id'    => $paymentId,
                    'gateway'       => $input['gateway'],
                    'batch_id'      => $input['batch_id'],
                ]);

            return;
        }

        if (empty($dataToUpdate) === true)
        {
            return;
        }

        $dataToUpdate['payment_id'] = $paymentId;

        // Final Payload
        $pushData['entity_name'] = $entity;
        $pushData['recon_data']  = $dataToUpdate;

        $queueName = $this->app['config']->get('queue.payment_nbplus_api_reconciliation.' . $this->mode);

        Queue::pushRaw(json_encode($pushData), $queueName);

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'info_code'  => InfoCode::RECON_NBPLUS_QUEUE_DISPATCH,
                'queue'      => $queueName,
                'payment_id' => $paymentId,
                'batch_id'   => $input['batch_id']
            ]
        );
    }

    /**
     * This method is called when recon request comes
     * in small chunks from batch service.
     *
     * @param array $input
     * @return mixed
     * @throws Exception\LogicException
     */
    public function reconcileViaBatchService(array $input)
    {
        $this->trace->info(TraceCode::RECON_REQUEST_VIA_BATCH_SERVICE, $input);

        $input = $this->preProcessBatchInput($input);

        $processor = new Reconciliation();

        $result = $processor->batchProcessEntries($input);

        $response = $this->formatResult($result);

        return $response;
    }

    /**
     * This method is help to restructure the input for the reconciliation
     * This will restructure the input as per normal reconciliation request input
     * @param array $entries
     * @return mixed
     */
    protected function preProcessBatchInput(array $entries)
    {
        $forceUpdate = [];
        $forceAuthorize = [];

        $config = [
            RequestProcessor\Base::GATEWAY  => $entries[0][Constants::GATEWAY],
            RequestProcessor\Base::SOURCE   => $entries[0][Constants::SOURCE],
            RequestProcessor\Base::SUB_TYPE => $entries[0][Constants::SUB_TYPE],
            FileProcessor::SHEET_NAME       => $entries[0][Constants::SHEET_NAME] ?? null,
            Constants::BATCH_ID             => $this->app['request']->header(RequestHeader::X_Batch_Id) ?? null,
        ];

        if (isset($entries[0][RequestProcessor\Base::FORCE_UPDATE]) === true)
        {
            $forceUpdate = $entries[0][RequestProcessor\Base::FORCE_UPDATE];
        }

        if (isset($entries[0][RequestProcessor\Base::FORCE_AUTHORIZE]) === true)
        {
            $forceAuthorize = $entries[0][RequestProcessor\Base::FORCE_AUTHORIZE];
        }

        foreach ($entries as &$entry)
        {
            unset($entry[Constants::GATEWAY]);
            unset($entry[Constants::SOURCE]);
            unset($entry[Constants::SUB_TYPE]);
            unset($entry[Constants::SHEET_NAME]);
            unset($entry[Constants::BATCH_ID]);
            unset($entry[RequestProcessor\Base::FORCE_AUTHORIZE]);
            unset($entry[RequestProcessor\Base::FORCE_UPDATE]);
        }

        //
        // Batch service modifies the `Amount` column to 'Amount (In Paise)'
        // So need to change it back, preserving column order.
        //
        if (isset($entries[0][Constants::COLUMN_BATCH_AMOUNT]) === true)
        {
            $this->changeColumnName($entries, Constants::COLUMN_BATCH_AMOUNT , Constants::COLUMN_API_AMOUNT);
        }

        $this->normalizeEntries($entries, $config[Constants::GATEWAY]);

        $input[0] = $entries;

        $input[0][Reconciliation::EXTRA_DETAILS] = [
            Reconciliation::FILE_DETAILS => [
                FileProcessor::SHEET_NAME => $config[FileProcessor::SHEET_NAME],
            ],
            Reconciliation::INPUT_DETAILS => [
                RequestProcessor\Base::FORCE_UPDATE    => $forceUpdate,
                RequestProcessor\Base::FORCE_AUTHORIZE => $forceAuthorize
            ],
            Batch\Entity::CONFIG => $config,
            Reconciliation::BATCH_SERVICE_RECON_REQUEST => true,
        ];

        return $input;
    }

   protected function changeColumnName(array &$entries, string $oldKey, string $newKey)
   {
        foreach ($entries as $index => &$row)
        {
            $columns = array_keys($row);

            $columns[array_search($oldKey, $columns)] = $newKey;

            $row = array_combine($columns, $row);
        }
    }

    protected function normalizeEntries(array &$entries, string $gateway)
    {
        // normalize header
        $converter = new Converter($gateway);

        foreach ($entries as &$row)
        {
            $normalizedHeader = $converter->normalizeHeaders(array_keys($row));

            $row = array_combine_pad($normalizedHeader, $row);
        }
    }

    /**
     * Format the result in proper response,
     * adds required status_code etc
     *
     * @param array $result
     * @return mixed
     */
    protected function formatResult(array $result)
    {
        $reconRows = new Base\PublicCollection;

        foreach ($result as $row)
        {
            $isFailed = $this->getReconStatus($row);

            $row[Constants::HTTP_STATUS_CODE]   = ($isFailed === true) ? 400 : 200;

            $idempotentId = $row[Constants::IDEMPOTENT_ID];

            // move this idempotent_id column to end
            unset($row[Constants::IDEMPOTENT_ID]);

            $row[Constants::IDEMPOTENT_ID] = $idempotentId;

            $reconRows->push($row);
        }

        $this->trace->info(TraceCode::RECON_RESPONSE, $reconRows->toArrayWithItems());

        return $reconRows->toArrayWithItems();
    }

    protected function getReconStatus(array $row)
    {
        return ($row[SubReconciliate::RECON_STATUS] === Constants::RECON_PUBLIC_DESCRIPTIONS[InfoCode::RECON_FAILED]);
    }

    /**
     * @param array $response
     * @param bool $shouldUpdateBatchSummary
     * @return array
     * @throws \Throwable
     */
    public function reconcileRefundsAfterScroogeRecon(array $response , bool $shouldUpdateBatchSummary = true)
    {
        $batchId        =  $response[ScroogeReconciliate::BATCH_ID] ?? null;
        $chunkNumber    = $response[ScroogeReconciliate::CHUNK_NUMBER] ?? 1;
        $forceUpdateArn = $this->core->shouldForceUpdateArnAfterScroogeRecon($response);
        $source         = $response[ScroogeReconciliate::SOURCE] ?? RequestProcessor\Base::MANUAL;

        $traceData =  [
            ScroogeReconciliate::CHUNK_NUMBER            => $chunkNumber,
            ScroogeReconciliate::BATCH_ID                => $batchId,
            ScroogeReconciliate::SOURCE                  => $source,
            ScroogeReconciliate::SHOULD_FORCE_UPDATE_ARN => $forceUpdateArn,
        ];

        $reconciled = $failures = [];

        if (empty($response[ScroogeReconciliate::REFUNDS]) === true)
        {
            $response[ScroogeReconciliate::REFUNDS] = [];

            $traceInfoCode = [
                ScroogeReconciliate::INFO_CODE => InfoCode::REFUND_RECON_SCROOGE_NO_REFUNDS
            ];

            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                array_merge($traceInfoCode, $traceData)
            );
        }

        foreach ($response[ScroogeReconciliate::REFUNDS] as $refundData)
        {
            $refundId = Payment\Refund\Entity::getSignedId($refundData[ScroogeReconciliate::REFUND_ID]);

            $this->repo->transactionOnLiveAndTest(function () use ($refundId,
                                                                   &$reconciled,
                                                                   &$failures,
                                                                   $source,
                                                                   $forceUpdateArn,
                                                                   $refundData,
                                                                   $batchId,
                                                                   $chunkNumber,
                                                                   $traceData)
            {
                try
                {
                    $refund = $this->repo->refund->findByPublicId($refundId);

                    if (empty($refundData[Transaction\Entity::RECONCILED_AT]) === false)
                    {
                        //
                        // Returns updated refund entity with transaction
                        //
                        $refund = $this->core->checkAndCreateIfRefundTransactionMissing($refund);

                        if ($refund->transaction === null)
                        {
                            $failures[] = $refundId;
                        }
                        else
                        {
                            $this->updateRefundAndTransactionAfterScroogeRecon($refund,
                                                                               $refundData,
                                                                               $source,
                                                                               $forceUpdateArn);

                            $reconciled[] = $refundId;
                        }
                    }
                    else
                    {
                        //
                        // As we send only those refunds to scrooge which are good to be
                        // reconciled, we expect all the refunds coming from scrooge to be
                        // have reconciled_at set, else its a failure case
                        //
                        $failures[] = $refundId;

                        $traceInfoCode = [
                            ScroogeReconciliate::INFO_CODE => InfoCode::REFUND_RECON_SCROOGE_NOT_RECONCILED,
                            ScroogeReconciliate::REFUND_ID => $refundId,
                        ];

                        $this->trace->info(
                            TraceCode::RECON_INFO_ALERT,
                            array_merge($traceInfoCode, $traceData)
                        );
                    }
                }
                catch (\Exception $ex)
                {
                    $traceInfoCode = [
                        ScroogeReconciliate::INFO_CODE => InfoCode::REFUND_RECON_SCROOGE_NOT_RECONCILED,
                        ScroogeReconciliate::REFUND_ID => $refundId,
                    ];

                    $this->trace->traceException(
                        $ex,
                        Trace::ERROR,
                        TraceCode::RECON_INFO_ALERT,
                        array_merge($traceInfoCode, $traceData)
                    );

                    $failures[] = $refundId;
                }
            });
        }

        //
        // $scroogeFailureCount represents the count of failures that happened
        // internally in scrooge. Basically it is the difference of the count
        // of refunds that was sent from API to scrooge (total refunds to process)
        // and the number of refunds it sent back in response to API (actual number
        // of refunds successfully processed).
        // The refunds that are failed on Scrooge, are not returned back in the response `refunds` data.
        // Only the number is sent as `failure_count`.
        //
        $scroogeFailureCount = $response[ScroogeReconciliate::FAILURE_COUNT] ?? 0;

        $data = [
            'success_count'         => count($reconciled),
            'failure_count'         => count($failures),
            'scrooge_failure_count' => $scroogeFailureCount,
            'batch_id'              => $batchId,
            'chunk_number'          => $chunkNumber,
            'refund_count'          => count($response[ScroogeReconciliate::REFUNDS]),
            'failures'              => $failures,
        ];

        if ($shouldUpdateBatchSummary === true)
        {
            $this->updateScroogeBatchSummary($batchId, $data);
        }

        $this->trace->info(
            TraceCode::REFUND_RECON_SCROOGE_CHUNK_MARK_RECONCILED,
            $data
        );

        return $data;
    }

    /**
     * @param Refund\Entity $refund
     * @param array $refundData
     * @param string $source
     * @param bool $forceUpdateArn
     */
    protected function updateRefundAndTransactionAfterScroogeRecon(Refund\Entity $refund,
                                                                   array $refundData,
                                                                   string $source,
                                                                   bool $forceUpdateArn)
    {
        $this->core->persistReconciledAtAfterScroogeRecon($refund, $refundData, $source);

        $processor = $this->getNewProcessor($refund->merchant);

        $arn = $refundData[ScroogeReconciliate::ARN] ?? null;

        if ($refundData[Refund\Entity::STATUS] === Refund\Status::PROCESSED)
        {
            $refund->setStatusProcessed();

            $this->core->pushRefundProcessedMetric($refund, $source);
        }

        if (($processor->isValidArn($arn) === true) and
            ((empty($refund->getReference1()) === true) or ($forceUpdateArn === true)))
        {
            $processor->updateReference1AndTriggerEventArnUpdated($refund, $arn);
        }

        if ((empty($refundData[Transaction\Entity::GATEWAY_SETTLED_AT]) === false) and
            (empty($refund->transaction->getGatewaySettledAt()) === true))
        {
            $refund->transaction->setGatewaySettledAt($refundData[Transaction\Entity::GATEWAY_SETTLED_AT]);
        }

        $this->repo->saveOrFail($refund);
        $this->repo->saveOrFail($refund->transaction);
    }

    public function getNewProcessor($merchant)
    {
        return new Payment\Processor\Processor($merchant);
    }

    public function updateScroogeBatchSummary(string $batchId, array $data)
    {
        $totalFailureCount = $data['failure_count'] + $data['scrooge_failure_count'];

        $totalSuccessCount = $data['success_count'];

        return $this->core->updateScroogeBatchSummary($batchId, $totalSuccessCount, $totalFailureCount);
    }

    protected function getRequestSource(array $input): string
    {
        if ($this->isManualRequest($input))
        {
            return RequestProcessor\Base::MANUAL;
        }
        else if ($this->isCrawlerRequest($input))
        {
            return RequestProcessor\Base::CRAWLER;
        }
        else if ($this->isLambdaRequest())
        {
            return RequestProcessor\Base::LAMBDA;
        }
        else
        {
            return RequestProcessor\Base::MAILGUN;
        }
    }

    /**
     * Determines whether the reconciliation request is manual or
     * via MailGun and gets the files details accordingly.
     *
     * @param array  $input The input received from the route.
     * @param string $source
     *
     * @return array Summary of reconciliation
     * @throws Exception\ReconciliationException Raised when there are no
     *                                           files to reconcile.
     */
    protected function processReconciliationRequest(array $input, string $source)
    {
        $requestProcessor = $this->getRequestProcessor($source);

        //
        // Sets the gateway reconciliator object and
        // Gets all the file details from the input.
        //
        $reconDetails = $requestProcessor->process($input);

        // There must be at least one file. Otherwise, error.
        if (empty($reconDetails[RequestProcessor\Base::FILE_DETAILS]) === true)
        {
            throw new Exception\ReconciliationException(
                'File details are empty.');
        }

        $gateway = $requestProcessor->getGateway();

        $this->trace->info(
            TraceCode::RECON_FILE_DETAILS,
            [
                'file_details'  => $reconDetails[RequestProcessor\Base::FILE_DETAILS],
                'gateway'       => $gateway
            ]);

        if ($source === RequestProcessor\Base::MAILGUN)
        {
            $this->validateSpf($input, $gateway);
        }

        $gatewayReconciliator = $requestProcessor->getGatewayReconciliator();

        $orchestrator = new Orchestrator($gateway, $gatewayReconciliator);

        //
        // This is a temporary logic. Plan is to move all gateway reconciliation
        // to batch once it is stable
        //
        if (in_array($gateway, self::NON_BATCH_RECON_GATEWAYS, true) === true)
        {
            return $orchestrator->orchestrate($reconDetails);
        }

        return $orchestrator->orchestrateV2($reconDetails);
    }

    /**
     * Request body, if sent via mail through Mailgun, is too large
     * to be parsed effectively on Splunk. So we unset the body params,
     * then trace everything else.
     * Other headers will be enough to identify the mail if needed.
     *
     * @param array $input Request body
     */
    protected function traceReconRequest(array $input)
    {
        unset($input[RequestProcessor\Mailgun::BODY_HTML]);
        unset($input[RequestProcessor\Mailgun::BODY_PLAIN]);
        unset($input[RequestProcessor\Mailgun::STRIPPED_HTML]);
        unset($input[RequestProcessor\Mailgun::STRIPPED_TEXT]);
        unset($input[RequestProcessor\Mailgun::MESSAGE_HEADERS]);

        $this->trace->info(
            TraceCode::RECON_REQUEST,
            $input);
    }

    /**
     * Validates spf record of incoming request
     *
     * @param array $input
     * @param $gateway
     */
    protected function validateSpf(array $input, $gateway)
    {
        if (isset($input[RequestProcessor\Mailgun::X_MAILGUN_SPF]) === true)
        {
            $spfStatus = strtolower(substr($input[RequestProcessor\Mailgun::X_MAILGUN_SPF], 0, 4));
            if ($spfStatus === RequestProcessor\Mailgun::SPF_PASS)
            {
                return;
            }
        }

        $this->trace->info(TraceCode::RECON_EMAIL_VALIDATION_FAILED,
            [
                'message'       => 'Spf validation for request failed',
                'x-mailgun-spf' => $input[RequestProcessor\Mailgun::X_MAILGUN_SPF] ?? null,
                'gateway'       => $gateway,
            ]);
    }

    /**
     * Initializes the request processor to be used to handle the request
     * based on the source of the request i.e manual | lambda | mailgun
     *
     * @param string $source
     *
     * @return RequestProcessor\Base
     */
    protected function getRequestProcessor(string $source): RequestProcessor\Base
    {
        $source = studly_case($source);

        $requestProcessor = __NAMESPACE__ . "\\RequestProcessor\\$source";

        return new $requestProcessor;
    }

    /**
     * Checks if request is a manual file upload
     *
     * @param array $input The input received from the route.
     * @return boolean Flag to indicate manual request
     */
    protected function isManualRequest(array $input): bool
    {
        if ((isset($input[RequestProcessor\Base::MANUAL]) === true) and
            ($input[RequestProcessor\Base::MANUAL] === '1'))
        {
            return true;
        }

        return false;
    }

    protected function isCrawlerRequest(array $input): bool
    {
        if ((isset($input[RequestProcessor\Base::CRAWLER]) === true) and
            ($input[RequestProcessor\Base::CRAWLER] === '1') and
            ($this->auth->isCron() === true))
        {
            return true;
        }
        return false;
    }

    /**
     * Checks if the request originated via an aws lambda trigger
     *
     * @return boolean
     */
    protected function isLambdaRequest(): bool
    {
        return ($this->auth->isLambda() === true);
    }

    public function getReconBatchesAndFiles($input)
    {
        $service = new Batch\Service;

        $data  = $service->getReconBatchesWithFiles($input);

        return $data;
    }

    public function getReconFilesCount($input)
    {
        $service = new Batch\Service;

        $data  = $service->getReconFilesCount($input);

        return $data;
    }
}

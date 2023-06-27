<?php

namespace RZP\Reconciliator;

use Queue;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Exception\ReconciliationException;
use RZP\Jobs\CardsPaymentRecon;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Models\Transaction;
use RZP\Models\FileStore;
use RZP\Metro\MetroHandler;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Refund;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\Base\Constants;
use RZP\Reconciliator\RequestProcessor;
use RZP\Services\NbPlus\Wallet as Wallet;
use RZP\Jobs\UpsRecon\UpsGatewayEntityUpdate;
use RZP\Models\Batch\Processor\Reconciliation;
use RZP\Reconciliator\Base\Foundation\SubReconciliate;
use RZP\Services\NbPlus\Netbanking as NetbankingService;
use RZP\Reconciliator\Base\Foundation\ScroogeReconciliate;
use RZP\Reconciliator\Base\SubReconciliator\NbPlus\NbPlusServiceRecon;
use RZP\Reconciliator\Base\SubReconciliator\Upi\Constants as UpsConstants;
use RZP\Reconciliator\Base\SubReconciliator\Upi\UpiPaymentServiceReconciliate;
use RZP\Models\Ledger\ReverseShadow\Payments\Core as ReverseShadowPaymentsCore;
use RZP\Reconciliator\Base\SubReconciliator\PaymentReconciliate;

class Service extends Base\Service
{
    /**
     * List of gateways where we are doing recon processing via non-batch.
     */
    const NON_BATCH_RECON_GATEWAYS = [
        RequestProcessor\Base::PAYTM,
        RequestProcessor\Base::PAYUMONEY,
    ];

    const GATEWAYS_WITH_REF_ID1 = [
        RequestProcessor\Base::INDUSIND_DEBIT_EMI,
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

    const VPA = 'vpa';

    const IFSC = 'ifsc';

    const NAME = 'name';

    const BLACKLISTED_EMAIL_FOR_API_AUTO_RECON_VIA_MAILGUN = ["finances.recon@mg.razorpay.com", "art-recon@mg.razorpay.com"];

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

                if($field === Constants::GATEWAY_REFERENCE_ID1 and
                    (in_array($input['gateway'], self::GATEWAYS_WITH_REF_ID1, true) !== true))
                {
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
                    $refund = $this->repo->refund->findByPublicIdFromAPI($refundId);

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

        $skipArnEvent = $refundData['skip_arn_updated_event'] ?? false;

        if ($refundData[Refund\Entity::STATUS] === Refund\Status::PROCESSED)
        {
            $refund->setStatusProcessed();

            $this->core->pushRefundProcessedMetric($refund, $source);
        }

        if (($processor->isValidArn($arn) === true) and
            ((empty($refund->getReference1()) === true) or ($forceUpdateArn === true)))
        {
            if ($skipArnEvent === true)
            {
                $processor->updateReference1AndTriggerEventArnUpdated($refund, $arn, false);
            }
            else
            {
                $processor->updateReference1AndTriggerEventArnUpdated($refund, $arn);
            }
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

    /**
     * @throws \Throwable
     */
    public function updateReconciliationData(array $input): array
    {
        $paymentId = $input['payment_id'];

        $payment = $this->repo->payment->findOrFail($paymentId);

        $method = $payment->getMethod();

        if($method == Payment\Method::NETBANKING or $method == Payment\Method::WALLET)
        {
            return $this->updateNetbankingReconciliationData($input, $payment);
        }

        else if($method == Payment\Method::UPI)
        {
            return $this->updateUpiReconciliationData($input, $payment);
        }

       else if($payment->isMethodCardOrEmi() === true)
        {
            return $this->updateCardReconciliationData($input, $payment);
        }

        $this->trace->info(
            TraceCode::METHOD_NOT_SUPPORTED_FOR_RECON,
            $input
        );
        return [];
    }

    /**
     * Update post reconciliation data from ART
     * @param array $input
     * @return array
     * @throws \Throwable
     */
    public function updateUpiReconciliationData(array $input, Payment\Entity $payment)
    {
        (new Validator)->validateUpdateUpiReconData($input);

        $paymentId = $input['payment_id'];

        if ($payment->isExternal() === true)
        {
            $payment->transaction = $this->repo->transaction->fetchByEntityAndAssociateMerchant($payment);
        }

        $transaction = $payment->transaction;

        if ((empty($transaction) === false) and
            ($transaction->isReconciled() === true))
        {
            return [
                'success'        => false,
                'gateway'        => $payment->getGateway(),
                'error' => [
                    'code'        => InfoCode::ALREADY_RECONCILED,
                    'description' => 'Upi payment is already reconciled'
                ],
            ];
        }

        $this->trace->info(
            TraceCode::RECON_UPDATE_RECONCILIATION_DATA_STARTED,
           [
               'input'   => $input,
               'gateway' => $payment->getGateway(),
           ]
        );

        try
        {
            $this->repo->transaction(function () use ($paymentId, $input, $payment)
            {
                $this->updateTransactionData($input, $payment);

                $this->updateGatewayData($input, $payment);

                if ($payment->isQrV2Payment() === true)
                {
                    (new UpiPaymentServiceReconciliate)->handleUnExpectedPaymentRefundInRecon($payment);
                }
            });

            $this->core->pushSuccessPaymentReconMetrics($payment,"art");

            return [
                'success'     => true,
                'gateway'     => $payment->getGateway(),

            ];
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::RECON_UPDATE_RECONCILIATION_DATA_FAILED,
                [
                    'paymentId' => $paymentId,
                    'gateway'   => $payment->getGateway(),
                ]
            );
            throw $ex;
        }
    }

      /**
     * Update post reconciliation data from ART
     * @param array $input
     * @return array
     * @throws \Throwable
     */
    public function updateCardReconciliationData(array $input, Payment\Entity $payment)
    {
        (new Validator)->validateUpdateCardReconData($input);

        $paymentId = $input['payment_id'];

        if ($payment->isExternal() === true)
        {
            $payment->transaction = $this->repo->transaction->fetchByEntityAndAssociateMerchant($payment);
        }

        $transaction = $payment->transaction;

        if ((empty($transaction) === false) and
            ($transaction->isReconciled() === true))
        {
            return [
                'success'        => false,
                'gateway'        => $payment->getGateway(),
                'error' => [
                    'code'        => InfoCode::ALREADY_RECONCILED,
                    'description' => 'Card payment is already reconciled'
                ],
            ];
        }

        $this->trace->info(
            TraceCode::RECON_UPDATE_RECONCILIATION_DATA_STARTED,
           [
               'input'   => $input,
               'gateway' => $payment->getGateway(),
           ]
        );

        try
        {
           $payment->reload()->transaction->reload();

            $this->repo->transaction(function () use ($paymentId, $input, $payment)
            {
                $this->updateTransactionData($input, $payment);

                $this->updateCpsData($input, $payment);

            });

            $this->core->pushSuccessPaymentReconMetrics($payment,"art");

            return [
                'success'     => true,
                'gateway'     => $payment->getGateway(),

            ];
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::RECON_UPDATE_RECONCILIATION_DATA_FAILED,
                [
                    'paymentId' => $paymentId,
                    'gateway'   => $payment->getGateway(),
                ]
            );
            throw $ex;
        }
    }

    /**
     * Update post reconciliation data from ART
     * @param array $input
     * @return array
     * @throws \Throwable
     */
    public function updateNetbankingReconciliationData(array $input, Payment\Entity $payment): array
    {

        switch ($payment->getMethod())
        {
            case Payment\Method::NETBANKING;
                (new Validator)->validateUpdateNetbankingReconData($input);
                break;
            case Payment\Method::WALLET:
                (new Validator)->validateUpdateWalletReconData($input);
                break;
        }

        $paymentId = $input['payment_id'];

        $transaction = $payment->transaction;

        if ((empty($transaction) === false) and
            ($transaction->isReconciled() === true))
        {
            return [
                'success'        => false,
                'gateway'        => $payment->getGateway(),
                'error' => [
                    'code'        => InfoCode::ALREADY_RECONCILED,
                    'description' => 'Netbanking payment is already reconciled'
                ],
            ];
        }

        $this->trace->info(
            TraceCode::RECON_UPDATE_RECONCILIATION_DATA_STARTED,
            $input
        );

        try
        {
            $this->repo->transaction(function () use ($paymentId, $input, $payment)
            {
                $this->updateTransactionData($input, $payment);
            });

            switch ($payment->getMethod())
            {
                case Payment\Method::NETBANKING;
                    $this->updateNetbankingGatewayData($input, $payment);
                    break;
                case Payment\Method::WALLET:
                    $this->updateWalletGatewayData($input, $payment);
                    break;
            }

            $this->core->pushSuccessPaymentReconMetrics($payment, "art");

            return [
                'success'     => true,
                'gateway'     => $payment->getGateway(),
            ];
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::RECON_UPDATE_RECONCILIATION_DATA_FAILED,
                [
                    'paymentId' => $paymentId,
                    'gateway'   => $payment->getGateway(),
                ]
            );
            throw $ex;
        }
    }

    /**
     * Retrive required field of UPS gateway entity
     * @param Payment\Entity $payment
     * @return array
     */
    protected function getUpsGatewayEntity(Payment\Entity $payment): array
    {
        $action = UpsConstants::ENTITY_FETCH;

        $gateway = $payment->getGateway();

        $input = [
            UpsConstants::MODEL            => UpsConstants::AUTHORIZE,
            UpsConstants::REQUIRED_FIELDS  => [
                UpsConstants::CUSTOMER_REFERENCE,
                UpsConstants::GATEWAY_REFERENCE,
                UpsConstants::NPCI_TXN_ID,
                UpsConstants::RECONCILED_AT,
            ],
            UpsConstants::COLUMN_NAME      => UpsConstants::PAYMENT_ID,
            UpsConstants::VALUE            => $payment->getId(),
            UpsConstants::GATEWAY          => $gateway
        ];

        $gatewayEntity = $this->app['upi.payments']->action($action, $input, $gateway);

        if ((isset($gatewayEntity[UpsConstants::CUSTOMER_REFERENCE]) === false) or
            (isset($gatewayEntity[UpsConstants::GATEWAY_REFERENCE]) === false) or
            (isset($gatewayEntity[UpsConstants::NPCI_TXN_ID]) === false) or
            (isset($gatewayEntity[UpsConstants::RECONCILED_AT]) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::SERVER_ERROR_UPI_PAYMENT_SERVICE_ENTITY_FETCH_ERROR,
                [
                    'input'     => $input,
                    'entity'    => $gatewayEntity
                ],
                null,
                'received wrong entity from Upi Payment Service');
        }

        return $gatewayEntity;
    }

    /** Persist/update gateway data post recon and pushes to metro
     * @param array $input
     * @param Payment\Entity $payment
     * @throws Exception\BadRequestException
     */
    protected function updateUpsGatewayData(array $input, Payment\Entity $payment)
    {
        $gatewayEntity = $this->getUpsGatewayEntity($payment);

        $dataToUpdate = [];

        if ((empty($input['upi']['gateway_payment_id']) === false) and
             ($input['upi']['gateway_payment_id'] !== $gatewayEntity[UpsConstants::GATEWAY_REFERENCE]))
        {
            $dataToUpdate[UpsConstants::GATEWAY_REFERENCE] = $input['upi']['gateway_payment_id'];
        }

        if ((empty($input['upi']['npci_txn_id']) === false) and
             ($input['upi']['npci_txn_id'] !== $gatewayEntity[UpsConstants::NPCI_TXN_ID]))
        {
            $dataToUpdate[UpsConstants::NPCI_TXN_ID] = $input['upi']['npci_txn_id'];
        }

        if ((empty($input['upi']['npci_reference_id']) === false) and
             ($input['upi']['npci_reference_id'] !== $gatewayEntity[UpsConstants::CUSTOMER_REFERENCE]))
        {
            $dataToUpdate[UpsConstants::CUSTOMER_REFERENCE] = $input['upi']['npci_reference_id'];
        }

        if (empty($dataToUpdate) === true)
        {
            // do not push to sqs if there is no data to update
            return;
        }

        $this->dispatchToUpsReconQueue($dataToUpdate, $payment);
    }

    /** Persist/update gateway data post recon
     * @param array $input
     * @param Payment\Entity $payment
     * @throws Exception\BadRequestException
     */
    protected function updateGatewayData(array $input, Payment\Entity $payment)
    {
        if (($payment->isRoutedThroughUpiPaymentService() === true) ||
            ($payment->isRoutedThroughPaymentsUpiPaymentService() === true))
        {
            $this->updateUpsGatewayData($input, $payment);
            return;
        }

        $paymentId = $input['payment_id'];

        $gatewayPayment = $this->repo->upi->findByPaymentIdAndAction($paymentId, 'authorize');

        if (empty($gatewayPayment) === true)
        {
            throw new Exception\BadRequestException(
                'Upi gateway payment not found',
                $input);
        }

        if (empty($input['upi']['gateway_payment_id']) === false)
        {
            $gatewayPayment->setGatewayPaymentId($input['upi']['gateway_payment_id']);
        }

        if (empty($input['upi']['npci_txn_id']) === false)
        {
            $gatewayPayment->setNpciTransactionId($input['upi']['npci_txn_id']);
        }

        if (empty($input['upi']['npci_reference_id']) === false)
        {
            $gatewayPayment->setNpciReferenceId($input['upi']['npci_reference_id']);
        }

        $this->updateAccountDetails($input['upi'], $gatewayPayment);

        $this->repo->saveOrFail($gatewayPayment);
    }

    protected function updateCpsData (array $input, Payment\Entity $payment)
    {

        try {

            if ($payment->isRoutedThroughCardPayments() === true || $payment->getCpsRoute() === Payment\Entity::REARCH_CARD_PAYMENT_SERVICE)
            {
             $dataToUpdate = [];

             if (empty($input['card']['auth_code']) === false)
             {
                $dataToUpdate['auth_code'] = trim($input['card']['auth_code']);
             }

             if (empty($input['card']['rrn']) === false)
             {
                $dataToUpdate['rrn'] = trim($input['card']['rrn']);
             }

            // add other gateway details for other gateway

            $dataToUpdate['gateway_transaction_id'] =  null;

            $dataToUpdate['gateway_reference_id1']  =  null;


             $data = [
                'payment_id' => $payment->getId(),
                'params'     => $dataToUpdate,
                'mode'       => $this->mode,
                'gateway'    => $payment->getGateway(),
                'batch_id'   => null,
            ];

             CardsPaymentRecon::dispatch($data);
            }

        }
         catch (\Exception $ex)
                {
                    $this->trace->traceException(
                        $ex,
                        Trace::ERROR,
                        TraceCode::ART_RECON_UPDATE_GATEWAY_DATA_FAILED,
                        [
                            'input'   => $input,
                            'gateway' =>  $payment->getGateway(),
                        ]
                    );

                }

    }

    /** Persist/update transaction data post recon
     * @param array $input
     * @param Payment\Entity $payment
     * @throws Exception\BadRequestException
     * @throws \Throwable
     */
    protected function updateTransactionData(array $input, Payment\Entity $payment)
    {
        $transaction = $payment->transaction;

        if (empty($transaction) === true)
        {
            throw new Exception\BadRequestException(
                'Payment transaction not found',
                $input);
        }

        $isReconciled = $transaction->isReconciled();

        // We do not need to update the reconciled at if it is already saved
        if ($isReconciled === true)
        {
            return;
        }

        $transaction->setReconciledAt($input['reconciled_at']);

        $transaction->setReconciledType($input['reconciled_type']);

        if (($payment->getMethod() === Payment\Method::UPI || $payment->getMethod() === Payment\Method::CARD) && isset($input['gateway_settled_at']) === true){

            $transaction->setGatewaySettledAt($input['gateway_settled_at']);

        }

        if ($payment->getMethod() !== Payment\Method::UPI && $payment->getMethod() !== Payment\Method::CARD)
        {
            $transaction->setGatewayAmount($input['amount']);
        }

        if ($payment->isMethodCardOrEmi() === true)
        {
            // verify this logic  for new integration
            $fee = (int)$input["card"]["gateway_fee"];

            $gst = (int)$input["card"]["gateway_service_tax"];

            $transaction = $this->recordGatewayFeeAndServiceTax($transaction ,$fee ,$gst );

            $payment->setGatewayCaptured(true);

            if (empty($input['card']['auth_code']) === false)
            {
                $payment->setReference2($input['card']['auth_code']);
            }

            if (empty($input['card']['rrn']) === false)
            {
                 $payment->setReference16($input['card']['rrn']);
            }

            if (empty($input['card']['arn']) === false)
            {
                 $payment->setReference1($input['card']['arn']);
            }

            $this->repo->saveOrFail($payment);

            $this->repo->saveOrFail($transaction);

            if ($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true)
                {
                    (new ReverseShadowPaymentsCore())->createLedgerEntryForCaptureGatewayCommissionReverseShadow($payment, $fee, $gst);
                }
       }
       else
       {
            $this->repo->saveOrFail($transaction);
       }
        if (($payment->isExternal() === true) and
            (($payment->isUpi() === true and
            $payment->isRoutedThroughPaymentsUpiPaymentService() === true) or
            ($payment->isCard() === true)))
        {
            (new Transaction\Core)->dispatchUpdatedTransactionToCPS($transaction, $payment);
        }
    }

    /**
     *  Persists the vpa and provider details
     * @param array $input
     * @param Entity $gatewayPayment
     */
    protected function updateAccountDetails(array $input, Entity $gatewayPayment)
    {
        $payerVpa = $gatewayPayment->getVpa();

        $reconVpa = $input[self::VPA] ?? null;

        $accountDetails = [];

        if (empty($input[self::VPA]) === false)
        {
            $accountDetails[self::VPA] = $input[self::VPA];
        }

        if (empty($input[self::IFSC]) === false)
        {
            $accountDetails[self::IFSC] = $input[self::IFSC];
        }

        if (empty($input[self::NAME]) === false)
        {
            $accountDetails[self::NAME] = $input[self::NAME];
        }

        if (($payerVpa === null) and
            (empty($reconVpa) === false))
        {
            $gatewayPayment->fill($accountDetails);

            $gatewayPayment->generatePspData($accountDetails);

            return;
        }

        if ((empty($payerVpa) === false) and
            (empty($reconVpa) === false))
        {
            if (strtolower($payerVpa) !== strtolower($reconVpa))
            {
                $this->trace->info(TraceCode::RECON_INFO_ALERT, [
                    'message' => 'Payer VPA is not same as in recon',
                    'info_code' => InfoCode::VPA_MISMATCH,
                    'payment_id' => $gatewayPayment->getPaymentId(),
                    'api_vpa' => $payerVpa,
                    'recon_vpa' => $reconVpa,
                    'gateway' => $gatewayPayment->getGateway()
                ]);
            }
        }
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

        $recipient = $reconDetails[RequestProcessor\Base::INPUT_DETAILS]['to'] ?? [];

        if($source === RequestProcessor\Base::MAILGUN && in_array($recipient, self::BLACKLISTED_EMAIL_FOR_API_AUTO_RECON_VIA_MAILGUN))
        {
            return;
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

    /**
     * Update Upi gateway entity for refund recon from ART
     * @param array $input
     * @throws \Exception
     */
    public function updateUpiGatewayData(array $input)
    {
        foreach ($input as $gatewayData)
        {
            $refundId = $gatewayData['refund_id'];

            $gatewayRefund = $this->getGatewayRefund($refundId);

            if ($gatewayRefund === null)
            {
                $this->trace->info(
                    TraceCode::ART_RECON_UPDATE_GATEWAY_ENTITY_NOT_FOUND,
                    $gatewayData
                );
            }
            else
            {
                try
                {
                    $this->persistNpciReferenceId($gatewayRefund, $gatewayData);

                    $this->persistNpciTransactionId($gatewayRefund,$gatewayData);

                    $this->repo->saveOrFail($gatewayRefund);
                }
                catch (\Exception $ex)
                {
                    $this->trace->traceException(
                        $ex,
                        Trace::ERROR,
                        TraceCode::ART_RECON_UPDATE_GATEWAY_DATA_FAILED,
                        [
                            'refundId' => $refundId,
                            'gateway' => $gatewayRefund->getGateway(),
                        ]
                    );

                }
            }
        }
    }

    /**
     * Fetches the gateway entity against refundID
     * @param string $refundId
     * @return mixed
     */
    protected function getGatewayRefund(string $refundId)
    {
        $gatewayRefunds = $this->repo->upi->findByRefundIdAndAction($refundId, 'refund');

        return $gatewayRefunds->first();
    }

    /** Persist Npci Reference Id post refund recon through ART
     * @param Entity $gatewayRefund
     * @param array $gatewayData
     */
    protected function persistNpciReferenceId(Entity $gatewayRefund, array $gatewayData)
    {
        if (empty($gatewayData['npci_reference_id']) === true)
        {
            return;
        }

        $dbNpciRefId = (string) $gatewayRefund->getNpciReferenceId();

        if ((empty($dbNpciRefId) === false) and
            ($gatewayData['npci_reference_id'] !== $dbNpciRefId))
        {
            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'               => InfoCode::DATA_MISMATCH,
                    'message'                 => 'Reference number in db is not same as in recon',
                    'refund_id'               => $gatewayRefund->getRefundId(),
                    'amount'                  => $gatewayRefund->getAmount(),
                    'payment_id'              => $gatewayRefund->getPaymentId(),
                    'db_reference_number'     => $dbNpciRefId,
                    'recon_reference_number'  => $gatewayData['npci_reference_id'],
                    'gateway'                 => $gatewayRefund->getGateway(),
                ]);
        }
        else
        {
            $gatewayRefund->setNpciReferenceId($gatewayData['npci_reference_id']);
        }
    }

    /** Persist Npci Txn Id post refund recon through ART
     * @param Entity $gatewayRefund
     * @param array $gatewayData
     */
    protected function persistNpciTransactionId(Entity $gatewayRefund, array $gatewayData)
    {
        if (empty($gatewayData['npci_txn_id']) === true)
        {
            return;
        }

        $dbGatewayTransactionId = (string) $gatewayRefund->getNpciTransactionId();

        if ((empty($dbGatewayTransactionId) === false) and
            ($gatewayData['npci_txn_id'] !== $dbGatewayTransactionId))
        {
            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'             => InfoCode::DATA_MISMATCH,
                    'message'               => 'Reference number in db is not same as in recon',
                    'refund_id'             => $gatewayRefund->getRefundId(),
                    'amount'                => $gatewayRefund->getAmount(),
                    'payment_id'            => $gatewayRefund->getPaymentId(),
                    'db_reference_number'   => $dbGatewayTransactionId,
                    'recon_reference_number'=> $gatewayData['npci_txn_id'],
                    'gateway'               => $gatewayRefund->getGateway(),
                ]);
        }
        else
        {
            $gatewayRefund->setNpciTransactionId($gatewayData['npci_txn_id']);
        }
    }

    public function getMailgunSource(array $input) //to be removed
    {
        $source = RequestProcessor\Base::MAILGUN;

        $requestProcessor = $this->getRequestProcessor($source);

        $this->trace->info(TraceCode::RECON_INFO, $input);

        $filePath = "/app/storage/files/app/error_verifiable_upi.csv";

        $extension = FileStore\Format::CSV;

        $fileName = "error_verifiable_upi";

        $url = $requestProcessor->automaticFileFetchUpload($filePath, "", $extension, $fileName, "");

        $this->trace->info(
            TraceCode::RECON_FILE_DETAILS, [
            'url' => $url
        ]);
    }

    private function updateNetbankingGatewayData(array $input, Payment\Entity $payment)
    {
        $data = [
            'payment_id' => $payment->getId(),
            NetbankingService::GATEWAY_TRANSACTION_ID => $input['netbanking']['gateway_transaction_id'] ?? null,
            NetbankingService::BANK_TRANSACTION_ID    => $input['netbanking']['bank_transaction_id'] ?? null,
            NetbankingService::BANK_ACCOUNT_NUMBER    => $input['netbanking']['bank_account_number'] ?? null,
            NetbankingService::ADDITIONAL_DATA        => [
                NetbankingService::CREDIT_ACCOUNT_NUMBER  => $input['netbanking']['additional_data']['credit_account_number'] ?? null,
                NetbankingService::CUSTOMER_ID            => $input['netbanking']['additional_data']['customer_id']  ?? null,
            ]
        ];

        (New NbPlusServiceRecon)->dispatchToNbplusServiceQueue($data);
    }

    /** Dispatch the entity update message to sqs queue
     * @param array $data
     * @param Payment\Entity $payment
     * @throws \Exception
     */
    protected function dispatchToUpsReconQueue(array $data, Payment\Entity $payment)
    {
        $pushData = [
            UpsConstants::PAYMENT_ID   => $payment->getId(),
            UpsConstants::GATEWAY_DATA => $data,
            UpsConstants::GATEWAY      => $payment->getGateway(),
            UpsConstants::BATCH_ID     => null,
            UpsConstants::MODEL        => UpsConstants::AUTHORIZE
        ];

        try
        {
            UpsGatewayEntityUpdate::dispatch($this->mode, $pushData);
        }
        catch (\Exception $ex)
        {
            $this->trace->error(TraceCode::UPI_PAYMENT_JOB_DISPATCH_ERROR,
                [
                    UpsConstants::PAYMENT_ID   => $payment->getId(),
                    "error_message"            => $ex->getMessage()
                ]);

            throw $ex;
        }
    }

    private function updateWalletGatewayData(array $input, Payment\Entity $payment)
    {
        $data = [
            'entity_name' => Method::WALLET,
            'recon_data' => [
                'payment_id' => $payment->getId(),
                Wallet::WALLET_TRANSACTION_ID => $input['wallet']['wallet_transaction_id'] ?? null
            ]
        ];

        (New NbPlusServiceRecon)->dispatchToNbplusServiceWalletQueue($data);
    }

    protected function recordGatewayFeeAndServiceTax($transaction , $reconGatewayFee, $reconGatewayServiceTax)
    {
        if ($transaction->getGatewayFee() === 0)
        {
            $transaction->setGatewayFee($reconGatewayFee);
        }

         if ($transaction->getGatewayServiceTax() === 0)
        {
            $transaction->setGatewayServiceTax($reconGatewayServiceTax);
        }

        return $transaction;
    }

}

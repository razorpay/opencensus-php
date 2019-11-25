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
use RZP\Models\Transaction;
use RZP\Models\Payment\Refund;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\RequestProcessor;
use RZP\Reconciliator\Base\Reconciliate;
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

    const CPS_PARAMS = [
        Reconciliate::GATEWAY_TRANSACTION_ID,
        Reconciliate::AUTH_CODE,
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

        $this->postReconciliationProcess($input);

        return $summary;
    }

    protected function postReconciliationProcess(array $input)
    {
        if ($this->isLambdaRequest() === true)
        {
            (new RequestProcessor\Lambda)->deleteFromAws($input[RequestProcessor\Lambda::KEY]);
        }
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
            foreach (self::CPS_PARAMS as $field)
            {
                if (empty($misParams[$field]) === true)
                {
                    // MIS row does not have this field, so no sense in
                    // comparing with CPS response or sending it to CPS.
                    continue;
                }

                if (empty($response[$paymentId][$field]) === true)
                {
                    // Existing CPS data is empty, Overwrite it
                    $pushData[$field] = $misParams[$field];
                }
                else if (trim($response[$paymentId][$field]) !== $misParams[$field])
                {
                    // CPS data and MIS data both are non empty and we have mismatch.
                    // Raise alert and don't save this MIS value.
                    $this->messenger->raiseReconAlert(
                        [
                            'trace_code'                => TraceCode::RECON_MISMATCH,
                            'info_code'                 => InfoCode::CPS_PAYMENT_AUTH_DATA_MISMATCH,
                            'payment_id'                => $paymentId,
                            'field'                     => $field,
                            'db_reference_number'       => $response[$paymentId][$field],
                            'recon_reference_number'    => $misParams[$field],
                            'gateway'                   => $input['gateway'],
                            'batch_id'                  => $input['batch_id'],
                        ]
                    );

                    // Skip saving this param
                    continue;
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

        if (empty($pushData) === true)
        {
            // No param has been set to be saved/overwritten,
            // no meaning in pushing to queue.

            return;
        }

        $pushData['payment_id'] = $paymentId;

        $queueName = $this->app['config']->get('queue.payment_card_api_reconciliation.' . $this->mode);

        Queue::pushRaw(json_encode($pushData), $queueName);

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'info_code' => InfoCode::RECON_CPS_QUEUE_DISPATCH,
                'queue'     => $queueName,
                'payload'   => json_encode($pushData),
            ]
        );
    }

    /**
     * @param array $response
     * @return array
     * @throws \Throwable
     */
    public function reconcileRefundsAfterScroogeRecon(array $response)
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

        $this->updateScroogeBatchSummary($batchId, $data);

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

        if ($refundData[Refund\Entity::STATUS] === Refund\Status::PROCESSED)
        {
            $refund->setStatusProcessed();

            $this->core->pushRefundProcessedMetric($refund, $source);
        }

        if ((empty($refundData[ScroogeReconciliate::ARN]) === false) and
            ((empty($refund->getReference1()) === true) or ($forceUpdateArn === true)))
        {
            $refund->setReference1($refundData[ScroogeReconciliate::ARN]);
        }

        if ((empty($refundData[Transaction\Entity::GATEWAY_SETTLED_AT]) === false) and
            (empty($refund->transaction->getGatewaySettledAt()) === true))
        {
            $refund->transaction->setGatewaySettledAt($refundData[Transaction\Entity::GATEWAY_SETTLED_AT]);
        }

        $this->repo->saveOrFail($refund);
        $this->repo->saveOrFail($refund->transaction);
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

        $this->trace->info(
            TraceCode::RECON_FILE_DETAILS,
            $reconDetails[RequestProcessor\Base::FILE_DETAILS]);

        $gateway = $requestProcessor->getGateway();

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

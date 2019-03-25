<?php

namespace RZP\Models\Payment\Refund;

use Config;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Jobs\BulkRefund as BulkRefundJob;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\Payment\Refund;
use RZP\Jobs\ScroogeRefundUpdate;
use Razorpay\Trace\Logger as Trace;
use RZP\Jobs\BulkScroogeVerifyRefund;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Payment\Refund\Entity as RefundEntity;

class Service extends Base\Service
{
    /**
     * We get the last 10 days refunds created of a gateway.
     * We run the cron for this once a day.
     */
    const GATEWAY_REFUND_RECORDS_TIME_LIMIT = 864000;

    const MAX_REFUND_RETRY_ATTEMPTS = 3;

    const MAX_REFUND_VERIFY_REQUESTS = 20;

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function create(array $input)
    {
        (new Validator)->validateInput('direct', $input);

        $paymentId = $input[Entity::PAYMENT_ID];

        unset($input[Entity::PAYMENT_ID]);

        return (new Payment\Service)->refund($paymentId, $input);
    }

    public function getRefundsFile(array $input = [])
    {
        list($from, $to) = $this->getTimestamps($input);

        $returnValue = [];

        $gatewayCode = null;

        $method = $input[Payment\Entity::METHOD];

        $email = $input['email'] ?? null;

        switch ($method)
        {
            case Payment\Method::NETBANKING:
                $gateways = Payment\Gateway::$refundFileNetbankingGateways;

                $type = Payment\Entity::BANK;

                if (isset($input['bank']))
                {
                    $gatewayCode = $input['bank'];

                    $gateway = $gateways[$gatewayCode];
                }

                // Removing kotak, axis and federal from gateways list
                // These gateways go through a reconciliation process
                // Please refer POST /reconciliate
                unset($gateways[IFSC::KKBK]);
                unset($gateways[IFSC::CORP]);
                unset($gateways[IFSC::RATN]);

                // These banks refund files have been moved to gateway_file, so
                // unsetting it here
                unset($gateways[IFSC::HDFC]);
                unset($gateways[IFSC::ICIC]);
                unset($gateways[IFSC::FDRL]);
                unset($gateways[IFSC::INDB]);
                unset($gateways[IFSC::IDFB]);
                unset($gateways[IFSC::UTIB]);
                unset($gateways[IFSC::ESFB]);
                unset($gateways[IFSC::CSBK]);
                unset($gateways[IFSC::VIJB]);
                unset($gateways[IFSC::CNRB]);
                unset($gateways[Netbanking::PUNB_R]);
                unset($gateways[Netbanking::BARB_R]);
                unset($gateways[IFSC::ALLA]);

                break;

            case Payment\Method::WALLET:
                $gateways = Payment\Gateway::$walletToGatewayMap;

                $type = Payment\Entity::WALLET;

                if (isset($input['wallet']))
                {
                    $gatewayCode = $input['wallet'];

                    $gateway = $gateways[$gatewayCode];
                }
                break;

            case Payment\Method::UPI:
                $gateways = Payment\Gateway::$upiToGatewayMap;

                $type = Payment\Entity::METHOD;
                $gatewayCode = Payment\Method::UPI;

                if (isset($input['bank']))
                {
                    $bank = $input['bank'];

                    $gateway = $gateways[$bank];
                }
                break;

            default:
                throw new Exception\LogicException(
                    'Invalid method provided for generating refunds file.',
                    null,
                    [
                        'input'     => $input,
                        'method'    => $method,
                    ]);
        }

        if ($gatewayCode === null)
        {
            foreach ($gateways as $gatewayCode => $gateway)
            {
                $returnValue[$gateway] = $this->generateRefundFileForGateway(
                    $type,
                    $gatewayCode,
                    $from,
                    $to,
                    $gateway,
                    $email
                );
            }
        }
        else
        {
            $returnValue[$gateway] = $this->generateRefundFileForGateway(
                $type,
                $gatewayCode,
                $from,
                $to,
                $gateway,
                $email
            );
        }

        $this->trace->info(
            TraceCode::REFUND_FILE_GENERATE_REQUEST,
            [
                'input'       => $input,
                'from'        => $from,
                'to'          => $to,
                'gateways'    => $gateways,
                'returnValue' => $returnValue,
            ]
        );

        return $returnValue;
    }

    protected function generateRefundFileForGateway($type, $gatewayCode, $from, $to, $gateway, $email = null)
    {
        // Handling claims file netbanking banks using daily files.
        if ((in_array($gatewayCode, Payment\Gateway::$claimsFileToBank)) and
            ($type === Payment\Entity::BANK))
        {
            $class = $this->getDailyFilesNamespace($gatewayCode);

            $result = (new $class($gatewayCode))->generate($from, $to, $email);

            return $result;
        }
        else
        {
            // TODO : Implement send email feature for other netbanking gateways.
            // Implemented for Daily file gateways.
            $refunds = $this->repo->refund->fetchRefundsForGatewayBetweenTimestamps(
                                                $type, $gatewayCode, $from, $to, $gateway);

            return $this->generateRefundFile($refunds, $email);
        }
    }

    protected function getDailyFilesNamespace($gatewayCode)
    {
        $entity = Payment\Gateway::$netbankingToGatewayMap[$gatewayCode];

        return Constants\Entity::$namespace[$entity] . '\\DailyFiles';
    }

    protected function generateRefundFile($refunds, $email = null)
    {
        $count = $refunds->count();

        if ($count === 0)
        {
            return ['count' => $count];
        }

        $data = [];

        foreach ($refunds as $refund)
        {
            $payment = $refund->payment;
            $terminal = $payment->terminal;

            $col['refund'] = $refund->toArray();
            $col['payment'] = $refund->payment->toArray();
            $col['terminal'] = $refund->payment->terminal->toArray();

            $data[] = $col;
        }

        $input['data'] = $data;
        $input['email'] = $email;

        $gateway = $terminal->getGateway();

        $file = $this->app['gateway']->call($gateway, Payment\Action::GENERATE_REFUNDS, $input, $this->mode);

        return ['file' => $file, 'count' => $count];
    }

    protected function getTimestamps($input)
    {
        $from = Carbon::yesterday(Timezone::IST)->getTimestamp();
        $to = Carbon::today(Timezone::IST)->getTimestamp() - 1;

        $frequency = 'daily';

        if (isset($input['frequency']))
        {
            $frequency = $input['frequency'];
        }

        if ($frequency === 'monthly')
        {
            if (isset($input['on']))
            {
                $dt = Carbon::createFromFormat('Y-m-d', $input['on'], Timezone::IST);

                $from = $dt->startOfMonth()->getTimestamp();

                $to   = $dt->endOfMonth()->addDay()->getTimestamp() - 1;
            }
            else
            {
                $dt = Carbon::yesterday(Timezone::IST);

                $from = $dt->startOfMonth()->getTimestamp();
                $to   = $dt->endOfMonth()->addDay()->getTimestamp() - 1;
            }
        }
        else
        {
            if (isset($input['on']))
            {
                $from = Carbon::createFromFormat('Y-m-d', $input['on'], Timezone::IST)->setTime(0,0,0);

                $fromTimeStamp = $from->getTimestamp();

                $to = $from->addDay()->getTimestamp() - 1;

                $from = $fromTimeStamp;
            }
        }

        if (isset($input['from']))
        {
            $from = $input['from'];
        }

        if (isset($input['to']))
        {
            $to = $input['to'];
        }

        return array($from, $to);
    }

    public function fetch($id)
    {
        return $this->repo->refund->fetchAndReturnPublicArray($id, $this->merchant);
    }

    public function fetchEntity($id)
    {
        $refund = $this->repo->refund->findOrFailPublic($id);

        $response = $refund->toArray();

        return $response;
    }

    public function fetchMultiple($input)
    {
        $refunds = $this->repo->refund->fetch($input, $this->merchant->getId());

        return $refunds->toArrayPublic();
    }

    public function verifyMultiple($ids)
    {
        $refundIds = explode(',', $ids);

        $data = [];

        foreach ($refundIds as $refundId)
        {
            Refund\Entity::verifyIdAndStripSign($refundId);

            $refund = $this->repo->refund->findOrFailPublic($refundId);

            $merchant = $this->repo->merchant->fetchMerchantFromEntity($refund);

            $data[] = $this->getNewProcessor($merchant)->verifyInternalRefund($refund);
        }

        return $data;
    }

    public function makeGatewayRefundCall(string $refundId, array $input)
    {
        $refund = $this->repo->refund->findOrFail($refundId);

        $merchant = $refund->merchant;

        $response = $this->getNewProcessor($merchant)->scroogeGatewayRefund($refund, $input);

        return $response;
    }

    public function makeGatewayVerifyRefundCall(string $refundId, array $input)
    {
        $refund = $this->repo->refund->findOrFail($refundId);

        $merchant = $refund->merchant;

        $response = $this->getNewProcessor($merchant)->scroogeGatewayVerifyRefund($refund, $input);

        return $response;
    }

    public function createScroogeRefund(string $refundId)
    {
        $refund = $this->repo->refund->findOrFail($refundId);

        $merchant = $refund->merchant;

        $response = $this->getNewProcessor($merchant)->callRefundFunctionOnScrooge($refund);

        return $response;
    }

    public function createScroogeRefundBulk(array $input)
    {
        (new Validator)->validateInput('create_scrooge_refund_bulk', $input);

        $this->trace->info(TraceCode::REFUND_SCROOGE_CREATE_BULK_INITIATED, $input);

        $refundIds = $input['refund_ids'];

        $successes = $failures = 0;

        $failureRefunds = [];

        $total = count($refundIds);

        Entity::verifyIdAndStripSignMultiple($refundIds);

        foreach ($refundIds as $refundId)
        {
            try
            {
                $this->createScroogeRefund($refundId);

                $successes++;
            }
            catch (\Exception $ex)
            {
                $failures++;

                $failureRefunds[] = $refundId;

                $this->trace->traceException($ex);
            }
        }

        $this->trace->info(
            TraceCode::REFUND_SCROOGE_CREATE_BULK_DISPATCHED,
            [
                'total_count'       => $total,
                'success_count'     => $successes,
                'failures_count'    => $failures,
                'failed_refunds'    => $failureRefunds
            ]);

        return [
            'total_count'       => $total,
            'success_count'     => $successes,
            'failures_count'    => $failures,
            'failed_refunds'    => $failureRefunds
        ];
    }

    /**
     * USE WITH EXTREME CAUTION
     * This calls the gateway for refund and does nothing on the api side.
     *
     * @param $refundIds
     * @return array
     */
    public function manualGatewayRefund($refundIds)
    {
        $refundIds = explode(',', $refundIds);

        $data = [];

        foreach ($refundIds as $refundId)
        {
            $refund = $this->repo->refund->findOrFail($refundId);
            $merchantId = $refund->getMerchantId();
            $merchant = $this->repo->merchant->findOrFail($merchantId);

            try
            {
                $response = $this->getNewProcessor($merchant)->manualGatewayRefund($refund);
            }
            catch(\Exception $ex)
            {
                $response = [
                    'refund_id'     => $refundId,
                    'payment_id'    => $refund->getPaymentId(),
                    'error_message' => $ex->getMessage(),
                ];

                $this->trace->traceException($ex);
            }

            $this->trace->info(
                TraceCode::MANUAL_GATEWAY_REFUND_RESPONSE,
                [
                    'refund_id'  => $refundId,
                    'payment_id' => $refund->getPaymentId(),
                    'response'   => $response
                ]
            );

            $data[] = $response;
        }

        $this->trace->info(
            TraceCode::MANUAL_GATEWAY_ALL_REFUNDS_RESPONSE,
            $data
        );

        return $data;
    }

    public function createMissingTransactions()
    {
        $refundsWithoutTransaction = $this->repo->refund->fetchRefundsWithoutTransactionsAndWithPaymentTransactions();

        $totalCount = count($refundsWithoutTransaction);

        $this->trace->info(
            TraceCode::REFUNDED_TRANSACTIONS_MISSING,
            [
                'total_count' => $totalCount,
                'refund_ids' => $refundsWithoutTransaction->pluck('id')->toArray(),
            ]);

        $summary = $this->createAllRefundsMissingTransaction($refundsWithoutTransaction);

        return $summary;
    }

    public function createMissingTransactionsForGatewayRefunded()
    {
        $gatewayRefundedWithoutTxns = $this->repo->refund->fetchGatewayRefundedRefundsWithoutTxns();

        $totalCount = count($gatewayRefundedWithoutTxns);

        $this->trace->info(
            TraceCode::GATEWAY_REFUNDED_TXNS_MISSING,
            [
                'total_count' => $totalCount,
                'refund_ids' => $gatewayRefundedWithoutTxns->pluck('id')->toArray(),
            ]);

        $summary = $this->createAllRefundsMissingTransaction($gatewayRefundedWithoutTxns);

        if ($totalCount === 0)
        {
            return $summary;
        }

        $message = 'Transactions created for gateways refunded refunds ' . $totalCount;

        $this->slack->queue($message, $summary, ['channel' => Config::get('slack.channels.tech_logs')]);

        return $summary;
    }

    public function createGatewayRefundRecords($gateway)
    {
        // Currently, we are running this for billdesk and freecharge refund timeouts only.
        if (in_array($gateway, Payment\Gateway::REFUND_TIMEOUT_HANDLED_GATEWAYS, true) === false)
        {
            throw new Exception\LogicException(
                'Cannot create a refund record on the gateway entity for the given gateway',
                null,
                [
                    'gateway' => $gateway
                ]);
        }

        $createdAfter = time() - self::GATEWAY_REFUND_RECORDS_TIME_LIMIT;

        $refunds = $this->repo->refund->fetchMissingRefundsOfGateway($gateway, $createdAfter);

        $data = [];

        // We get all the gateway refunds. We return back data for applicable and if success.
        foreach ($refunds as $refund)
        {
            $merchant = $this->repo->merchant->fetchMerchantFromEntity($refund);

            $data[] = $this->getNewProcessor($merchant)->createGatewayRefundRecord($refund);
        }

        $applicable = $success = 0;
        $successRefundData = [];

        foreach ($data as $refundData)
        {
            if ($refundData['applicable'] === true)
            {
                $applicable++;
            }

            if ($refundData['success'] === true)
            {
                $success++;
                $successRefundData[] = $refundData;
            }
        }

        $summary = [
            'total_applicable_refunds'  => $applicable,
            'total_success_refunds'     => $success,
            'success_refund_data'       => $successRefundData,
        ];

        $this->trace->info(
            TraceCode::CREATE_GATEWAY_REFUND_RECORD_SUMMARY,
            $summary);

        $message = "Gateway refund records creation";

        $this->app['slack']->queue($message, $summary, ['channel' => Config::get('slack.channels.tech_logs')]);

        return $summary;
    }

    protected function createAllRefundsMissingTransaction(
        Base\PublicCollection $refundsWithoutTxn)
    {
        $totalCount = count($refundsWithoutTxn);

        $successes = $failures = 0;
        $failureRefundIds = [];

        foreach ($refundsWithoutTxn as $refundWithoutTxn)
        {
            $success = $this->createMissingRefundTransaction($refundWithoutTxn);

            if ($success === true)
            {
                $successes++;
            }
            else
            {
                $failures++;
                $failureRefundIds[] = $refundWithoutTxn->getId();
            }
        }

        return [
            'total_count'       => $totalCount,
            'success_count'     => $successes,
            'failures_count'    => $failures,
            'failed_refunds'    => $failureRefundIds
        ];
    }

    protected function createMissingRefundTransaction(Entity $refundWithoutTxn)
    {
        $this->trace->info(
            TraceCode::REFUND_TRANSACTION_CREATE_REQUEST,
            $refundWithoutTxn->toArray());

        try
        {
            $payment = $refundWithoutTxn->payment;

            $this->repo->transaction(
                function()
                use ($refundWithoutTxn, $payment)
                {
                    $transaction = $this->getNewProcessor($refundWithoutTxn->merchant)
                                        ->createTransactionForRefund(
                                            $refundWithoutTxn, $payment);

                    if ($transaction === null)
                    {
                        throw new Exception\LogicException(
                            'Transaction did not get created',
                            null,
                            [
                                'refund_id'     => $refundWithoutTxn->getId(),
                                'payment_id'    => $payment->getId(),
                            ]);
                    }

                    //
                    // This needs to be saved here because of the association with
                    // transaction which is set in the createTransactionForRefund function.
                    //
                    $this->repo->saveOrFail($refundWithoutTxn);
                });

            return true;
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::REFUND_TRANSACTION_CREATE_FAILED,
                $refundWithoutTxn->toArray()
            );

            return false;
        }
    }

    public function createBilldeskCancelledRefunds()
    {
        $cancelledBilldeskRefunds = $this->repo->billdesk->fetchMissingBilldeskCancelledRefunds();

        $successes = $failures = 0;
        $failureRefunds = [];

        // We get all the Billdesk refunds. We return back data for applicable and if success.

        foreach ($cancelledBilldeskRefunds as $cancelledBilldeskRefund)
        {
            $paymentId = $cancelledBilldeskRefund->getPaymentId();
            $refundId = $cancelledBilldeskRefund->getRefundId();
            $refundAmount = (int) ($cancelledBilldeskRefund->getRefundAmount() * 100);

            $payment = $this->repo->payment->findOrFailPublic($paymentId);

            $merchant = $payment->merchant;

            try
            {
                $this->getNewProcessor($merchant)
                     ->createRefundOnApiForCancelledBilldeskRefund($payment, $refundId, $refundAmount);

                $successes++;
            }
            catch (\Exception $ex)
            {
                $failures++;

                $failureRefunds[] = $refundId;

                $this->trace->traceException($ex);
            }
        }

        $total = count($cancelledBilldeskRefunds);

        $summary = [
            'total'             => $total,
            'success'           => $successes,
            'failures'          => $failures,
            'failed_refunds'    => $failureRefunds,
        ];

        $this->trace->info(
            TraceCode::MISSING_BILLDESK_CANCELLED_REFUNDS,
            $summary);

        $message = 'Missing billdesk cancelled refunds created';

        $this->app['slack']->queue($message, $summary, ['channel' => Config::get('slack.channels.tech_logs')]);

        return $summary;
    }

    protected function getNewProcessor($merchant)
    {
        $processor = new Payment\Processor\Processor($merchant);

        return $processor;
    }

    public function validateUnknownGatewayRefunds(string $gateway)
    {
        $supportedGateways = Payment\Gateway::UNKNOWN_REFUNDS_VALIDATION_GATEWAYS;

        if (in_array($gateway, $supportedGateways, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_GATEWAY,
                [
                    'gateway' => $gateway,
                ]);
        }

        $repoFunc = 'fetch' . studly_case($gateway) . 'RefundsForValidation';

        $refunds = $this->repo->refund->$repoFunc();

        $failed = $unknown = $success = 0;
        $failedRefundData = [];

        foreach ($refunds as $refund)
        {
            $refundData = $this->getNewProcessor($refund->merchant)
                               ->validateUnknownGatewayRefund($refund);

            if ($refundData['success'] === true)
            {
                $success++;
            }
            else if ($refundData['success'] === false)
            {
                $failed++;
            }
            else if ($refundData['success'] === 'unknown')
            {
                $unknown++;
            }
        }

        $summary = [
            'gateway'               => $gateway,
            'total_refunds'         => count($refunds),
            'total_failed_refunds'  => $failed,
            'total_success_refunds' => $success,
            'total_unknown_refunds' => $unknown,
            'failed_refunds'        => $failedRefundData,
        ];

        $this->trace->info(
            TraceCode::GATEWAY_VALIDATE_REFUND_SUMMARY,
            $summary);

        $message = "Gateway refund records validation";

        $this->app['slack']->queue(
            $message,
            $summary,
            ['channel' => Config::get('slack.channels.tech_logs')]);

        return $summary;
    }

    public function retryFailedRefunds($input)
    {
        $this->trace->info(
            TraceCode::REFUND_RETRY_INITIATED,
            $input);

        // Adding a lock for 60 minutes to avoid race conditions on the cron.
        $summary = $this->mutex->acquireAndRelease(
            'refund_retry_failed',
            function() use ($input)
            {
                $gateways = (array) ($input['gateways'] ?? Payment\Gateway::REFUND_RETRY_GATEWAYS);

                //
                // Every combination of gateway / refund needs to be processed
                // Get the appropriate refunds and pass them as part of the refund
                // Get refunds that have failed and those that have not been
                // retried more than 3. Post every retry update last retried at.
                //
                $refunds = $this->repo
                                ->refund
                                ->fetchRefundsByGatewayAndAttempts($gateways, self::MAX_REFUND_RETRY_ATTEMPTS);

                $status = [];

                $success = $failure = 0;

                foreach ($refunds as $refund)
                {
                    $refundId = $refund->getId();

                    try
                    {
                        $processor = $this->getNewProcessor($refund->merchant);

                        $status[$refundId] = $processor->processRefundRetry($refund);

                        $success++;
                    }
                    catch (\Throwable $e)
                    {
                        $this->trace->traceException(
                            $e,
                            Trace::DEBUG,
                            TraceCode::PAYMENT_VERIFY_REFUND_EXCEPTION,
                            [
                                'refund_id' => $refundId,
                                'refund_attempts' => $refund->getAttempts()
                            ]);

                        $failure++;
                    }
                }

                return [
                    'successful'    => $success,
                    'failure'       => $failure,
                    'status'        => $status,
                ];
            },
            3600,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);

        $this->trace->info(
            TraceCode::REFUND_RETRY_RESULT,
            [
                'summary' => $summary
            ]);

        return $summary;
    }

    public function retry(string $id, array $input)
    {
        (new Validator)->validateInput('retry', $input);

        $refund = $this->repo->refund->findByPublicId($id);

        $refundStatus = $this->getNewProcessor($refund->merchant)->processRefundRetry($refund, $input);

        return [
            'refund_id' => $id,
            'status'    => $refundStatus
        ];
    }

    public function retryBulk(array $input)
    {
        (new Validator)->validateInput('retry_bulk', $input);

        $this->trace->info(TraceCode::REFUND_RETRY_BULK_INITIATED, $input);

        $refundIds = $input['refund_ids'];

        $total = count($refundIds);

        Entity::verifyIdAndStripSignMultiple($refundIds);

        foreach ($refundIds as $refundId)
        {
            $data = [
                'id' => $refundId,
                'mode' => Mode::LIVE,
                'verify' => true,
            ];

            BulkRefundJob::dispatch($data);
        }

        $this->trace->info(
            TraceCode::REFUND_RETRY_BULK_DISPATCHED,
            [
                'total' => $total
            ]);
    }

    public function directRetryBulk(array $input)
    {
        (new Validator)->validateInput('direct_retry_bulk', $input);

        $this->trace->info(TraceCode::REFUND_DIRECT_RETRY_BULK_INITIATED, $input);

        $refundIds = $input['refund_ids'];

        $total = count($refundIds);

        Entity::verifyIdAndStripSignMultiple($refundIds);

        foreach ($refundIds as $refundId)
        {
            $data = [
                'id' => $refundId,
                'mode' => Mode::LIVE,
                'verify' => false,
            ];

            BulkRefundJob::dispatch($data);
        }

        $this->trace->info(
            TraceCode::REFUND_DIRECT_RETRY_BULK_DISPATCHED,
            [
                'total' => $total
            ]);
    }

    public function verify(string $id)
    {
        $refund = $this->repo->refund->findByPublicId($id);

        $verifySuccess = $this->getNewProcessor($refund->merchant)->verifyRefund($refund);

        return [
            'refund_id'      => $id,
            'verify_success' => $verifySuccess
        ];
    }

    public function editStatus($refundId, array $input)
    {
        Refund\Entity::verifyIdAndStripSign($refundId);

        $refund = $this->repo->refund->findOrFailPublic($refundId);

        $refund->edit($input, 'editStatus');

        if ($refund->isProcessed() === true)
        {
            $refund->setErrorNull();

            if ($refund->getProcessedAt() === null)
            {
                $refund->setProcessedAt(time());
            }
        }

        if (Payment\Gateway::isScroogeGatewayLiveAtGivenTimestamp($refund->getGateway(),
                                                                  $refund->getCreatedAt()) === true)
        {
            $this->makeScroogeEditRefundRequest($refund, $input);
        }
        else
        {
            $this->repo->saveOrFail($refund);
        }

        return [
            'status' => $refund->getStatus(),
        ];
    }

    public function editNotes($id, array $input)
    {
        $refundId = Entity::verifyIdAndStripSign($id);

        $refund = $this->mutex->acquireAndRelease($refundId,
            function() use ($refundId, $input)
            {
                $refund = $this->repo->refund->findByIdAndMerchant($refundId, $this->merchant);

                $refund->edit($input, 'notes');

                $this->repo->saveOrFail($refund);

                return $refund;
            },
            20,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);

        return $refund->toArrayPublic();
    }

    public function updateScroogeRefundStatus(string $refundId, array $input)
    {
        $this->trace->info(
            TraceCode::REFUND_UPDATE_STATUS_REQUEST,
            [
                'refund_id' => $refundId,
                'status'    => $input['status'] ?? '',
            ]);

        try
        {
            $refund = $this->repo->refund->findOrFailPublic($refundId);

            $gateway = $refund->getGateway();

            if (Payment\Gateway::isScroogeGatewayAndMerchant($gateway) === true)
            {
                $refund->getValidator()->validateUpdateScroogeRefundStatus($input);

                if ($input['status'] === Status::PROCESSED)
                {
                    $this->updateRefund($refund, $input);

                    $refund->setStatusProcessed();
                    $refund->setGatewayRefunded(true);
                }
                else if ($input['status'] === Status::FAILED)
                {
                    $this->getNewProcessor($refund->merchant)->reverseRefund($refund);
                }

                $this->repo->saveOrFail($refund);

                $refund = $refund->toArrayPublic();
            }
            else
            {
                $this->trace->error(
                    TraceCode::REFUND_UPDATE_STATUS_NON_SCROOGE_GATEWAY,
                    [
                        'refund_id' => $refund->getId(),
                        'status'    => $refund->getStatus(),
                    ]);
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex, null, null, ['refund_id' => $refundId]);

            throw $ex;
        }

        return $refund;
    }

    public function markProcessedBulk(array $input)
    {
        (new Validator)->validateInput('mark_processed_bulk', $input);

        $this->trace->info(TraceCode::REFUND_MARK_PROCESSED_BULK_INITIATED, $input);

        $refundIds = $input['refund_ids'];

        $total = count($refundIds);

        $allRefundsStatuses = [];

        foreach ($refundIds as $refundId)
        {
            try
            {
                $refund = $this->repo->refund->findByPublicId($refundId);

                $this->trace->info(
                    TraceCode::REFUND_MARK_PROCESSED_OLD_STATUS,
                    [
                        'status' => $refund->getStatus()
                    ]);

                if (Payment\Gateway::isScroogeGatewayLiveAtGivenTimestamp($refund->getGateway(),
                                                                          $refund->getCreatedAt()) === true)
                {
                    $data = [
                        Payment\Entity::STATUS => Status::PROCESSED
                    ];

                    $this->makeScroogeEditRefundRequest($refund, $data);
                }
                else
                {
                    $refund->setStatusProcessed();

                    $this->repo->saveOrFail($refund);
                }

                $allRefundsStatuses[Status::PROCESSED][] = $refundId;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex, null, null, ['refund_id' => $refundId]);

                $allRefundsStatuses['errors'][] = [
                    'refund_id' => $refundId,
                    'message'   => $ex->getMessage(),
                ];
            }
        }

        $summary = [
            'total' => $total,
            'refunds_statuses' => $allRefundsStatuses,
        ];

        $this->trace->info(TraceCode::REFUND_MARK_PROCESSED_BULK_SUMMARY, $summary);

        return $summary;
    }

    /**
     * @param Entity $refund
     * @param array $input
     * @param string $event
     */
    public function makeScroogeEditRefundRequest(Entity $refund, array $input, string $event = 'processed_event')
    {
        $refund->getValidator()->validateScroogeEditRefund($input);

        $data = [
            'refunds' => [
                [
                    'refund_id'     => $refund->getId(),
                    'event'         => $event,
                    'gateway_keys'  =>
                    [
                        Entity::REFERENCE1 => $input[Entity::REFERENCE1] ?? '',
                        Entity::REFERENCE2 => $input[Entity::REFERENCE2] ?? '',
                    ]
                ]
            ],

            'mode' => $this->mode,
        ];

        $this->trace->info(
            TraceCode::REFUND_UPDATE_QUEUE_SCROOGE_DISPATCH,
                     $data
        );

        try
        {
            ScroogeRefundUpdate::dispatch($data);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::REFUND_UPDATE_QUEUE_SCROOGE_DISPATCH_FAILED,
                $data
            );
        }
    }

    public function fetchRefundDetailsForCustomer(array $input)
    {
        $traceInput = $input;
        unset($traceInput['captcha']);

        $this->trace->info(
            TraceCode::CUSTOMER_TRACK_REFUND_STATUS_INITIATED,
            [
                'input' => $traceInput
            ]
        );

        (new Validator)->validateInput('customer_refund_details', $input);

        $mode = $input['mode'] ?? Mode::LIVE;

        $this->auth->setModeAndDbConnection($mode);

        if (empty($input['payment_id']) === false)
        {
            $payment = $this->getPaymentFromPaymentIdForCustomerDetails($input['payment_id']);

            if (empty($payment) === false)
            {
                $refunds = $payment->refunds;
            }
        }
        else if (empty($input['refund_id']) === false)
        {
            $refund = $this->getRefundFromRefundIdForCustomerDetails($input['refund_id']);

            if (empty($refund) === false)
            {
                $payment = $refund->payment;

                $refunds = $payment->refunds;
            }
        }
        else
        {
            $payment = $this->getPaymentFromReservationIdForCustomerDetails($input['reservation_id']);

            if (empty($payment) === false)
            {
                $refunds = $payment->refunds;
            }
        }

        if (empty($refunds) === false)
        {
            if ($refunds->count() > 0)
            {
                // This needs to be set for `toArrayPublicCustomer`. Specifically, for the acquirer data.
                $this->auth->setMerchantById($refunds->first()->getMerchantId());
            }
        }

        $return = [
            'refunds' => isset($refunds) ? $refunds->toArrayPublicCustomer() : [],
            'payment' => isset($payment) ? $payment->toArrayPublicCustomer() : [],
        ];

        $this->trace->info(
            TraceCode::CUSTOMER_TRACK_REFUND_STATUS_SERVED,
            [
                'input' => $traceInput
            ] + $return
        );

        return $return;
    }

    protected function getPaymentFromReservationIdForCustomerDetails($reservationId)
    {
        $featureEntities = $this->repo->feature->findMerchantsHavingFeatures([Feature\Constants::IRCTC_REPORT]);

        $irctcMerchantIds = $featureEntities->pluck(Feature\Entity::ENTITY_ID)->toArray();

        $payment = $this->repo->payment->fetchFirstAuthorizedPaymentsForOrderReceiptOfMerchants($reservationId, $irctcMerchantIds);

        if (empty($payment) === true)
        {
            return null;
        }

        return $payment;
    }

    protected function getPaymentFromPaymentIdForCustomerDetails($paymentId)
    {
        Payment\Entity::stripSignWithoutValidation($paymentId);

        $payment = $this->repo->payment->find($paymentId);

        if (empty($payment) === true)
        {
            return null;
        }

        return $payment;
    }

    protected function getRefundFromRefundIdForCustomerDetails($refundId)
    {
        Entity::stripSignWithoutValidation($refundId);

        $refund = $this->repo->refund->find($refundId);

        if (empty($refund) === true)
        {
            return null;
        }

        return $refund;
    }

    protected function updateRefund($refund, $input)
    {
        if ((empty($input[RefundEntity::BANK_REFERENCE_NO]) === false) and
            (empty($refund->getReference1()) === true))
        {
            $refund->setReference1($input[RefundEntity::BANK_REFERENCE_NO]);
        }
    }

    public function updateProcessedAt(array $input)
    {
        if (isset($input['limit']) === true)
        {
            $limit = intval($input['limit']);
        }
        else
        {
            $limit = 5000;
        }

        if (isset($input['created_at']) === true)
        {
            $createdAt = $input['created_at'];
        }
        else
        {
            $createdAt = now()->subHour(6)->getTimestamp();
        }

        $start = microtime(true);

        $this->trace->info(
            TraceCode::REFUND_UPDATE_PROCESSED_AT_INITIATED,
            [
                'start_time' => $start,
                'limit'      => $limit,
                'created_at' => $createdAt,
            ]);

        $successCount  = $this->repo->refund->updateProcessedAt($limit, $createdAt);

        $end = microtime(true);

        $processingTime = $end - $start;

        $this->trace->info(
            TraceCode::REFUND_UPDATE_PROCESSED_AT_SUMMARY,
            [
                'end_time'      => $end,
                'time_taken'    => $processingTime,
                'success_count' => $successCount
            ]
        );

        return [
                'success_count' => $successCount,
                'time_taken'    => $processingTime,
        ];
    }

    public function bulkUpdateRefundsReference1(array $input)
    {
        if (empty($input['refunds']) === true)
        {
            return [
                'success_count' => 0
            ];
        }

        $start = microtime(true);

        $successCount = $failedCount = $validationErrorCount = 0;

        $failedRefundIds = [];

        foreach ($input['refunds'] as $refund)
        {
            if ((empty($refund[Refund\Entity::ID]) === true) or (empty($refund[Refund\Entity::REFERENCE1]) === true))
            {
                $validationErrorCount += 1;

                continue;
            }

            $refundEntity = $this->repo->refund->findOrFail($refund[Refund\Entity::ID]);

            $this->trace->info(
                TraceCode::REFUND_UPDATE_REFERENCE1,
                [
                    'refund_id'      => $refund[Refund\Entity::ID],
                    'old_reference1' => $refundEntity->getReference1(),
                    'new_reference1' => $refund[Refund\Entity::REFERENCE1],
                ]
            );

            if ($this->repo->refund->updateRefundReference1($refund) === 1)
            {
                $successCount += 1;
            }
            else
            {
                $failedCount += 1;

                $failedRefundIds[] = $refund[Refund\Entity::ID];
            }
        }

        $end = microtime(true);

        $processingTime = $end - $start;

        $response = [
            'success_count'          => $successCount,
            'failed_count'           => $failedCount,
            'validation_error_count' => $validationErrorCount,
            'time_taken'             => $processingTime,
            'failed_refund_ids'      => $failedRefundIds,
        ];

        $this->trace->info(
            TraceCode::REFUND_UPDATE_REFERENCE1_SUMMARY,
            $response
        );

        return $response;
    }

    public function backfillUpiMindgateReference1(array $input)
    {
        if (isset($input['limit']) === true)
        {
            $limit = intval($input['limit']);
        }
        else
        {
            $limit = 5000;
        }

        if (isset($input['from']) === true)
        {
            $from = $input['from'];
        }
        else
        {
            // Hard coding it to 25th June - this is the first Upi Mindgate refund
            $from = 1529865000;
        }

        if (isset($input['to']) === true)
        {
            $to = $input['to'];
        }
        else
        {
            // Hard coding it to 13th December 3:00 pm - this is when scrooge started sending RRN for Upi Mindgate
            // in the mark processed route - to fill the reference1
            $to = 1544693490;
        }

        if (isset($input['delay']) === true)
        {
            $delay = $input['delay'];
        }
        else
        {
            $delay = 3600;
        }

        $start = microtime(true);

        $this->trace->info(
            TraceCode::REFUND_UPDATE_RRN_INITIATED,
            [
                'start_time' => $start,
                'limit'      => $limit,
                'from'       => $from,
                'to'         => $to,
                'delay'      => $delay,
            ]);

        $successCount  = 0;

        $time = $to;

        while ($time >= $from)
        {
            $successCount += $this->repo->refund->backfillUpiMindgateReference1($limit, ($time - $delay), $time);

            $time -= $delay;
        }

        $end = microtime(true);

        $processingTime = $end - $start;

        $this->trace->info(
            TraceCode::REFUND_UPDATE_RRN_SUMMARY,
            [
                'end_time'      => $end,
                'time_taken'    => $processingTime,
                'success_count' => $successCount
            ]
        );

        return [
            'success_count' => $successCount,
            'time_taken'    => $processingTime,
        ];
    }

    public function verifyScroogeRefundsBulk(array $input)
    {
        $gateways = [Payment\Gateway::UPI_MINDGATE, Payment\Gateway::UPI_ICICI];

        $limit = (isset($input['limit']) === true) ? intval($input['limit']) : 500;

        $offset = (isset($input['offset']) === true) ? intval($input['offset']) : 0;

        $from = $input['from'] ?? (now()->subHour(24)->getTimestamp());

        $to = $input['to'] ?? (now()->getTimestamp());

        $gateways = $input['gateways'] ?? $gateways;

        $merchantIds = $input['merchant_id'] ?? [];

        $status = $input['status'] ?? 'file_init';

        $scroogeRefunds = $input['refunds'] ?? [];

        $this->trace->info(
            TraceCode::REFUND_SCROOGE_VERIFY_INITIATED,
            [
                'limit'      => $limit,
                'from'       => $from,
                'to'         => $to,
                'gateways'   => $gateways,
                'refunds'    => $scroogeRefunds,
            ]);

        if (empty($scroogeRefunds) === true)
        {
            $scroogeRefundsInput = [
                'query' => [
                    'gateway'       => $gateways,
                    'status'        => $status,
                    'created_at'    => [
                        'gte' => (string) $from,
                        'lte' => (string) $to
                    ]
                ],
                'count' => $limit,
                'skip'  => $offset,
            ];

            if (empty($merchantIds) === false)
            {
                $scroogeRefundsInput['query']['merchant_id'] = $merchantIds;
            }

            $response = $this->app['scrooge']->getRefunds($scroogeRefundsInput);

            if (isset($response['body']->data) === true)
            {
                $scroogeRefunds = json_decode(json_encode($response['body']->data), true);
            }
        }

        $failureRefunds = [];

        $total = $success = $failure = 0;

        if (empty($scroogeRefunds) === false)
        {
            foreach ($scroogeRefunds as $scroogeRefund)
            {
                $data = [
                    RefundEntity::ID        => $scroogeRefund[RefundEntity::ID],
                    RefundEntity::ATTEMPTS  => $scroogeRefund[RefundEntity::ATTEMPTS]
                ];

                $data['mode'] = Mode::LIVE;

                try
                {
                    BulkScroogeVerifyRefund::dispatch($data);

                    $success += 1;
                }
                catch (\Exception $exception)
                {
                    $failure +=1 ;

                    $failureRefunds[] = $scroogeRefund[RefundEntity::ID];

                    $this->trace->traceException($exception);
                }

                $total += 1;
            }
        }

        $traceData = [
            'total'             => $total,
            'success'           => $success,
            'failure'           => $failure,
            'failure_refunds'   => $failureRefunds,
        ];

        $this->trace->info(TraceCode::BULK_SCROOGE_REFUND_VERIFY_JOB_DISPATCHED, $traceData);

        return $traceData;
    }

    public function validateInputForVerifyRefundsInBulk(array $input)
    {
        $data = [
            'refund_data'     => [],
            'invalid_refunds' => [],
            'refund_count'    => 0
        ];

        foreach ($input['refund_data'] as $refundEntity)
        {
            $refundArray = explode (':', $refundEntity);

            $refundId = $refundArray[0];

            try
            {
                Refund\Entity::verifyIdAndStripSign($refundId);

                $refund = $this->repo->refund->findOrFailPublic($refundId);

                $payment = $refund->payment;

                $attempts = 1;

                if (($payment->isUpi() === true) and (isset($refundArray[1]) === true))
                {
                    $attempts = (int)$refundArray[1];
                }

                $data['refund_count'] += $attempts;

                $data['refund_data'][] = [
                    'refund'               => $refund,
                    RefundEntity::ATTEMPTS => $attempts
                ];
            }
            catch (\Throwable $ex)
            {
                $data['invalid_refunds'][] = [
                    RefundEntity::ID       => $refundId,
                    'failure_message'      => 'Verify Refund Not Called. Error : ' . $ex->getMessage()
                ];
            }
        }

        return $data;
    }

    public function verifyRefundsInBulk(array $input)
    {
        $this->trace->info(TraceCode::BULK_REFUND_VERIFY_REQUEST, $input);

        $data = $this->validateInputForVerifyRefundsInBulk($input);

        $response = [
            'message'    => 'Request Processed Successfully',
            'result'     => []
        ];

        if ($data['refund_count'] > self::MAX_REFUND_VERIFY_REQUESTS)
        {
            $response['message'] = 'Maximum refunds that can be verified at once is ' . self::MAX_REFUND_VERIFY_REQUESTS;

            return $response;
        }

        $fileData = [];

        $refundEntities = $data['refund_data'];

        if (empty($refundEntities) === false)
        {
            foreach ($refundEntities as $refundEntity)
            {
                $merchant = $refundEntity['refund']->merchant;

                $results = $this->getNewProcessor($merchant)->verifyScroogeRefundWithAttempts($refundEntity['refund'],
                                                                                              $refundEntity[RefundEntity::ATTEMPTS],
                                                                                              true);

                array_push($fileData, ...$results);
            }
        }

        if (empty($data['invalid_refunds']) === false)
        {
            foreach ($data['invalid_refunds'] as $invalidRefund)
            {
                $fileData[] = [
                    'refund_id'         => $invalidRefund[RefundEntity::ID],
                    'attempt_number'    => 'NA',
                    'success'           => 'NA',
                    'payment_id'        => 'NA',
                    'verify_response'   => $invalidRefund['failure_message']
                ];
            }
        }

        $response['result'] = $fileData;

        return $response;
    }
}

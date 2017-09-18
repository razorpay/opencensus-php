<?php

namespace RZP\Models\Payment\Refund;

use Config;
use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\Base;
use RZP\Constants;
use RZP\Constants\Table;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Payment\Refund;
use RZP\Exception;
use RZP\Models\Transaction;
use Razorpay\Trace\Logger as Trace;

class Service extends Base\Service
{
    /**
     * We get the last 10 days refunds created of a gateway.
     * We run the cron for this once a day.
     */
    const GATEWAY_REFUND_RECORDS_TIME_LIMIT = 864000;

    const MAX_REFUND_RETRY_ATTEMPTS = 3;

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
                unset($gateways[IFSC::UTIB]);
                unset($gateways[IFSC::FDRL]);
                unset($gateways[IFSC::RATN]);
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
                $returnValue[$gateway] = $this->generateRefundFileForGateway($type, $gatewayCode, $from, $to, $gateway, $email);
            }
        }
        else
        {
            $returnValue[$gateway] = $this->generateRefundFileForGateway($type, $gatewayCode, $from, $to, $gateway, $email);
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

    public function fetchMultiple($input)
    {
        $refunds = $this->repo->refund->fetch($input, $this->merchant->getId());

        return $refunds->toArrayPublic();
    }

    public function verify($ids)
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

        $summary = $this->createAllRefundsMissingTransaction($refundsWithoutTransaction, true);

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
        Base\PublicCollection $refundsWithoutTxn,
        bool $forceRefundTransaction = false)
    {
        $totalCount = count($refundsWithoutTxn);

        $successes = $failures = 0;
        $failureRefundIds = [];

        foreach ($refundsWithoutTxn as $refundWithoutTxn)
        {
            $success = $this->createMissingRefundTransaction($refundWithoutTxn, $forceRefundTransaction);

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

    protected function createMissingRefundTransaction(Entity $refundWithoutTxn, bool $forceRefundTransaction = false)
    {
        $this->trace->info(
            TraceCode::REFUND_TRANSACTION_CREATE_REQUEST,
            $refundWithoutTxn->toArray());

        try
        {
            $payment = $refundWithoutTxn->payment;

            $this->repo->transaction(
                function()
                use ($refundWithoutTxn, $payment, $forceRefundTransaction)
                {
                    $transaction = $this->getNewProcessor($refundWithoutTxn->merchant)
                                        ->createTransactionForRefund(
                                            $refundWithoutTxn, $payment, $forceRefundTransaction);

                    if ($transaction === null)
                    {
                        throw new Exception\LogicException(
                            'Transaction did not get created',
                            null,
                            [
                                'refund_id'     => $refundWithoutTxn->getId(),
                                'payment_id'    => $payment->getId(),
                                'force'         => $forceRefundTransaction,
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

        // Adding a lock for 15 minutes to avoid race conditions on the cron.
        // This cron is only executed once a day for now.
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
            900,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);

        $this->trace->info(
            TraceCode::REFUND_RETRY_RESULT,
            [
                'summary' => $summary
            ]);

        return $summary;
    }

    public function retry($id)
    {
        $refund = $this->repo->refund->findByPublicId($id);

        $refundStatus = $this->getNewProcessor($refund->merchant)->processRefundRetry($refund);

        return [
            'refund_id' => $id,
            'status'    => $refundStatus
        ];
    }
}

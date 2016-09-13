<?php

namespace RZP\Models\Payment\Refund;

use Carbon\Carbon;
use Config;
use RZP\Models\Bank\IFSC;
use RZP\Models\Base;
use RZP\Gateway\Netbanking;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Payment\Refund;
use RZP\Exception;
use RZP\Models\Transaction;

class Service extends Base\Service
{
    /**
     * We get the last 24 hours refunds created of a gateway.
     * We run the cron for this once a day.
     */
    const GATEWAY_REFUND_RECORDS_TIME_LIMIT = 86400;

    public function getRefundsFile(array $input = array())
    {
        list($from, $to) = $this->getTimestamps($input);

        $returnValue = [];

        $gatewayCode = null;

        $method = $input[Payment\Entity::METHOD];

        switch ($method)
        {
            case Payment\Method::NETBANKING:
                $gateways = Payment\Gateway::$netbankingToGatewayMap;
                $type = Payment\Entity::BANK;

                if (isset($input['bank']))
                {
                    $gatewayCode = $input['bank'];
                }
                break;

            case Payment\Method::WALLET:
                $gateways = Payment\Gateway::$walletToGatewayMap;
                $type = Payment\Entity::WALLET;

                if (isset($input['wallet']))
                {
                    $gatewayCode = $input['wallet'];
                }
                break;

            default:
                throw new Exception\LogicException('Invalid method provided for generating refunds file.');
        }

        if ($gatewayCode === null)
        {
            foreach ($gateways as $gatewayCode => $gateway)
            {
                $returnValue[$gateway] = $this->generateRefundFileForGateway($type, $gatewayCode, $from, $to, $gateway);
            }
        }
        else
        {
            $gateway = $gateways[$gatewayCode];

            $returnValue[$gateway] = $this->generateRefundFileForGateway($type, $gatewayCode, $from, $to, $gateway);
        }

        return $returnValue;
    }

    protected function generateRefundFileForGateway($type, $gatewayCode, $from, $to, $gateway)
    {
        // Handling netbanking kotak using seperate file.
        if (($gatewayCode === IFSC::KKBK) and
            ($type === Payment\Entity::BANK))
        {
            $result = (new Netbanking\Kotak\DailyFiles)->generate($from, $to);

            return $result;
        }
        else
        {
            $refunds = $this->repo->refund->fetchRefundsForGatewayBetweenTimestamps(
                                            $type, $gatewayCode, $from, $to, $gateway);

            return $this->generateRefundFile($refunds);
        }
    }

    protected function generateRefundFile($refunds)
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

        $gateway = $terminal->getGateway();

        $action = 'generateRefunds';

        $file = $this->app['gateway']->call($gateway, $action, $input, $this->mode);

        return ['file' => $file, 'count' => $count];
    }

    protected function getTimestamps($input)
    {
        $from = Carbon::yesterday('Asia/Kolkata')->timestamp;
        $to = Carbon::today('Asia/Kolkata')->timestamp - 1;
        $frequency = 'daily';

        if (isset($input['frequency']))
        {
            $frequency = $input['frequency'];
        }

        if ($frequency === 'monthly')
        {
            if (isset($input['on']))
            {
                $dt = Carbon::createFromFormat('Y-m-d', $input['on'], 'Asia/Kolkata');

                $from = $dt->startOfMonth()->timestamp;
                $to   = $dt->endOfMonth()->addDay()->timestamp - 1;
            }
            else
            {
                $dt = Carbon::yesterday('Asia/Kolkata');

                $from = $dt->startOfMonth()->timestamp;
                $to   = $dt->endOfMonth()->addDay()->timestamp - 1;
            }
        }
        else
        {
            if (isset($input['on']))
            {
                $from = Carbon::createFromFormat('Y-m-d', $input['on'], 'Asia/Kolkata');

                $fromTimeStamp = $from->timestamp;

                $to = $from->addDay()->timestamp - 1;

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

            $merchant = $this->repo->merchant->getMerchantFromEntity($refund);

            $data[] = $this->processor($merchant)->verifyRefund($refund);
        }

        return $data;
    }

    public function createGatewayRefundRecords($gateway)
    {
        // Currently, we are running this for billdesk refund timeouts only.
        assert ($gateway === Payment\Gateway::BILLDESK);

        $createdAfter = time() - self::GATEWAY_REFUND_RECORDS_TIME_LIMIT;

        $repoFunction = 'fetch' . studly_case($gateway) . 'Refunds';
        $billdeskRefunds = $this->repo->payment->$repoFunction($createdAfter);

        $data = [];

        // we get all the billdesk refunds. we return back data for applicable and if success.

        foreach ($billdeskRefunds as $billdeskRefund)
        {
            $merchant = $this->repo->merchant->getMerchantFromEntity($billdeskRefund);

            $data[] = $this->processor($merchant)->createGatewayRefundRecord($billdeskRefund);
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

        $message = "Gateway refund records creation";

        $this->app['slack']->queue($message, $summary, ['channel' => Config::get('slack.channels.tech_logs')]);

        return $summary;
    }

    public function createMissingTransactions()
    {
        $refundsWithoutTransaction = $this->repo->refund->fetchRefundsWithoutTransactionsAndWithPaymentTransactions();

        $totalCount = count($refundsWithoutTransaction);

        $this->trace->info(
            TraceCode::TRANSACTION_REFUND_TRACE,
            ['total_count' => $totalCount]
        );

        $successes = $failures = 0;
        $failureRefundIds = [];

        foreach ($refundsWithoutTransaction as $refundWithoutTransaction)
        {
            $this->trace->info(
                TraceCode::TRANSACTION_REFUND_TRACE,
                $refundWithoutTransaction->toArray());

            try
            {
                $payment = $refundWithoutTransaction->payment;

                $this->repo->transaction(function() use($refundWithoutTransaction, $payment)
                {
                    $transaction = $this->processor($refundWithoutTransaction->merchant)
                                        ->createTransactionForRefund(
                                            $refundWithoutTransaction, $payment);

                    $this->repo->saveOrFail($refundWithoutTransaction);

                    if ($transaction === null)
                    {
                        throw new Exception\LogicException('Should not have reached here.');
                    }
                });

                $successes += 1;
            }
            catch (\Exception $ex)
            {
                $failures += 1;
                $failureRefundIds[] = $refundWithoutTransaction->getId();

                $this->trace->error(
                    TraceCode::REFUND_TRANSACTION_FAILED,
                    $refundWithoutTransaction->toArray()
                );

                $this->trace->traceException($ex);
            }
        }

        return [
            'total_count'       => $totalCount,
            'success_count'     => $successes,
            'failure_count'     => $failures,
            'failed_refund_ids' => $failureRefundIds,
        ];
    }

    protected function processor($merchant)
    {
        $processor = new Payment\Processor\Processor($merchant);

        return $processor;
    }
}

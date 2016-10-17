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

                    $gateway = $gateways[$gatewayCode];
                }

                // Removing kotak from gateways list/
                // Should not be run along with others.
                unset($gateways[IFSC::KKBK]);
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
            $returnValue[$gateway] = $this->generateRefundFileForGateway($type, $gatewayCode, $from, $to, $gateway);
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

            $data[] = $this->getNewProcessor($merchant)->verifyRefund($refund);
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
                    $transaction = $this->getNewProcessor($refundWithoutTransaction->merchant)
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

    protected function getNewProcessor($merchant)
    {
        $processor = new Payment\Processor\Processor($merchant);

        return $processor;
    }
}

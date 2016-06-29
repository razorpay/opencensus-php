<?php

namespace Models\Payment\Refund;

use Carbon\Carbon;
use Models\Bank\IFSC;
use Models\Base;
use Models\Gateway;
use Gateway\Netbanking;
use Models\Payment;
use Models\Merchant;
use Models\Payment\Refund;
use Trace\TraceCode;
use EE\Exception;
use Models\Transaction;

class Service extends Base\Service
{
    public function getRefundsFile(array $input = array())
    {
        list($from, $to) = $this->getTimestamps($input);

        $returnValue = [];

        $gatewayCode = null;

        $method = $input['method'];

        switch ($method)
        {
            case 'netbanking':
                $gateways = Payment\Gateway::$netbankingToGatewayMap;
                $type = Payment\Entity::BANK;

                if (isset($input['bank']))
                {
                    $gatewayCode = $input['bank'];
                }
                break;

            case 'wallet':
                $gateways = Payment\Gateway::$walletToGatewayMap;
                $type = Payment\Entity::WALLET;

                if (isset($input['wallet']))
                {
                    $gatewayCode = $input['wallet'];
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

        $file = Gateway::call($gateway, $action, $input, $this->mode);

        return ['file' => $file, 'count' => $count];
    }

    protected function getTimestamps($input)
    {
        $from = Carbon::yesterday('Asia/Kolkata')->timestamp;
        $to = Carbon::today('Asia/Kolkata')->timestamp - 1;
        $frequency = 'daily';

        if(isset($input['frequency']))
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
        Refund\Entity::verifyIdAndStripSign($id);

        $refund = $this->repo->refund->findByIdAndMerchantId($id, $this->merchant->getId());

        return $refund->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $refunds = $this->repo->refund->fetch($input, $this->merchant->getId());

        return $refunds->toArrayPublic();
    }

    public function verify($id)
    {
        Refund\Entity::verifyIdAndStripSign($id);

        $refund = $this->repo->refund->findOrFail($id);

        $merchantId = $refund->getMerchantId();

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $data = $this->processor($merchant)->verifyRefund($refund);

        return $data;
    }

    public function createMissingTransactions()
    {
        $refundsWithoutTransaction = $this->repo->refund->fetchRefundsWithoutTransactionsAndWithPaymentTransactions();

        $totalCount = count($refundsWithoutTransaction);

        $this->trace->info(
            TraceCode::TRANSACTION_REFUND_TRACE,
            [
                'total_count' => $totalCount
            ]
        );

        $successes = $failures = 0;
        $failureRefundIds = [];

        foreach ($refundsWithoutTransaction as $refundWithoutTransaction)
        {
            $this->trace->info(
                TraceCode::TRANSACTION_REFUND_TRACE,
                $refundWithoutTransaction->toArray()
            );

            try
            {
                $payment = $refundsWithoutTransaction->payment;

                $transaction = $this->createTransactionForRefund($refundWithoutTransaction, $payment);

                if ($transaction === null)
                {
                    throw new Exception\LogicException(
                        "Should not have reached here."
                    );
                }

                $successes += 1;
            }
            catch (\Exception $ex)
            {
                $failures += 1;
                $failureRefundIds[] = $refundsWithoutTransaction->getId();

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

    protected function createTransactionForRefund($refund, $payment)
    {
        $gateway = $payment->getGateway();

        if ((Payment\Gateway::supportsAuthAndCapture($gateway) === false) or
            ($payment->getCaptureTimestamp() !== null))
        {
            if ($payment->transaction === null)
            {
                throw new Exception\LogicException(
                    'Transaction expected but not present for payment: ' . $payment->getId());
            }

            $txn = (new Transaction\Core)->createFromRefund($refund);

            $this->repo->saveOrFail($txn);

            return $txn;
        }

        return null;
    }

    protected function processor($merchant = null)
    {
        $bindings = $this->getBindings($merchant);

        return Payment\Processor\Processor::create($bindings);
    }

    protected function getBindings(Merchant\Entity $merchant = null)
    {
        $bindings = array(
            'merchant'  => $merchant,
            'core'      => new Payment\Core(),
            'trace'     => $this->trace,
            'mode'      => $this->mode);

        return $bindings;
    }
}

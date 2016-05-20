<?php

namespace Models\Payment\Refund;

use Carbon\Carbon;
use Models\Bank\IFSC;
use Models\Base;
use Models\Gateway;
use Models\Payment;
use Models\Merchant;
use Models\Payment\Refund;
use Trace\Trace;
use Trace\TraceCode;

class Service extends Base\Service
{
    public function getNetbankingRefundsFile(array $input = array())
    {
        list($from, $to) = $this->getTimestamps($input);

        $returnValue = [];

        if (isset($input['bank']))
        {
            $gateway = Payment\Gateway::$netbankingToGatewayMap[$input['bank']];

            $returnValue[$gateway] = $this->generateNBRefundFileForBank($input['bank'], $from, $to, $gateway);
        }
        else
        {
            foreach (Payment\Gateway::$netbankingToGatewayMap as $bankCode => $bankGateway)
            {
                $returnValue[$bankGateway] = $this->generateNBRefundFileForBank($bankCode, $from, $to, $bankGateway);
            }
        }

        return $returnValue;
    }

    public function getWalletRefundsFile(array $input = array(), $frequency = null)
    {
        $input['frequency'] = $frequency;

        list($from, $to) = $this->getTimestamps($input);

        $returnValue = [];

        if (isset($input['wallet']))
        {
            $gateway = Payment\Gateway::$walletToGatewayMap[$input['wallet']];

            $returnValue[$gateway] = $this->generateRefundFileForWallet($input['wallet'], $from, $to, $gateway);
        }
        else
        {
            foreach (Payment\Gateway::$walletToGatewayMap as $wallet => $walletGateway)
            {
                $returnValue[$walletGateway] = $this->generateRefundFileForWallet($wallet, $from, $to, $walletGateway);
            }
        }

        return $returnValue;
    }

    protected function generateRefundFileForWallet($wallet, $from, $to, $gateway)
    {
        $refunds = (new Refund\Repository)->fetchRefundsForWalletBetweenTimestamps(
                                                $wallet, $from, $to, $gateway);

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

    protected function generateNBRefundFileForBank($bankCode, $from, $to, $gateway)
    {
        $refunds = (new Refund\Repository)->fetchRefundsForBankBetweenTimestamps(
                                                $bankCode, $from, $to, $gateway);

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

        if (isset($input['on']))
        {
            $from = Carbon::createFromFormat('Y-m-d', $input['on'], 'Asia/Kolkata');

            $fromTimeStamp = $from->timestamp;

            $to = $from->addDay()->timestamp - 1;

            $from = $fromTimeStamp;
        }
        else if(isset($input['frequency']) and $input['frequency'] === 'monthly')
        {
            $from = Carbon::yesterday('Asia/Kolkata')->startOfMonth()->timestamp;
        }
        else
        {
            if (isset($input['from']))
            {
                $from = $input['from'];
            }

            if (isset($input['to']))
            {
                $to = $input['to'];
            }
        }

        return array($from, $to);
    }

    public function fetch($id)
    {
        Refund\Entity::verifyIdAndStripSign($id);

        $refund = (new Refund\Repository)->findByIdAndMerchantId($id, $this->merchant->getId());

        return $refund->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $refunds = (new Refund\Repository)->fetch($input, $this->merchant->getId());

        return $refunds->toArrayPublic();
    }

    public function verify($id)
    {
        Refund\Entity::verifyIdAndStripSign($id);

        $refund = (new Refund\Repository)->findOrFail($id);

        $merchantId = $refund->getMerchantId();

        $merchant = (new Merchant\Repository)->findOrFail($merchantId);

        $data = $this->processor($merchant)->verifyRefund($refund);

        return $data;
    }

    protected function processor($merchant = null)
    {
        $bindings = $this->getBindings($merchant);

        return Payment\Processor\Processor::create($bindings);
    }

    protected function getBindings(Merchant\Entity $merchant = null)
    {
        $trace = \Trace::getFacadeRoot();

        $bindings = array(
            'merchant'  => $merchant,
            'core'      => new Payment\Core(),
            'trace'     => $this->trace,
            'mode'      => $this->mode);

        return $bindings;
    }
}

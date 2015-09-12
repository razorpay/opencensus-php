<?php

namespace Models\Payment\Refund;

use Carbon\Carbon;
use Models\Base;
use Models\Gateway;
use Models\Payment;
use Models\Payment\Refund;
use Trace\Trace;
use Trace\TraceCode;

class Service extends Base\Service
{
    public function getHdfcNetbankingRefundsFile()
    {
        $from = Carbon::yesterday('Asia/Kolkata')->timestamp;
        $to = Carbon::today('Asia/Kolkata')->timestamp - 1;

        $refunds = (new Refund\Repository)->fetchRefundsForBankBetweenTimestamps(
            'HDFC', $from, $to);

        if ($refunds->count() === 0)
        {
            return [];
        }

        $input = [];

        foreach ($refunds as $refund)
        {
            $payment = $refund->payment;
            $terminal = $payment->terminal;

            $col['refund'] = $refund->toArray();
            $col['payment'] = $refund->payment->toArray();
            $col['terminal'] = $refund->payment->terminal->toArray();

            $input[] = $col;
        }

        $gateway = $terminal->getGateway();

        assert ($gateway === Payment\Gateway::NETBANKING_HDFC);

        $action = 'generateRefundsExcel';

        $file = Gateway::call($gateway, $action, $input, $this->mode);

        return ['file' => $file];
    }

    public function fetch($id)
    {
        Refund\Entity::verifyIdAndStripSign($id);

        $refund = (new Refund\Repository)->findByIdAndMerchantId($id, $this->merchant->getKey());

        return $refund->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $refunds = (new Refund\Repository)->fetch($input, $this->merchant->getKey());

        return $refunds->toArrayPublic();
    }
}
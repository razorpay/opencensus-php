<?php

namespace Models\Payment\Refund;

use Carbon\Carbon;
use Models\Bank\IFSC;
use Models\Base;
use Models\Gateway;
use Models\Payment;
use Models\Payment\Processor\Netbanking;
use Models\Payment\Refund;
use Trace\Trace;
use Trace\TraceCode;

class Service extends Base\Service
{
    public function getNetbankingRefundsFile(array $input = array())
    {
        $from = Carbon::yesterday('Asia/Kolkata')->timestamp;
        $to = Carbon::today('Asia/Kolkata')->timestamp - 1;

        if (isset($input['on']))
        {
            $from = Carbon::createFromFormat('Y-m-d', $input['on'], 'Asia/Kolkata');
            $to = $from->addDay()->timestamp - 1;
            $from = $from->timestamp;
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

        // Add more banks here as we direct connects with them.
        $banks = array(Netbanking::HDFC);

        if (isset($input['bank']) === false)
        {
            $input['bank'] = NetBanking::HDFC;
        }

        $bankCode = $input['bank'];

        $refunds = (new Refund\Repository)->fetchRefundsForBankBetweenTimestamps(
                                                $bankCode, $from, $to);

        $count = $refunds->count();

        if ($count === 0)
        {
            return ['count' => $count];
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

        $action = 'generateRefundsExcel';

        $file = Gateway::call($gateway, $action, $input, $this->mode);

        return ['file' => $file, 'count' => $count];
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

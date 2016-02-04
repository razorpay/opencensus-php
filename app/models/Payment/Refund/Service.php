<?php

namespace Models\Payment\Refund;

use Carbon\Carbon;
use Models\Bank\IFSC;
use Models\Base;
use Models\Gateway;
use Models\Payment;
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

            $fromTimeStamp = $from->timestamp;

            $to = $from->addDay()->timestamp - 1;

            $from = $fromTimeStamp;
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

    protected function generateNBRefundFileForBank($bankCode, $from, $to, $gateway)
    {
        $refunds = (new Refund\Repository)->fetchRefundsForBankBetweenTimestamps(
                                                $bankCode, $from, $to, $gateway);

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

        $action = 'generateRefunds';

        $file = Gateway::call($gateway, $action, $input, $this->mode);

        return ['file' => $file, 'count' => $count];
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
}

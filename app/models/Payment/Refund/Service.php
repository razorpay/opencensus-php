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

                // Refund file for kotak To be generated along with claims file.
                // Removing from here.
                unset($gateways[IFSC::KKBK]);

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
        if (($gatewayCode === IFSC::KKBK) and
            ($type === Payment\Entity::BANK))
        {
            $input = Input::all();

            $result = (new Gateway\Netbanking\Kotak\DailyFiles)->generate($from, $to);

            return $result;
        }
        else
        {
            $refunds = (new Refund\Repository)->fetchRefundsForGatewayBetweenTimestamps(
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

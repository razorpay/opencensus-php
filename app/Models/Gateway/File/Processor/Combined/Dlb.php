<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;

use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Dlb extends Base
{
    protected function formatDataForMail(array $data)
    {
        $amount = [
            'claims'  => 0,
            'refunds' => 0,
            'total'   => 0,
        ];

        $count = [
            'claims'  => 0,
            'refunds' => 0,
        ];

        $refundsFile = [];

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                $sum += $item['refund']['amount'];

                return $sum;
            });

            $count['refunds'] = count($data['refunds']);

            $refundsFile = $this->getFileData(FileStore\Type::DLB_NETBAKING_REFUND);
        }

        if (isset($data['claims']) === true)
        {
            $amount['claims'] = array_reduce($data['claims'], function ($sum, $item)
            {
                $sum += $item['payment']->getAmount();

                return $sum;
            });

            $count['claims'] = count($data['claims']);

        }

        $amount['total'] = $this->getFormattedAmount($amount['claims'] - $amount['refunds']);

        $amount['refunds'] = $this->getFormattedAmount($amount['refunds']);

        $amount['claims'] = $this->getFormattedAmount($amount['claims']);

        $date = Carbon::now(Timezone::IST)->format('d/m/y');

        $config = $this->app['config']->get('nodal.axis');

        $account = [
            'accountNumber' => $config['account_number'],
            'accountName'   => 'Razorpay Software Private Limited - Axis Bank Nodal A/c',
            'ifsc'          => $config['ifsc_code'],
            'bank'          => 'Axis Bank Limited',
        ];

        return [
            'bankName'    => 'Dlb',
            'amount'      => $amount,
            'count'       => $count,
            'date'        => $date,
            'refundsFile' => $refundsFile,
            'emails'      => $this->gatewayFile->getRecipients(),
            'account'     => $account,
        ];
    }

    protected function getFormattedAmount($amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}

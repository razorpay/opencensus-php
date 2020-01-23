<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Kvb extends Base
{
    const BANK_NAME = 'Kvb';

    protected function formatDataForMail(array $data)
    {
        $amount = [
            'claims'  => 0,
            'refunds' => 0,
        ];

        $count = [
            'claims'  => 0,
            'refunds' => 0,
            'total'   => 0
        ];

        $refundsFile = $claimsFile = [];

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                $sum += $item['refund']['amount'];

                return $sum;
            });

            $count['refunds'] = count($data['refunds']);

            $refundsFile = $this->getFileData(FileStore\Type::KVB_NETBANKING_REFUND);
        }

        if (isset($data['claims']) === true)
        {
            $amount['claims'] = array_reduce($data['claims'], function ($sum, $item)
            {
                $sum += $item['payment']->getAmount();

                return $sum;
            });

            $count['claims'] = count($data['claims']);

            $claimsFile = $this->getFileData(FileStore\Type::KVB_NETBANKING_CLAIM);
        }

        $count['total']  = $count['claims'] - $count['refunds'];
        $amount['total'] = $amount['claims'] - $amount['refunds'];

        $amount['total']   = number_format($amount['total'] / 100, 2, '.', '');
        $amount['refunds'] = number_format($amount['refunds'] / 100, 2, '.', '');
        $amount['claims']  = number_format($amount['claims'] / 100, 2, '.', '');

        $date = Carbon::yesterday(Timezone::IST)->format('d.m.Y');

        $config = $this->app['config']->get('nodal.axis');

        $account = [
            'accountNumber' => $config['account_number'],
            'accountName'   => 'Razorpay Software Private Limited - Axis Bank Nodal A/c',
            'ifsc'          => $config['ifsc_code'],
        ];

        return [
            'bankName'    => self::BANK_NAME,
            'amount'      => $amount,
            'count'       => $count,
            'refundsFile' => $refundsFile,
            'claimsFile'  => $claimsFile,
            'date'        => $date,
            'account'     => $account,
            'emails'      => $this->gatewayFile->getRecipients(),
        ];
    }
}

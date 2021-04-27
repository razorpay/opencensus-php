<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;

use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Sbin extends Base
{
    const BANK_NAME = 'Sbi';

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
            'total'   => 0
        ];

        $refundsFile = [];
        $claimsFile  = [];

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                $sum += $item['refund']['amount'];

                return $sum;
            });

            $amount['refunds'] = $amount['refunds'] / 100;

            $count['refunds'] = count($data['refunds']);

            $refundsFile = $this->getFileData(FileStore\Type::SBI_NETBANKING_REFUND);
        }

        if (isset($data['claims']) === true)
        {
            $amount['claims'] = array_reduce($data['claims'], function ($sum, $item)
            {
                $sum += $item['payment']->getAmount();

                return $sum;
            });

            $amount['claims'] = $amount['claims'] / 100;

            $count['claims'] = count($data['claims']);

            $claimsFile = $this->getFileData(FileStore\Type::SBI_NETBANKING_CLAIM);
        }

        $amount['total'] = $amount['claims'] - $amount['refunds'];

        $count['total'] = $count['refunds'] + $count['claims'];

        $date = Carbon::now(Timezone::IST)->format('d-m-Y');

        $config = $this->app['config']->get('nodal.axis');

        $accountDetails = [
            'accountNumber' => $config['account_number'],
            'accountName'   => 'Razorpay Software Private Limited - Axis Bank Nodal A/c',
            'ifsc'          => $config['ifsc_code'],
        ];

        return [
            'bankName'       => self::BANK_NAME,
            'amount'         => $amount,
            'count'          => $count,
            'refundsFile'    => $refundsFile,
            'claimsFile'     => $claimsFile,
            'date'           => $date,
            'emails'         => $this->gatewayFile->getRecipients(),
            'accountDetails' => $accountDetails
        ];
    }
}

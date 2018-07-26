<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Allahabad extends Base
{
    const BANK_NAME = 'Allahabad';

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

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                $sum += $item['refund']['amount'];

                return $sum;
            });

            $amount['refunds'] = $amount['refunds'] / 100;

            $count['refunds'] = count($data['refunds']);

            $refundsFile = $this->getFileData(FileStore\Type::ALLAHABAD_NETBANKING_REFUND);
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
        }

        $amount['total'] = $amount['claims'] - $amount['refunds'];

        $count['total'] = $count['refunds'] + $count['claims'];

        $date = Carbon::now(Timezone::IST)->format('d/m/Y');

        $config = $this->app['config']->get('nodal.axis');

        $accountDetails = [
            'bankName'      => 'Axis Bank Ltd',
            'accountNumber' => $config['account_number'],
            'accountName'   => 'Razorpay Software Private Limited',
            'ifsc'          => $config['ifsc_code'],
        ];

        return [
            'bankName'       => self::BANK_NAME,
            'amount'         => $amount,
            'count'          => $count,
            'refundsFile'    => $refundsFile,
            'date'           => $date,
            'emails'         => $this->gatewayFile->getRecipients(),
            'accountDetails' => $accountDetails
        ];
    }
}

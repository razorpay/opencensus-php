<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;

use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Ujjivan extends Base
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

        $claimsFile = [];
        $refundsFile = [];

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                $sum += $item['refund']['amount'];

                return $sum;
            });

            $count['refunds'] = count($data['refunds']);

            $refundsFile = $this->getFileData(FileStore\Type::UJJIVAN_NETBANKING_REFUND);
        }

        if (isset($data['claims']) === true)
        {
            $amount['claims'] = array_reduce($data['claims'], function ($sum, $item)
            {
                $sum += $item['payment']->getAmount();

                return $sum;
            });

            $count['claims'] = count($data['claims']);

            $claimsFile = $this->getFileData(FileStore\Type::UJJIVAN_NETBANKING_CLAIMS);

        }

        $amount['total'] = $this->getFormattedAmount($amount['claims'] - $amount['refunds']);

        $amount['refunds'] = $this->getFormattedAmount($amount['refunds']);

        $amount['claims'] = $this->getFormattedAmount($amount['claims']);

        $date = Carbon::now(Timezone::IST)->format('d/m/y');

        $config = $this->app['config']->get('nodal.axis');

        $accountDetails = [
            'bankName'      => 'Axis Bank Ltd',
            'accountNumber' => $config['account_number'],
            'accountName'   => 'Razorpay Software Private Limited',
            'ifsc'          => $config['ifsc_code'],
        ];

        return [
            'bankName'          => 'Ujjivan',
            'amount'            => $amount,
            'count'             => $count,
            'date'              => $date,
            'refundsFile'       => $refundsFile,
            'claimsFile'        => $claimsFile,
            'emails'            => $this->gatewayFile->getRecipients(),
            'accountDetails'    => $accountDetails
        ];
    }

    protected function getFormattedAmount($amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}

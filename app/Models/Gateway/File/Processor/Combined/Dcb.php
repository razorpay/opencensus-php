<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;

use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Dcb extends Base
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

        $claimsFile = $refundsFile = [];

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                $sum += $item['refund']['amount'];

                return $sum;
            });

            $count['refunds'] = count($data['refunds']);

            $refundsFile = $this->getFileData(FileStore\Type::DCB_NETBANKING_REFUND);
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

        $amount['total'] = $amount['claims'] - $amount['refunds'];

        $amount['total']   = number_format($amount['total'] / 100, 2, '.', '');
        $amount['refunds'] = number_format($amount['refunds'] / 100, 2, '.', '');
        $amount['claims']  = number_format($amount['claims'] / 100, 2, '.', '');

        $config = $this->app['config']->get('nodal.axis');

        $account = [
            'accountNumber'  => $config['account_number'],
            'accountName'    => 'Razorpay Software Private Limited',
            'bankName'       => 'Axis Bank Ltd',
            'branchCity'     => 'Bangalore',
            'branchLocation' => 'Koramangala',
            'ifsc'           => $config['ifsc_code'],
        ];

        $txnDate = Carbon::now()->format('d-m-Y');

        return [
            'bankName'    => 'Dcb',
            'subject'     => 'Razorpay_DCB_Netbanking_PG claimed & refund file for ' . $txnDate,
            'amount'      => $amount,
            'count'       => $count,
            'claimsFile'  => $claimsFile,
            'refundsFile' => $refundsFile,
            'account'     => $account,
            'emails'      => $this->gatewayFile->getRecipients(),
        ];
    }
}

<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use RZP\Models\FileStore;

class Dcb extends Base
{
    protected function formatDataForMail(array $data)
    {
        $amount = [
            'claims'  => 0,
            'refunds' => 0,
            'total'   => 0,
        ];

        $claimsFile = $refundsFile = [];

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                $sum += $item['refund']['amount'];

                return $sum;
            });

            $refundsFile = $this->getFileData(FileStore\Type::DCB_NETBANKING_REFUND);
        }

        if (isset($data['claims']) === true)
        {
            $amount['claims'] = array_reduce($data['claims'], function ($sum, $item)
            {
                $sum += $item['payment']->getAmount();

                return $sum;
            });
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

        return [
            'bankName'    => 'Dcb',
            'amount'      => $amount,
            'claimsFile'  => $claimsFile,
            'refundsFile' => $refundsFile,
            'account'     => $account,
            'emails'      => $this->gatewayFile->getRecipients(),
        ];
    }
}

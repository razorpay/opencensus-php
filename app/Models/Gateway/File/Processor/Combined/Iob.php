<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Iob extends Base
{
    const BANK_NAME = 'Iob';

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

        $refundsFile = $claimsFile = [];

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                $sum += $item['refund']['amount'];

                return $sum;
            });

            $count['refunds'] = count($data['refunds']);

            $refundsFile = $this->getFileData(FileStore\Type::IOB_NETBANKING_REFUND);
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

        $count['total']  = $count['refunds'] + $count['claims'];
        $amount['total'] = $amount['claims'] - $amount['refunds'];

        $amount['total']   = number_format($amount['total'] / 100, 2, '.', '');
        $amount['refunds'] = number_format($amount['refunds'] / 100, 2, '.', '');
        $amount['claims']  = number_format($amount['claims'] / 100, 2, '.', '');

        $date = Carbon::yesterday(Timezone::IST)->format('d.m.Y');

        $fromDate = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)->format('d.m.Y');
        $toDate = Carbon::createFromTimestamp($this->gatewayFile->getEnd(), Timezone::IST)->format('d.m.Y');

        $config = $this->app['config']->get('nodal.axis');

        $account = [
            'accountNumber' => $config['account_number'],
            'accountName'   => 'Razorpay Software Private Limited',
            'ifsc'          => $config['ifsc_code'],
            'bankName'      => 'Axis Bank Ltd',
        ];

        $emailIds = [
            'recon'         => 'finances.recon@razorpay.com, amit.mohanty@razorpay.com',
            'l1'            => 'settlements@razorpay.com',
            'l2'            => 'chandrababu.g@razorpay.com',
            'transaction'   => 'support@razorpay.com'
        ];

        return [
            'bankName'      => self::BANK_NAME,
            'amount'        => $amount,
            'count'         => $count,
            'refundsFile'   => $refundsFile,
            'date'          => $date,
            'from'          => $fromDate,
            'to'            => $toDate,
            'account'       => $account,
            'rzpEmailId'    => $emailIds,
            'emails'        => $this->gatewayFile->getRecipients(),
        ];
    }
}

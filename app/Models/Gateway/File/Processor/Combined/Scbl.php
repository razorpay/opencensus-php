<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Scbl extends Base
{
    const BANK_NAME = 'Scbl';

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

        $refundsFile = [];

        $claimsFile = [];

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                $sum += $item['refund']['amount'];

                return $sum;
            });

            $count['refunds'] = count($data['refunds']);

            $refundsFile = $this->getFileData(FileStore\Type::SCB_NETBANKING_REFUND);
        }

        if (isset($data['claims']) === true)
        {
            $amount['claims'] = array_reduce($data['claims'], function ($sum, $item)
            {
                $sum += $item['payment']->getAmount();

                return $sum;
            });

            $count['claims'] = count($data['claims']);

            $claimsFile = $this->getFileData(FileStore\Type::SCB_NETBANKING_CLAIM);
        }

        $amount['total'] = $amount['claims'] - $amount['refunds'];

        $amount['total']   = number_format($amount['total'] / 100, 2, '.', '');
        $amount['refunds'] = number_format($amount['refunds'] / 100, 2, '.', '');
        $amount['claims']  = number_format($amount['claims'] / 100, 2, '.', '');

        $date = Carbon::now(Timezone::IST)->format('d-m-Y');


        return [
            'bankName'    => self::BANK_NAME,
            'amount'      => $amount,
            'count'       => $count,
            'claimsFile'  => $claimsFile,
            'refundsFile' => $refundsFile,
            'date'        => $date,
            'emails'      => $this->gatewayFile->getRecipients(),
        ];
    }
}

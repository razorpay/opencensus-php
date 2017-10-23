<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Rbl extends Base
{
    protected function formatDataForMail()
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
        $refundFile = [];

        if (isset($this->data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($this->data['refunds'], function ($sum, $item)
            {
                $sum += ($item['refund']['amount'] / 100);

                return $sum;
            });

            $count['refunds'] = count($this->data['refunds']);

            $refundsFile = $this->getFileData(FileStore\Type::RBL_NETBANKING_REFUND);
        }

        if (isset($this->data['claims']) === true)
        {
            $amount['claims'] = array_reduce($this->data['claims'], function ($sum, $item)
            {
                $sum += ($item['payment']->getAmount() / 100);

                return $sum;
            });

            $count['claims'] = count($this->data['claims']);

            $claimsFile = $this->getFileData(FileStore\Type::RBL_NETBANKING_CLAIM);
        }

        $amount['total'] = $amount['claims'] - $amount['refunds'];

        $date = Carbon::now(Timezone::IST)->format('jS F Y');

        return [
            'bankName'    => 'Rbl',
            'amount'      => $amount,
            'count'       => $count,
            'date'        => $date,
            'claimsFile'  => $claimsFile,
            'refundsFile' => $refundsFile,
            'emails'      => $this->gatewayFile->getRecipients(),
        ];
    }
}

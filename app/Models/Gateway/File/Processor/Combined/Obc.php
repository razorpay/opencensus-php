<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Models\FileStore;
use RZP\Models\Gateway\File\Type;

class Obc extends Base
{
    protected function formatDataForMail(array $data)
    {
        $amount = [
            'claims'  => 0.00,
            'refunds' => 0.00,
            'total'   => 0.00,
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
                $sum += $this->getFormattedAmount($item['refund']['amount']);

                return $sum;
            });

            $count['refunds'] = count($data['refunds']);

            $refundsFile = $this->getFileData(FileStore\Type::OBC_NETBANKING_REFUND);
        }

        if (isset($data['claims']) === true)
        {
            $amount['claims'] = array_reduce($data['claims'], function ($sum, $item)
            {
                $sum += $this->getFormattedAmount($item['payment']->getAmount());

                return $sum;
            });

            $count['claims'] = count($data['claims']);
        }

        $amount['total'] = $amount['claims'] - $amount['refunds'];

        $date = Carbon::now(Timezone::IST)->format('d/m/y');

        return [
            'bankName'    => 'obc',
            'amount'      => $amount,
            'count'       => $count,
            'date'        => $date,
            'refundsFile' => $refundsFile,
            'emails'      => $this->gatewayFile->getRecipients()
        ];
    }

    protected function getFormattedAmount($amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}

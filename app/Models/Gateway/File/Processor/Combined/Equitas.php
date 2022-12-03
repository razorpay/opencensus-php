<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;

use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Equitas extends Base
{
    protected function formatDataForMail(array $data): array
    {
        $amount = [
            'claims'  => 0,
            'refunds' => 0,
            'total'   => 0,
        ];

        $count = [
            'claims'  => 0,
            'refunds' => 0,
            'total'   => 0,
        ];

        $refundsFile= [];

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                $sum += ($item['refund']['amount'] / 100);

                return $sum;
            });

            $count['refunds'] = count($data['refunds']);

            $refundsFile = $this->getFileData(FileStore\Type::EQUITAS_NETBANKING_REFUND);
        }

        if (isset($data['claims']) === true)
        {
            $amount['claims'] = array_reduce($data['claims'], function ($sum, $item)
            {
                $sum += ($item['payment']->getAmount() / 100);

                return $sum;
            });

            $count['claims'] = count($data['claims']);
        }

        $amount['total'] = $amount['claims'] - $amount['refunds'];

        $count['total'] = $count['refunds'] + $count['claims'];

        $date = Carbon::now(Timezone::IST)->format('jS F Y');

        return [
            'bankName'    => 'Equitas',
            'amount'      => $amount,
            'count'       => $count,
            'refundsFile' => $refundsFile,
            'date'        => $date,
            'emails'      => $this->gatewayFile->getRecipients(),
        ];
    }
}

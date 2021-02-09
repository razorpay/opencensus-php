<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Federal extends Base
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
            'total'   => 0
        ];

        $refundsFile= [];

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                if($item['payment']['cps_route'] !== 3)
                {
                    $sum += ($item['refund']['amount'] / 100);
                }
                return $sum;
            });

            $count['refunds'] = array_reduce($data['refunds'], function ($count, $item)
            {
                if($item['payment']['cps_route'] !== 3)
                {
                    $count += 1;
                }
                return $count;
            });

            $refundsFile = $this->getFileData(FileStore\Type::FEDERAL_NETBANKING_REFUND);
        }

        if (isset($data['claims']) === true)
        {
            $amount['claims'] = array_reduce($data['claims'], function ($sum, $item)
            {
                if($item['payment']['cps_route'] !== 3)
                {
                    $sum += ($item['payment']->getAmount() / 100);
                }
                return $sum;
            });

            $count['claims'] = array_reduce($data['claims'], function ($count, $item) {

                if($item['payment']['cps_route'] !== 3)
                {
                    $count += 1;
                }
                return $count;
            });
        }

        $amount['total'] = $amount['claims'] - $amount['refunds'];

        $count['total'] = $count['refunds'] + $count['claims'];

        $date = Carbon::now(Timezone::IST)->format('jS F Y');

        return [
            'bankName'    => 'Federal',
            'amount'      => $amount,
            'count'       => $count,
            'refundsFile' => $refundsFile,
            'date'        => $date,
            'emails'      => $this->gatewayFile->getRecipients(),
        ];
    }
}

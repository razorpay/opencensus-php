<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor;

class NetbankingIndusind extends Processor\Base
{
    use GenerateCombinedFile;

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
            'total'   => 0
        ];

        $claimsFile = [];

        $refundsFile= [];

        if (isset($this->data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($this->data['refunds'], function ($sum, $item)
            {
                $sum += ($item['refund']['amount'] / 100);

                return $sum;
            });

            $count['refunds'] = count($this->data['refunds']);

            $refundsFile = $this->getFileData(FileStore\Type::INDUSIND_NETBANKING_REFUND);
        }

        if (isset($this->data['claims']) === true)
        {
            $amount['claims'] = array_reduce($this->data['claims'], function ($sum, $item)
            {
                $sum += ($item['payment']->getAmount() / 100);

                return $sum;
            });

            $count['claims'] = count($this->data['claims']);
        }

        $amount['total'] = $amount['claims'] - $amount['refunds'];

        $count['total'] = $count['refunds'] + $count['claims'];

        $date = Carbon::now(Timezone::IST)->format('jS F Y');

        return [
            'bankName'    => 'Indusind',
            'amount'      => $amount,
            'count'       => $count,
            'claimsFile'  => $claimsFile,
            'refundsFile' => $refundsFile,
            'date'        => $date,
            'emails'      => $this->gatewayFile->getRecipients(),
        ];
    }
}

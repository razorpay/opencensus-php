<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use RZP\Models\FileStore;

class Sib extends Base
{
    const BANK_NAME = 'SIB';

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
                $sum += ($item['refund']['amount']);

                return $sum / 100;
            });

            $count['refunds'] = count($data['refunds']);

            $refundsFile = $this->getFileData(FileStore\Type::SIB_NETBANKING_REFUND);
        }

        if (isset($data['claims']) === true)
        {
            $amount['claims'] = array_reduce($data['claims'], function ($sum, $item)
            {
                $sum += ($item['payment']['amount']);

                return $sum / 100;
            });

            $count['claims'] = count($data['claims']);
        }

        $amount['total'] = $amount['claims'] - $amount['refunds'];

        $count['total'] = $count['claims'] + $count['refunds'];

        return [
            'bankName'    => self::BANK_NAME,
            'amount'      => $amount,
            'count'       => $count,
            'refundsFile' => $refundsFile,
            'emails'      => $this->gatewayFile->getRecipients(),
        ];
    }
}

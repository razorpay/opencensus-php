<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Models\FileStore;
use RZP\Models\Gateway\File\Type;

class Obc extends Base
{
    public function createFile($data)
    {
        if (isset($data['refunds']) === true)
        {
            $refundFileProcessor = $this->getFileProcessor(Type::REFUND);

            $refundFileProcessor->createFile($data['refunds']);
        }
    }

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

        $refundsFile = [];

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                $sum += ($item['refund']['amount'] / 100);

                return $sum;
            });

            $count['refunds'] = count($data['refunds']);

            $refundsFile = $this->getFileData(FileStore\Type::OBC_NETBANKING_REFUND);
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
            'bankName'    => 'OBC',
            'amount'      => $amount,
            'count'       => $count,
            'date'        => $date,
            'refundsFile' => $refundsFile,
            'emails'      => $this->gatewayFile->getRecipients()
        ];
    }
}

<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Processor;

class Axis extends Processor\Base
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
        $refundFile = [];

        if (isset($this->data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($this->data['refunds'], function ($sum, $item)
            {
                $sum += ($item['refund']['amount'] / 100);

                return $sum;
            });

            $count['refunds'] = count($this->data['refunds']);

            $refundsFile = $this->getFileData(FileStore\Type::AXIS_NETBANKING_REFUND);
        }

        if (isset($this->data['claims']) === true)
        {
            $amount['claims'] = array_reduce($this->data['claims'], function ($sum, $item)
            {
                $sum += ($item['payment']->getAmount() / 100);

                return $sum;
            });

            $count['claims'] = count($this->data['claims']);

            $claimsFile = $this->getFileData(FileStore\Type::AXIS_NETBANKING_CLAIMS);
        }

        $amount['total'] = $amount['claims'] - $amount['refunds'];

        $count['total'] = $count['refunds'] + $count['claims'];

        $date = Carbon::now(Timezone::IST)->format('jS F Y');

        return [
            'bankName'    => 'Axis',
            'amount'      => $amount,
            'count'       => $count,
            'date'        => $date,
            'claimsFile'  => $claimsFile,
            'refundsFile' => $refundsFile,
            'emails'      => $this->gatewayFile->getRecipients()
        ];
    }

    protected function getFileData(string $type)
    {
        $file = $this->gatewayFile
                     ->files()
                     ->where(FileStore\Entity::TYPE, $type)
                     ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $fileData = [
            'url'  => $signedUrl,
            'name' => $file->getLocation(),
        ];

        return $fileData;
    }
}

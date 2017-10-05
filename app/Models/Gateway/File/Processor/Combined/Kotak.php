<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Kotak extends Base
{
    protected function formatDataForMail()
    {
        $amount = [
            'claims'  => 0,
            'refunds' => 0,
            'total'   => 0,
        ];

        $refundsFile = [];

        $claimsFile = [];

        if (isset($this->data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($this->data['refunds'], function ($sum, $item)
            {
                $sum += ($item['refund']['amount'] / 100);

                return $sum;
            });

            $refundsFile = $this->getFileData(FileStore\Type::KOTAK_NETBANKING_REFUND);
        }

        if (isset($this->data['claims']) === true)
        {
            $amount['claims'] = array_reduce($this->data['claims'], function ($sum, $item)
            {
                $sum += ($item['payment']->getAmount() / 100);

                return $sum;
            });

            $claimsFile = $this->getFileData(FileStore\Type::KOTAK_NETBANKING_CLAIM);
        }

        $amount['total'] = $amount['claims'] - $refundData['refunds'];

        return [
            'bankName'    => 'Kotak',
            'amount'      => $amount,
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

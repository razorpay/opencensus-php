<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;

class Kotak extends Base
{
    protected function formatDataForMail(array $data)
    {
        $amount = [
            'claims'  => 0,
            'refunds' => 0,
            'total'   => 0,
        ];

        $refundsFile = [];

        $claimsFile = [];

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                $sum += ($item['refund']['amount'] / 100);

                return $sum;
            });

            $refundsFile = $this->getFileData(FileStore\Type::KOTAK_NETBANKING_REFUND);
        }

        if (isset($data['claims']) === true)
        {
            $amount['claims'] = array_reduce($data['claims'], function ($sum, $item)
            {
                $sum += ($item['payment']->getAmount() / 100);

                return $sum;
            });

            $claimsFile = $this->getFileData(FileStore\Type::KOTAK_NETBANKING_CLAIM);
        }

        $amount['total'] = $amount['claims'] - $amount['refunds'];

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
            'name' => basename($file->getLocation()),
        ];

        return $fileData;
    }
}

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

        $refundData = [];

        $claimData = [];

        if (isset($this->data['refunds']) === true)
        {
            $refundData['total_amount'] = array_reduce($this->data['refunds'], function ($sum, $item)
            {
                $sum += ($item['refund']['amount'] / 100);

                return $sum;
            });

            $refundFileData = $this->getFileData(FileStore\Type::KOTAK_NETBANKING_REFUND);

            $refundData = array_merge($refundData, $refundFileData);
        }

        if (isset($this->data['claims']) === true)
        {
            $claimData['total_amount'] = array_reduce($this->data['claims'], function ($sum, $item)
            {
                $sum += ($item['payment']->getAmount() / 100);

                return $sum;
            });

            $claimsFileData = $this->getFileData(FileStore\Type::KOTAK_NETBANKING_CLAIM);

            $claimData = array_merge($claimData, $claimsFileData);
        }

        $amount['claims'] = $claimData['total_amount'];
        $amount['refunds'] = $refundData['total_amount'];
        $amount['total'] = $claimData['total_amount'] - $refundData['total_amount'];

        return [
            'bankName'    => 'Kotak',
            'amount'      => $amount,
            'claimsFile'  => $claimData,
            'refundsFile' => $refundData,
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

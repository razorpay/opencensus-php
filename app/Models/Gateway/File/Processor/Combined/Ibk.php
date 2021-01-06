<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use RZP\Models\FileStore;

class Ibk extends Base
{
    protected function formatDataForMail(array $data)
    {
        $refundsFile = $this->getFileData(FileStore\Type::IBK_NETBANKING_REFUND);

        $claimsFile = $this->getFileData(FileStore\Type::IBK_NETBANKING_CLAIM);

        return [
            'bankName'    => 'Ibk',
            'claimsFile'  => $claimsFile,
            'refundsFile' => $refundsFile,
            'emails'      => $this->gatewayFile->getRecipients(),
        ];
    }
}

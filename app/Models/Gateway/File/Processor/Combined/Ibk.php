<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use RZP\Models\FileStore;
use RZP\Models\Gateway\File\Type;

class Ibk extends Base
{
    protected function formatDataForMail(array $data)
    {
        $refundsFile = $claimsFile = [];

        if (isset($data['refunds']) === true)
        {
            $refundsFile = $this->getFileData(FileStore\Type::IBK_NETBANKING_REFUND);
        }

        if (isset($data['claims']) === true)
        {
            $claimsFile = $this->getFileData(FileStore\Type::IBK_NETBANKING_CLAIM);
        }

        return [
            'bankName'    => 'Ibk',
            'claimsFile'  => $claimsFile,
            'refundsFile' => $refundsFile,
            'emails'      => $this->gatewayFile->getRecipients(),
        ];
    }

    public function createFile($data)
    {
        if (isset($data['refunds']) === true)
        {
            $refundFileProcessor = $this->getFileProcessor(Type::REFUND);

            $refundFileProcessor->createFile($data['refunds']);
        }

        if (isset($data) === true)
        {
            $claimFileProcessor = $this->getFileProcessor(Type::CLAIM);

            $claimFileProcessor->createFile($data);
        }
    }
}

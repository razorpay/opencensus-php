<?php

namespace RZP\Reconciliator\UpiIcici;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const REFUND_RECON_FILE_NAME    = 'refund_report';
    const PAYMENT_RECON_FILE_NAME   = 'mis_report';

    const SHEET0_NAME               = 'Recon MIS';

    protected function getTypeName($fileName)
    {
        $type = null;

        if (strpos(strtolower($fileName), self::PAYMENT_RECON_FILE_NAME) !== false)
        {
            $type = self::PAYMENT;
        }
        else if (strpos(strtolower($fileName), self::REFUND_RECON_FILE_NAME) !== false)
        {
            $type = self::REFUND;
        }

        return $type;
    }

    public function getSheetNames(array $fileDetails = [])
    {
        $reconType = $this->getTypeName($fileDetails['file_name']);

        if ($reconType === self::PAYMENT)
        {
            return [self::SHEET0_NAME];
        }

        return [];
    }

    protected function getFileName(array $extraDetails): string
    {
        return $extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::FILE_NAME];
    }
}
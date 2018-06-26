<?php

namespace RZP\Reconciliator\UpiIcici;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const REFUND_RECON_FILE_NAME    = 'refund_report';
    const PAYMENT_RECON_FILE_NAME   = 'mis_report';

    const SHEET_NAME                = 'Recon MIS';

    public function getSheetNames(array $fileDetails = [])
    {
        $fileName = strtolower($fileDetails['file_name']);

        $reconType = $this->getTypeName($fileName);

        if ($reconType === self::PAYMENT)
        {
            return [self::SHEET_NAME];
        }

        return [];
    }

    protected function getTypeName($fileName)
    {
        $type = null;

        if (strpos($fileName, self::PAYMENT_RECON_FILE_NAME) !== false)
        {
            $type = self::PAYMENT;
        }
        else if (strpos($fileName, self::REFUND_RECON_FILE_NAME) !== false)
        {
            $type = self::REFUND;
        }

        return $type;
    }

    protected function getFileName(array $extraDetails): string
    {
        return $extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::FILE_NAME];
    }
}

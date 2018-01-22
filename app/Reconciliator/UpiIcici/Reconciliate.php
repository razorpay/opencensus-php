<?php

namespace RZP\Reconciliator\UpiIcici;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const REFUND_RECON_FILE_NAME    = 'refund_report';
    const PAYMENT_RECON_FILE_NAME   = 'mis_report';
    const START_ROW                 = 2;

    public function getStartRow($fileDetails)
    {
        return self::START_ROW;
    }

    protected function getTypeName($fileName)
    {
        if (strpos(strtolower($fileName), self::PAYMENT_RECON_FILE_NAME) !== false)
        {
            return self::PAYMENT;
        }
        else if (strpos(strtolower($fileName), self::REFUND_RECON_FILE_NAME) !== false)
        {
            return self::REFUND;
        }

        return null;
    }

    protected function getFileName(array $extraDetails): string
    {
        return $extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::FILE_NAME];
    }
}
<?php

namespace RZP\Reconciliator\UpiJuspay;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const REFUND_RECON_FILE_NAME    = 'upi_refund_bajaj';
    const PAYMENT_RECON_FILE_NAME   = 'upi_sett_bajaj';

    protected function getTypeName($fileName)
    {
        $type = null;

        if (strpos($fileName, self::REFUND_RECON_FILE_NAME) !== false)
        {
            $type = self::REFUND;
        }
        else if (strpos($fileName, self::PAYMENT_RECON_FILE_NAME) !== false)
        {
            $type = self::PAYMENT;
        }

        return $type;
    }

    // In the base function: for excel recon files, we consider the sheet name if present as the file name,
    // here we do not want to consider the sheet name - we only need the file name hence overriding
    protected function getFileName(array $extraDetails): string
    {
        return $extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::FILE_NAME];
    }

    public function inExcludeList(array $fileDetails, array $inputDetails = [])
    {
        $fileName = strtolower($fileDetails['file_name']);

        if ((strpos($fileName, self::PAYMENT_RECON_FILE_NAME) !== false) or
            (strpos($fileName, self::REFUND_RECON_FILE_NAME) !== false))
        {
            return false;
        }

        return true;
    }

    public function getDelimiter()
    {
        return '|';
    }
}

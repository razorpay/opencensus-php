<?php

namespace RZP\Reconciliator\UpiIcici;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    // TODO: Check this
    const RECON_FILE_NAME    =    'upi';

    const START_ROW          =    2;

    public function inExcludeList(array $fileDetails)
    {
        $fileName = strtolower($fileDetails['file_name']);

        if (strpos($fileName, self::RECON_FILE_NAME) !== false)
        {
            return false;
        }

        return true;
    }

    public function getStartRow($fileDetails)
    {
        return self::START_ROW;
    }

    protected function getTypeName($fileName)
    {
        if (strpos(strtolower($fileName), self::RECON_FILE_NAME) !== false)
        {
            // TODO: Double check if this is only refund
            return self::REFUND;
        }

        return null;
    }

    protected function getFileName(array $extraDetails): string
    {
        return $extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::FILE_NAME];
    }
}
<?php

namespace RZP\Reconciliator\UpiIcici;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const RECON_FILE_NAME    = 'merchantreport';
    const START_ROW          = 2;

    public function getStartRow($fileDetails)
    {
        return self::START_ROW;
    }

    protected function getTypeName($fileName)
    {
        if (strpos(strtolower($fileName), self::RECON_FILE_NAME) !== false)
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
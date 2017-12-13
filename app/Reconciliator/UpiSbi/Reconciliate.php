<?php

namespace RZP\Reconciliator\UpiSbi;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    /**
     * @see https://drive.google.com/drive/u/0/folders/0B1kf6HOmx7JBTmMzTXgwQVRrNm8
     */

    /**
     * According to the POC from the gateway, the file will contain this substring
     */
    const TRANSACTION_REPORT = 'merchantreport';

    public function inExcludeList(array $fileDetails)
    {
        $fileName = strtolower($fileDetails['file_name']);

        if (strpos($fileName, self::TRANSACTION_REPORT) !== false)
        {
            return false;
        }

        return true;
    }

    protected function getFileName(array $extraDetails): string
    {
        return $extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::FILE_NAME];
    }

    protected function getTypeName($fileName)
    {
        if (strpos(strtolower($fileName), self::TRANSACTION_REPORT) !== false)
        {
            return self::PAYMENT;
        }

        return null;
    }
}

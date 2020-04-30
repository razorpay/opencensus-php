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
    const TRANSACTION_REPORT = 'transaction report';

    const REFUND_REPORT      = 'refundreport';

    protected function getFileName(array $extraDetails): string
    {
        return $extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::FILE_NAME];
    }

    /**
     * Receiving MIS files via SFTP, not keeping any constraint on file name.
     *
     * @param string $fileName
     * @return string|void
     */
    protected function getTypeName($fileName)
    {
        if (str_contains(strtolower($fileName), self::TRANSACTION_REPORT) !== false)
        {
            return self::PAYMENT;
        }
        elseif (str_contains(strtolower($fileName), self::REFUND_REPORT) !== false)
        {
            return self::REFUND;
        }

        return null;
    }
}

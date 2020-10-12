<?php

namespace RZP\Reconciliator\NetbankingIob;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const PID                   = 'Bank Code';
    const REF_NUM               = 'payment reference number';
    const AMOUNT                = 'Transaction Amount';
    const DTIME                 = 'date and time DD/MM/YYYY HH24:mm:ss';
    const STATUS                = 'Status of transaction';
    const BANK_REF_NO           = 'bank ref no.';

    const PAYMENT_SUCCESS = 'Y';

    protected static $column_headers = [
        self::PID,
        self::REF_NUM,
        self::AMOUNT,
        self::DTIME,
        self::STATUS,
        self::BANK_REF_NO
    ];

    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getColumnHeadersForType($type)
    {
        return self::$column_headers;
    }

    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP => 1,
            FileProcessor::LINES_FROM_BOTTOM => 0
        ];
    }

    public function getDelimiter()
    {
        return '~';
    }

    public function getDecryptedFile(array & $fileDetails)
    {
        $filePath = $fileDetails[FileProcessor::FILE_PATH];

        $data = file_get_contents($filePath);

        file_put_contents($filePath, $data);
    }
}

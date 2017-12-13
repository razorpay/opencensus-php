<?php

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    /**
     * @see https://drive.google.com/drive/u/0/folders/0B1kf6HOmx7JBTmMzTXgwQVRrNm8
     */

    const TRANSACTION_REPORT = 'MerchantReport';

    public function inExcludeList(array $fileDetails)
    {
        $fileName = strtolower($fileDetails['file_name']);

        if (strpos($fileName, self::TRANSACTION_REPORT) !== false)
        {
            return false;
        }

        return true;
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

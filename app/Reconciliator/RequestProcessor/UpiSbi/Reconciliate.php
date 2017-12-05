<?php

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
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
            // TODO: Check this - do we only get payments here?
            return self::PAYMENT;
        }

        return null;
    }
}
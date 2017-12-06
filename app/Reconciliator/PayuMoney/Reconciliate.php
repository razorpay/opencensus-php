<?php

namespace RZP\Reconciliator\PayuMoney;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    // TODO: Check this
    const FILE_NAME = 'payu';

    /**
     * We exclude all files that do not contain the string FILE_NAME above
     *
     * @param array $fileDetails
     * @return bool
     */
    public function inExcludeList(array $fileDetails)
    {
        $fileName = strtolower($fileDetails['file_name']);

        if (strpos($fileName, self::FILE_NAME) !== false)
        {
            return false;
        }

        return true;
    }

    protected function getTypeName($fileName)
    {
        if (strpos(strtolower($fileName), self::FILE_NAME) !== false)
        {
            // TODO: Double check this
            return self::PAYMENT;
        }

        return null;
    }
}
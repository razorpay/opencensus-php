<?php

namespace RZP\Reconciliator\BillDesk;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    const SUCCESS = 'success';

    /**
     * Figures out what kind of reconciliation is it
     * depending on the file name. It should be either
     * 'refund', 'payment' or 'combined'.
     * In case of excel sheets, the file name is the sheet name
     * and not the excel file name.
     *
     * @param string $fileName
     * @return null|string
     */
    protected function getTypeName($fileName)
    {
        if (strpos($fileName, self::SUCCESS) !== false)
        {
            $typeName = self::PAYMENT;
        }
        else if (strpos($fileName, self::REFUND) !== false)
        {
            $typeName = self::REFUND;
        }
        else
        {
            return null;
        }

        return $typeName;
    }

    /**
     * Some gateways send files which should not be used as part of the
     * reconciliation process. This decides whether a given file should
     * be part of the reconciliation or not.
     *
     * @param array $fileDetails
     * @return bool Whether the given file is present in the gateway's
     *              exclude list or not.
     */
    public function inExcludeList(array $fileDetails)
    {
        $fileName = strtolower($fileDetails['file_name']);

        if ((strpos($fileName, self::SUCCESS) === false) and
            (strpos($fileName, self::REFUND) === false))
        {
            return true;
        }

        return false;
    }
}

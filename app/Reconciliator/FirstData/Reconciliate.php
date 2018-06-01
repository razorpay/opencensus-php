<?php

namespace RZP\Reconciliator\FirstData;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    //
    // FirstData is sending a summary file with name razorpay_templet26_21may180_summary.xls.
    //
    const SUMMARY = 'summary';

    /**
     * Figures out what kind of reconciliation is it
     * depending on the file name. It should be either
     * 'refund', 'payment' or 'combined'.
     *
     * 'combined' is used when a file has both payments and refunds reports.
     * In case of excel sheets, the file name is the sheet name
     * and not the excel file name.
     *
     * @param string $fileName
     * @return string
     */
    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }

    /**
     * Some gateways send files which should not be used as part of the
     * reconciliation process. This decides whether a given file should
     * be part of the reconciliation or not.
     *
     * Skipping the summary file as doesn't contain transactions for recon.
     *
     * @param array $fileDetails
     * @return bool Whether the given file is present in the gateway's
     *              exclude list or not.
     */
    public function inExcludeList(array $fileDetails)
    {
        $fileName = strtolower($fileDetails['file_name']);

        if (str_contains($fileName, self::SUMMARY) === true)
        {
            return true;
        }

        return false;
    }
}

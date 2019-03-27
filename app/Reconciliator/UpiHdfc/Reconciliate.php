<?php

namespace RZP\Reconciliator\UpiHdfc;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

/**
 * @see https://drive.google.com/file/d/0B5y89FSSH1qSRHVuelBuYkdxcTFzMlFwcVpwNHVVMkZQZm1Z/view?usp=sharing
 */
class Reconciliate extends Base\Reconciliate
{
    //
    // Refund recon file is downloaded from dashboard by FinOps and manually uploaded
    // Sample file name : "transaction_report_04-feb-2019_15.19.092.xlsx"
    // Also, we return recon type as 'refund' if the file name has 'refund_report' string
    //
    const REFUND_FILE_NAMES = [
        'transaction_report',
        'refund_report',
    ];

    protected function getTypeName($fileName)
    {
        foreach (self::REFUND_FILE_NAMES as $name)
        {
            if (strpos($fileName, $name) !== false)
            {
                return self::REFUND;
            }
        }

        return self::PAYMENT;
    }

    //
    // Need to have this function here, as we want to decide
    // reconType based on the filename instead of sheet name.
    //
    protected function getFileName(array $extraDetails): string
    {
        return $extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::FILE_NAME];
    }
}

<?php

namespace RZP\Reconciliator\UpiAxis;

use Illuminate\Support\Str;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

/**
 * @see https://drive.google.com/open?id=1YtLo-dKf2YiQIdgfSFX8o0nmgGWQgz2c
 */
class Reconciliate extends Base\Reconciliate
{
    const REFUND_RECON_FILE_NAME    = [
        'upi_refund_razorpay',
        'refund_razorpay software pvt ltd',
    ];

    const PAYMENT_RECON_FILE_NAME   = [
        'razorpay software private limited',
        'razorpay software pvt ltd',
        'upi_sett_razorpay',
    ];

    const UNUSED_COLUMNS = [
        'surcharge',
        'tax',
        'debit_amount',
        'mdr_tax',
        'merchant_id',
        'unq_cust_id',
    ];

    protected function getTypeName($fileName)
    {
        $type = null;

        if (Str::contains($fileName, self::PAYMENT_RECON_FILE_NAME) !== false)
        {
            $type = self::PAYMENT;
        }
        if (Str::contains($fileName, self::REFUND_RECON_FILE_NAME) !== false)
        {
            $type = self::REFUND;
        }

        return $type;
    }

    // In the base function: for excel recon files, we consider the sheet name if present as the file name,
    // here we do not want to consider the sheet name - we only need the file name hence overriding
    protected function getFileName(array $extraDetails): string
    {
        return $extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::FILE_NAME];
    }

    //
    // In the new format file, we are getting 6 extra columns that
    // we do not use in recon, also these new appended columns will
    // create issue in output file (looker dashboard), so need
    // to unset these columns
    //
    protected function preProcessFileContents(array &$fileContents, string $reconciliationType)
    {
        foreach ($fileContents as &$row)
        {
            foreach (self::UNUSED_COLUMNS as $col)
            {
                if (isset($row[$col]) === true)
                {
                    unset($row[$col]);
                }
            }
        }
    }
}

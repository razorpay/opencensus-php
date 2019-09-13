<?php

namespace RZP\Reconciliator\UpiAxis;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

/**
 * @see https://drive.google.com/open?id=1YtLo-dKf2YiQIdgfSFX8o0nmgGWQgz2c
 */
class Reconciliate extends Base\Reconciliate
{
    const REFUND_RECON_FILE_NAME    = 'refund_razorpay software pvt ltd';
    const PAYMENT_RECON_FILE_NAME   = ['razorpay software private limited','razorpay software pvt ltd'];

    protected function getTypeName($fileName)
    {
        $type = null;

        if (str_contains($fileName, self::PAYMENT_RECON_FILE_NAME) !== false)
        {
            $type = self::PAYMENT;
        }

        return $type;
    }

    // In the base function: for excel recon files, we consider the sheet name if present as the file name,
    // here we do not want to consider the sheet name - we only need the file name hence overriding
    protected function getFileName(array $extraDetails): string
    {
        return $extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::FILE_NAME];
    }

    public function inExcludeList(array $fileDetails, array $inputDetails = [])
    {
        // Skipping refund recon files - because currently bank is sending payment_id
        // and payment_rrn in the refund recon file - so there is no way to uniquely identify the
        // refund transaction uniquely on our system.
        if (str_contains($fileDetails[FileProcessor::FILE_NAME], self::REFUND_RECON_FILE_NAME) !== false)
        {
            return true;
        }

        return false;
    }
}

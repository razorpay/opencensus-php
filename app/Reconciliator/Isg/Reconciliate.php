<?php

namespace RZP\Reconciliator\Isg;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const REFUND_RECON_FILE_NAME    = ['refund', 'Refund'];
    const PAYMENT_RECON_FILE_NAME   = ['Purchase','purchase'];

    protected function getTypeName($fileName)
    {
        $type = null;

        if (str_contains($fileName, self::PAYMENT_RECON_FILE_NAME) !== false)
        {
            $type = self::PAYMENT;
        }

        if (str_contains($fileName, self::REFUND_RECON_FILE_NAME) !== false)
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

}

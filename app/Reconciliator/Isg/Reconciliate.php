<?php

namespace RZP\Reconciliator\Isg;

use Illuminate\Support\Str;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const REFUND_RECON_FILE_NAME    = ['refund'];
    const PAYMENT_RECON_FILE_NAME   = ['purchase'];

    protected function getTypeName($fileName)
    {
        $type = null;

        if (Str::contains(strtolower($fileName), self::PAYMENT_RECON_FILE_NAME) !== false)
        {
            $type = self::PAYMENT;
        }

        if (Str::contains(strtolower($fileName), self::REFUND_RECON_FILE_NAME) !== false)
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

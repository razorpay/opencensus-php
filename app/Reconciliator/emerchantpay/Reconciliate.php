<?php

namespace RZP\Reconciliator\emerchantpay;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    const REFUND_RECON_FILE_NAME    = 'refund approved';
    const PAYMENT_RECON_FILE_NAME   = 'sale approved';

    /**
     * Figures out what kind of reconciliation is it
     * depending on the file name. In Case of Emerchantpay
     * It should be either 'refund', 'payment'.
     *
     * Since Emerchantpay sends us separate files for
     * payment and refunds we will use file name instead of
     * sheet name.
     * @param string $fileName
     * @return null|string
     */
    protected function getTypeName($fileName)
    {
        $type = null;

        if (strpos($fileName, self::PAYMENT_RECON_FILE_NAME) !== false)
        {
            $type = self::PAYMENT;
        }
        else if (strpos($fileName, self::REFUND_RECON_FILE_NAME) !== false)
        {
            $type = self::REFUND;
        }

        return $type;
    }

    protected function getFileName(array $extraDetails): string
    {
        return $extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::FILE_NAME];
    }
}

<?php

namespace RZP\Reconciliator\BajajFinserv;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;
use RZP\Gateway\Netbanking\Sbi\ReconFields\RefundReconFields;
use RZP\Gateway\Netbanking\Sbi\ReconFields\PaymentReconFields;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }

    public function getFileType(string $mimeType): string
    {
        return FileProcessor::EXCEL;
    }

    public function inExcludeList(array $fileDetails, array $inputDetails = [])
    {
        if (strpos($fileDetails[FileProcessor::EXTENSION], 'xlsx') === false)
        {
            return true;
        }

        return false;
    }
}

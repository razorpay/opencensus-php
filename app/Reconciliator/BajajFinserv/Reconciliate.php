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
        return self::PAYMENT;
    }

    public function getFileType(string $mimeType): string
    {
        return FileProcessor::EXCEL;
    }
}

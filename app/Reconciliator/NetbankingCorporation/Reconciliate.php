<?php

namespace RZP\Reconciliator\NetbankingCorporation;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;
use RZP\Gateway\Netbanking\Corporation\ReconcilationFields;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getColumnHeadersForType($type)
    {
        return ReconcilationFields::getPaymentColumnHeaders();
    }

    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 1,
            FileProcessor::LINES_FROM_BOTTOM => 0
        ];
    }

    public function getDelimiter()
    {
        return '|';
    }
}


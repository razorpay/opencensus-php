<?php

namespace RZP\Reconciliator\NetbankingVijaya;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;
use RZP\Gateway\Netbanking\Vijaya\ReconFields;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getColumnHeadersForType($type)
    {
        return ReconFields::getPaymentColumnHeaders();
    }

    public function getDelimiter()
    {
        return '^';
    }

    public function inExcludeList(array $fileDetails, array $inputDetails = [])
    {
        if (strpos($fileDetails[FileProcessor::EXTENSION], 'txt') !== false)
        {
            return false;
        }

        return true;
    }
}

<?php

namespace RZP\Reconciliator\NetbankingCorporation;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;
use RZP\Gateway\Netbanking\Corporation\ReconciliationFields;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getColumnHeadersForType($type)
    {
        return ReconciliationFields::getPaymentColumnHeaders();
    }

    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 1,
            FileProcessor::LINES_FROM_BOTTOM => 1
        ];
    }

    public function getDelimiter()
    {
        return '|';
    }

    public function inExcludeList(array $fileDetails, array $inputDetails = [])
    {
        if (preg_match('/[0-9]{5}_[0-9]{8}_olt/', $fileDetails['file_name']) === 1)
        {
            return false;
        }

        return true;
    }
}

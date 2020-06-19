<?php

namespace RZP\Reconciliator\EmandateAxis;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    public function getTypeName($fileName)
    {
        return self::EMANDATE_DEBIT;
    }

    public function inExcludeList(array $fileDetails, array $inputDetails = [])
    {
        if (strpos($fileDetails[FileProcessor::EXTENSION], 'xls') !== false)
        {
            return false;
        }

        return true;
    }
}

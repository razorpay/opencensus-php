<?php

namespace RZP\Reconciliator\CardlessEmiFlexMoney;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        if (strpos($fileName, self::REFUND) !== false)
        {
            $typeName = self::REFUND;
        }
        else
        {
            $typeName = self::PAYMENT;
        }

        return $typeName;
    }

    protected function getFileName(array $extraDetails): string
    {
        return $extraDetails[FileProcessor::FILE_DETAILS][FileProcessor::FILE_NAME];
    }
}

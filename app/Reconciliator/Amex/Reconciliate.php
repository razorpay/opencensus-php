<?php

namespace RZP\Reconciliator\Amex;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }

    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 20,
            FileProcessor::LINES_FROM_BOTTOM => 0
        ];
    }
}

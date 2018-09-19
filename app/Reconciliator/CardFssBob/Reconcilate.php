<?php

namespace RZP\Reconciliator\CardFssBob;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 3,
            FileProcessor::LINES_FROM_BOTTOM => 0
        ];
    }

    /**
     *
     * @param string $fileName
     * @return string
     */
    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }
}

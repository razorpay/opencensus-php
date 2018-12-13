<?php

namespace RZP\Reconciliator\NetbankingEquitas;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getDelimiter()
    {
        return ',';
    }

    public function getFileType(string $mimeType): string
    {
        return FileProcessor::CSV;
    }
}

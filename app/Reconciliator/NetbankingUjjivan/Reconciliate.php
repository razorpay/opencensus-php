<?php

namespace RZP\Reconciliator\NetbankingUjjivan;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getFileType(string $mimeType): string
    {
        return FileProcessor::CSV;
    }

    public function getColumnHeadersForType($type)
    {
        return Constants::PAYMENT_COLUMN_HEADERS;
    }

    public function getDelimiter()
    {
        return '^';
    }

}

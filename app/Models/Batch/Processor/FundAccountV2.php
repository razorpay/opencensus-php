<?php

namespace RZP\Models\Batch\Processor;

class FundAccountV2 extends Base
{
    protected function parseFirstRowAndGetHeadings(array & $rows, string $delimiter)
    {
        $headers = str_getcsv(current($rows), $delimiter);
        return $headers;
    }
}

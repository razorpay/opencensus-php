<?php

namespace RZP\Models\Batch\Processor;

use RZP\Trace\TraceCode;

class FundAccount extends Base
{
    protected function parseFirstRowAndGetHeadings(array & $rows, string $delimiter)
    {
        $headers = str_getcsv(current($rows), $delimiter);

        return $headers;
    }
}

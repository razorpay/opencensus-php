<?php

namespace RZP\Models\Batch\Processor\Util;

use RZP\Exception;
use RZP\Models\Batch\Header;

class PartnersCommonUtil
{
    public function validateHeaders(array $rows, string $delimiter, string $fileType, string $batchType)
    {
        $headings = Header::getHeadersForFileTypeAndBatchType($fileType, $batchType);

        $firstRow = str_getcsv(current($rows), $delimiter);

        if (Header::areTwoHeadersSame($headings, $firstRow) === false)
        {
            $msg = 'Uploaded file has invalid headers. Acceptable headers are [%s]';

            $msg = sprintf($msg, implode(', ', $headings));

            throw new Exception\BadRequestValidationFailureException($msg);
        }
    }

}
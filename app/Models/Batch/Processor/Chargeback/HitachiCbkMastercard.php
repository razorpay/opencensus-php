<?php

namespace RZP\Models\Batch\Processor\Chargeback;


use RZP\Exception;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Processor\Base;

class HitachiCbkMastercard  extends Base
{
    protected function validateHeaders(array $rows, $delimiter)
    {
        $headings = $this->getHeadings();
        $firstRow = str_getcsv(current($rows), $delimiter);

        if (Header::areTwoHeadersSame($headings, $firstRow) === false)
        {
            $msg = 'Uploaded file has invalid headers. Acceptable headers are [%s]';

            $msg = sprintf($msg, implode(', ',$headings));

            throw new Exception\BadRequestValidationFailureException($msg);
        }
    }

    protected function parseFirstRowAndGetHeadings(array & $rows, string $delimiter)
    {
        $this->validateHeaders($rows, $delimiter);

        return parent::parseFirstRowAndGetHeadings($rows, $delimiter);
    }

    public function shouldSendToBatchService(): bool
    {
        return true;
    }
}
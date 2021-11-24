<?php

namespace RZP\Models\Batch\Processor\Util;

use RZP\Exception;
use RZP\Models\Batch\Type;
use RZP\Models\Batch\Header;

class PartnersCommonUtil
{
    public function validateHeaders(array $rows, string $delimiter, string $fileType, string $batchType)
    {
        $headings = Header::getHeadersForFileTypeAndBatchType($fileType, $batchType);

        $firstRow = str_getcsv(current($rows), $delimiter);

        // TODO: update the batch header once the FE changes for the same are deployed on prod.
        if (($batchType === Type::PARTNER_SUBMERCHANT_INVITE) and
            ((in_array(Header::CONTACT_MOBILE, $firstRow, true) === true)))
        {
            $headings[] = Header::CONTACT_MOBILE;
        }

        if (Header::areTwoHeadersSame($headings, $firstRow) === false)
        {
            $msg = 'Uploaded file has invalid headers. Acceptable headers are [%s]';

            $msg = sprintf($msg, implode(', ', $headings));

            throw new Exception\BadRequestValidationFailureException($msg);
        }
    }
}

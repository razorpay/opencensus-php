<?php

namespace RZP\Models\BankingAccountStatement\Generator;

use RZP\Exception\BadRequestValidationFailureException;

class SupportedFormats
{
    const PDF = 'pdf';

    const XLSX = 'xlsx';

    const SUPPORTED_FORMAT_MAP = ['rbl' => [self::PDF, self::XLSX]];

    public static function validate($channel, $format)
    {
        if(!array_key_exists($channel, self::SUPPORTED_FORMAT_MAP))
        {
            $message = "{$channel} is not a valid channel";

            throw new BadRequestValidationFailureException($message);
        }

        if(!in_array($format, self::SUPPORTED_FORMAT_MAP[$channel]))
        {
            $message = "{$channel} does not support {$format} type of Account Statements";

            throw new BadRequestValidationFailureException($message);
        }
    }

}

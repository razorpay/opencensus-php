<?php

namespace RZP\Models\BankingAccountStatement\Generator;

use RZP\Models\BankingAccountStatement\Channel;
use RZP\Exception\BadRequestValidationFailureException;

class SupportedFormats
{
    const PDF = 'pdf';

    const XLSX = 'xlsx';

    const CHANNEL_FORMAT_MAP = [
        Channel::RBL => [
            self::PDF,
            self::XLSX,
        ]
    ];

    const ALL_VALID_FORMATS = [self::PDF, self::XLSX];

    public static function validate($channel, $format)
    {
        if (array_key_exists($channel, self::CHANNEL_FORMAT_MAP) === false)
        {
            $message = "{$channel} is not a valid channel";

            throw new BadRequestValidationFailureException($message);
        }

        if (in_array($format, self::CHANNEL_FORMAT_MAP[$channel], true) === false)
        {
            $message = "{$channel} does not support {$format} type of Account Statements";

            throw new BadRequestValidationFailureException($message);
        }
    }
}

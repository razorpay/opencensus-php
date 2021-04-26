<?php

namespace RZP\Models\BankingAccountStatement\Generator;

use RZP\Models\FileStore\Format;
use RZP\Models\BankingAccountStatement\Channel;
use RZP\Exception\BadRequestValidationFailureException;

class SupportedFormats
{
    const PDF = Format::PDF;

    const XLSX = Format::XLSX;

    const CHANNEL_FORMAT_MAP = [
        Channel::RBL => [
            self::PDF,
            self::XLSX,
        ],
        Channel::ICICI => [
            self::PDF,
            self::XLSX,
        ]
    ];

    const ALL_VALID_FORMATS = [self::PDF, self::XLSX];

    public static function validateChannelFormat($channel, $format)
    {
        if (array_key_exists($channel, self::CHANNEL_FORMAT_MAP) === false)
        {
            $message = "{$channel} is not a valid channel";

            throw new BadRequestValidationFailureException($message,
                                                           null,
                                                           [
                                                               'channel' => $channel
                                                           ]);
        }

        if (in_array($format, self::CHANNEL_FORMAT_MAP[$channel], true) === false)
        {
            $message = "{$channel} does not support {$format} type of Account Statements";

            throw new BadRequestValidationFailureException($message,
                                                           null,
                                                           [
                                                               'channel' => $channel,
                                                               'format' => $format,
                                                           ]);
        }
    }

    public static function validateFormat($format)
    {
        if (in_array($format, SupportedFormats::ALL_VALID_FORMATS, true) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid format: ' . $format,
                                                           null,
                                                           [
                                                               'format' => $format
                                                           ]);
        }
    }
}

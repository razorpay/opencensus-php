<?php

namespace RZP\Models\BankingAccountStatement\Details;

use RZP\Exception;

class Channel
{
    const RBL     = 'rbl';
    const ICICI   = 'icici';
    const AXIS    = 'axis';
    const YESBANK = 'yesbank';
    const IDFC     = 'idfc';

    public static function getChannels()
    {
        return [
            self::RBL,
            self::ICICI,
            self::AXIS,
            self::YESBANK,
            self::IDFC,
        ];
    }

    public static function getChannelsWithNullPaginationKey()
    {
        return [
            self::ICICI,
            self::AXIS,
            self::YESBANK,
            self::IDFC,
        ];
    }

    public static function validate(string $channel = null)
    {
        if (in_array($channel, self::getChannels(), true) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid channel name: ' . $channel);
        }
    }
}

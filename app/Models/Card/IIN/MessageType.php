<?php

namespace RZP\Models\Card\IIN;

class MessageType
{
    const SMS = 'SMS';

    const DMS = 'DMS';

    const TYPE_LIST = [
        self::SMS,
        self::DMS,
    ];

    public static function isValid(string $type):bool
    {
        return (in_array($type, self::TYPE_LIST, true) === true);
    }
}

<?php

namespace RZP\Models\SubVirtualAccount;

class Type
{
    const SUB_DIRECT_ACCOUNT = 'sub_direct_account';
    const DEFAULT            = 'default';

    public static function getTypes()
    {
        return [
            self::SUB_DIRECT_ACCOUNT,
            self::DEFAULT,
        ];
    }

    public static function isValid($type)
    {
        if (in_array($type, self::getTypes()) === true)
        {
            return true;
        }

        return false;
    }
}

<?php

namespace RZP\Models\FundAccount\Validation\Processor;

use RZP\Models\FundAccount\Entity;
use RZP\Exception\BadRequestException;

class Type
{
    protected static $validTypes = [
        Entity::BANK_ACCOUNT,
        Entity::VPA
    ];

    protected static function isValid(string $type)
    {
        return in_array($type, self::$validTypes, true);
    }

    /**
     * @param string $type
     *
     * @throws BadRequestException
     */
    public static function validate(string $type)
    {
        if (in_array($type, self::$validTypes, true) === false)
        {
            throw new BadRequestException("Invalid fund account type: $type");
        }
    }
}

<?php

namespace RZP\Models\FundAccount;

use RZP\Constants\Entity;
use RZP\Exception\BadRequestValidationFailureException;

class Type
{
    const VPA            = Entity::VPA;
    const BANK_ACCOUNT   = Entity::BANK_ACCOUNT;
    const CARD           = Entity::CARD;
    const WALLET_ACCOUNT = Entity::WALLET_ACCOUNT;

    // Public facing account type for wallet_accounts
    const WALLET         = Entity::WALLET;

    public static function isValid(string $type): bool
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }

    public static function validateType(string $type)
    {
        if (self::isValid($type) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid account type: ' . $type);
        }
    }
}

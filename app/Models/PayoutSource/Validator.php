<?php

namespace RZP\Models\PayoutSource;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Validator extends Base\Validator
{
    const PAYOUT_SOURCE_CREATE = 'payout_source_create';

    protected static $createRules = [
        Entity::SOURCE_ID   => 'required|string',
        Entity::SOURCE_TYPE => 'required|string',
        Entity::PRIORITY    => 'required|integer|min:1'
    ];

    protected static $payoutSourceCreateRules = [
        Entity::SOURCE_ID   => 'required|string',
        Entity::SOURCE_TYPE => 'required|string',
        Entity::PRIORITY    => 'required|integer|min:1'
    ];

    public static function validateSourceType(string $sourceType)
    {
        if (self::isValid($sourceType) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_INVALID_SOURCE_TYPE,
                null,
                [
                    'source_type' => $sourceType,
                ]);
        }
    }

    protected static function isValid(string $sourceType): bool
    {
        return (in_array($sourceType, Entity::$validSourceTypes, true) === true);
    }
}


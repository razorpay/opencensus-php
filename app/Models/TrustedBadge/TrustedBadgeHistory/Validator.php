<?php

namespace RZP\Models\TrustedBadge\TrustedBadgeHistory;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID     => 'required|filled|string',
        Entity::STATUS          => 'required|filled|custom',
        Entity::MERCHANT_STATUS => 'sometimes|filled|custom',
    ];

    protected static $validStatuses = [
        Entity::ELIGIBLE,
        Entity::INELIGIBLE,
        Entity::BLACKLIST,
        Entity::WHITELIST,
    ];

    protected static $validMerchantStatuses = [
        Entity::OPTIN,
        Entity::OPTOUT,
        Entity::WAITLIST,
    ];

    protected function validateStatus($attribute, $status): void
    {
        if (!in_array($status, self::$validStatuses, true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_TRUSTED_BADGE_STATUS, null, [$status]);
        }
    }

    protected function validateMerchantStatus($attribute, $merchantStatus): void
    {
        if (!in_array($merchantStatus, self::$validMerchantStatuses, true ))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_TRUSTED_BADGE_MERCHANT_STATUS, null, [$merchantStatus]);
        }
    }
}

<?php

namespace RZP\Models\TrustedBadge;

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

    protected static $validateStatusRules = [
        'merchant_ids'          => 'required|array',
        'status'                => 'required|string|in:blacklist,whitelist',
        'action'                => 'required|string|in:add,remove',
    ];

    protected static $validateRedirectRules = [
        'cta'           => 'required|filled|string|in:dashboard,feedback',
        'mailer'        => 'required|filled|string|in:welcome,optout,optin_request',
        'merchant_id'   => 'required|filled|string'
    ];

    protected function validateStatus($attribute, $status): void
    {
        if (!in_array($status, self::$validStatuses, true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_TRUSTED_BADGE_STATUS, null, [$status]);
        }
    }

    public function validateMerchantStatus($attribute, $merchantStatus): void
    {
        if (!in_array($merchantStatus, self::$validMerchantStatuses, true ))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_TRUSTED_BADGE_MERCHANT_STATUS, null, [$merchantStatus]);
        }
    }
}

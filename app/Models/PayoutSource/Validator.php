<?php

namespace RZP\Models\PayoutSource;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Validator extends Base\Validator
{
    const PAYOUT_SOURCE_CREATE = 'payout_source_create';

    const PAYOUT_LINKS    = 'payout_links';
    const VENDOR_PAYMENTS = 'vendor_payments';
    const TAX_PAYMENTS    = 'tax_payments';

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

    public static $validSourceTypes = [
        self::PAYOUT_LINKS,
        self::VENDOR_PAYMENTS,
        self::TAX_PAYMENTS,
    ];

    public static $validAppNameAndSourceTypeMapping = [
        self::VENDOR_PAYMENTS => [self::VENDOR_PAYMENTS, self::TAX_PAYMENTS],
        self::PAYOUT_LINKS    => [self::PAYOUT_LINKS],
    ];

    public function isValidSourceType(string $sourceType) : bool
    {
        return (in_array($sourceType, self::$validSourceTypes, true) === true);
    }

    public function isValidSourceTypeForApp(string $sourceType, string $appName) : bool
    {
        return (in_array($sourceType, self::$validAppNameAndSourceTypeMapping[$appName], true) === true);
    }

    public function isSourceTypeMappingPresentForApp(string $appName) : bool
    {
        return (in_array($appName, array_keys(self::$validAppNameAndSourceTypeMapping), true) === true);
    }
}


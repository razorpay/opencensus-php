<?php

namespace RZP\Models\Gateway\Downtime\Webhook\Constants;

use RZP\Models\Gateway\Downtime\Entity;

class DowntimeService
{
    const ACTION    = 'action';
    const PLATFORM  = 'PLFT';
    const MERCHANT  = 'MERCHANT';
    const TYPE      = 'type';
    const RULE_ID   = 'ruleId';
    const STRATEGY  = 'strategy';
    const EVENT_TIME = 'eventTime';
    const MERCHANT_ID = 'merchantId';
    const CARD_TYPE = 'cardType';
    const FLOW = 'flow';

    const UNIQUE_KEYS = [
        Entity::GATEWAY,
        Entity::ISSUER,
        Entity::METHOD,
        Entity::SOURCE,
        Entity::NETWORK,
        Entity::VPA_HANDLE,
        Entity::MERCHANT_ID,
    ];

    const PLATFORM_DOWNTIME_UNIQUE_KEYS = [
        Entity::GATEWAY,
        Entity::ISSUER,
        Entity::METHOD,
        Entity::SOURCE,
        Entity::NETWORK,
        Entity::VPA_HANDLE
    ];

    const UNIQUE_KEYS_WITH_CARD_TYPE = [
        Entity::GATEWAY,
        Entity::ISSUER,
        Entity::METHOD,
        Entity::SOURCE,
        Entity::NETWORK,
        Entity::VPA_HANDLE,
        Entity::MERCHANT_ID,
        Entity::CARD_TYPE,
    ];

    public static function getUniqueKeys()
    {
        return self::UNIQUE_KEYS;
    }

    public static function getUniqueKeysWithCardType()
    {
        return self::UNIQUE_KEYS_WITH_CARD_TYPE;
    }

    public static function getUniqueKeysForPlatformDowntimes()
    {
        return self::PLATFORM_DOWNTIME_UNIQUE_KEYS;
    }
}

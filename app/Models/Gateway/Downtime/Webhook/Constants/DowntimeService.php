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

    const UNIQUE_KEYS_FOR_TURBO = [
        Entity::METHOD,
        Entity::SOURCE,
        Entity::CARD_TYPE,
        Entity::MERCHANT_ID,
    ];

    const NETWORK_MAP_FOR_UPI_TURBO = [
        'BANK_ACC'     => 'bank_account',
        'bank_account' => 'BANK_ACC',
        'CR_CARD'      => 'credit_card',
        'credit_card'  => 'CR_CARD',
        'WALLET'       => 'wallet',
        'wallet'       => 'WALLET',
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

    public static function getUniqueKeysForTurbo()
    {
        return self::UNIQUE_KEYS_FOR_TURBO;
    }

    public static function getNetworkForTurbo($newtork)
    {
        if (array_key_exists($newtork, self::NETWORK_MAP_FOR_UPI_TURBO) === false)
        {
            return null;
        }

        return self::NETWORK_MAP_FOR_UPI_TURBO[$newtork];
    }

}

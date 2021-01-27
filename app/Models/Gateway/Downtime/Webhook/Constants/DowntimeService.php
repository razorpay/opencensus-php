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

    const UNIQUE_KEYS = [
        Entity::GATEWAY,
        Entity::ISSUER,
        Entity::METHOD,
        Entity::SOURCE,
        Entity::NETWORK,
        Entity::VPA_HANDLE,
        Entity::MERCHANT_ID,
    ];
}

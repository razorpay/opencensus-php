<?php

namespace RZP\Models\EntityOrigin;

class Constants
{
    // Origin types
    const MERCHANT                  = 'merchant';
    const APPLICATION               = 'application';
    const SUBSCRIPTION              = 'subscription';
    const PAYMENT_LINK              = 'payment_link';
    // For  Route Marketplace transfers.
    const MARKETPLACE_APPLICATION   = 'marketplace_app';

    // For transaction isolation
    const APPLICATION_ID            = 'application_id';

    const ENTITY_ORIGIN_REDIS_KEY   = 'entity_origin_redis_key_';
    const OPENWALLET                = 'openwallet';

    const ENTITY_ORIGIN_CACHE_TTL_IN_DAYS = 2;
}

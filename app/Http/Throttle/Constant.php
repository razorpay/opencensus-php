<?php

namespace RZP\Http\Throttle;

final class Constant
{
    /**
     * Redis key for global settings
     */
    const GLOBAL_SETTINGS_KEY            = 't';

    /**
     * Redis key prefix for route level settings
     */
    const ROUTE_SETTINGS_KEY_REFIX       = 't:r:';

    /**
     * Redis key prefix for identifier level settings
     */
    const IDENTIFIER_SETTINGS_KEY_PREFIX = 't:i:';

    /**
     * Key id -> Mid is kept in cache for faster access
     */
    const JUST_ANOTHER_PREFIX            = 't:kmp:';

    const LEAK_RATE_VALUE                = 'lrv';
    const LEAK_RATE_DURATION             = 'lrd';
    const MAX_BUCKET_SIZE                = 'mbs';
}

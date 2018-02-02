<?php

namespace RZP\Http\Throttle;

final class Constant
{
    const GLOBAL_SETTINGS_KEY      = 't';     // Redis key for global settings
    const ROUTE_SETTINGS_KEY_REFIX = 't:r:';  // Redis key prefix for route level settings
    const ID_SETTINGS_KEY_PREFIX   = 't:i:';  // Redis key prefix for identifier level settings
    const KEYID_MID_KEY_PREFIX     = 't:km:'; // Key id -> Mid is kept in cache for faster access
    const LEAK_RATE_VALUE          = 'lrv';
    const LEAK_RATE_DURATION       = 'lrd';
    const MAX_BUCKET_SIZE          = 'mbs';
}

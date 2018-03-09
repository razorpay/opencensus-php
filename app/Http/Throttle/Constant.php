<?php

namespace RZP\Http\Throttle;

final class Constant
{
    const NUM_SECONDS_IN_WEEK      = 604800;

    /**
     * Key to hold global level settings
     */
    const GLOBAL                   = 'global';

    /**
     * Key to hold id level settings
     */
    const ID_LEVEL                 = 'id_level';

    /**
     * Redis key for global settings
     */
    const GLOBAL_SETTINGS_KEY      = 't';

    /**
     * Redis key prefix for identifier level settings
     */
    const ID_SETTINGS_KEY_PREFIX   = 't:i:';

    /**
     * Key id -> Mid is kept in cache for faster access
     */
    const KEYID_MID_KEY_PREFIX     = 't:km:';

    const LEAK_RATE_VALUE          = 'lrv';
    const LEAK_RATE_DURATION       = 'lrd';
    const MAX_BUCKET_SIZE          = 'mbs';
}

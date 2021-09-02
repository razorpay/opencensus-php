<?php

namespace RZP\Models\Base\QueryCache;

class Constants
{
    const VERSION                       = 'version';
    const TTL                           = 'ttl';
    const DEFAULT_QUERY_CACHE_TTL_MINS  = 5;
    const DEFAULT_QUERY_CACHE_VERSION   = 'v1';
    const QUERY_CACHE_PREFIX            = 'rememberable';
    const UPI_POLLING_CACHE_PREFIX      = '{upi.polling}';

    const CACHE_HITS      = 'cache_hits';
    const CACHE_MISSES    = 'cache_misses';
    const CACHE_WRITES    = 'cache_writes';
    const CACHE_FLUSHES   = 'cache_flushes';
}

<?php

namespace RZP\Base\Cache;

use Illuminate\Contracts\Cache\Store;
use Illuminate\Cache\CacheManager as BaseCacheManager;

class CacheManager extends BaseCacheManager
{
    public function repository(Store $store)
    {
        return tap(new Repository($store), function ($repository) {
            $this->setEventDispatcher($repository);
        });
    }
}

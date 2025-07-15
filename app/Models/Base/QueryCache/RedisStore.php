<?php

namespace RZP\Models\Base\QueryCache;

use Illuminate\Cache\RedisStore as LaravelRedisStore;
use Illuminate\Cache\RedisTaggedCache;

class RedisStore extends LaravelRedisStore
{
    /**
     * Begin executing a new tags operation using our custom RedisTagSet.
     *
     * @param  array|mixed  $names
     * @return \Illuminate\Cache\RedisTaggedCache
     */
    public function tags($names)
    {
        return new RedisTaggedCache(
            $this, 
            new RedisTagSet($this, is_array($names) ? $names : func_get_args())
        );
    }
} 
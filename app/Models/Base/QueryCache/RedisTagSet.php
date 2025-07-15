<?php

namespace RZP\Models\Base\QueryCache;

use Illuminate\Cache\RedisTagSet as LaravelRedisTagSet;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class RedisTagSet extends LaravelRedisTagSet
{
    /**
     * Add a reference entry to the tag set with TTL.
     *
     * @param  string  $key
     * @param  int|null  $ttl
     * @param  mixed  $updateWhen
     * @return void
     */
    public function addEntry(string $key, ?int $ttl = null, $updateWhen = null): void
    {
        // Call parent to add the entry normally
        parent::addEntry($key, $ttl, $updateWhen);

        // Only set TTL if a valid TTL was provided
        if ($ttl !== null && $ttl > 0)
        {
            try {
                // Calculate tag TTL as original TTL plus 30 mins buffer (1800 seconds)
                $tagsTtl = $ttl + 1800;

                // Get Redis connection
                $connection = $this->store->connection();

                $prefix = $this->store->getPrefix();

                // Set TTL on all tag keys for this tag set
                foreach ($this->tagIds() as $tagKey)
                {
                    $fullTagKey = $prefix . $tagKey;

                    $result = $connection->expire($fullTagKey, $tagsTtl);

//                    app('trace')->info(TraceCode::REDIS_TAG_SET_EXPIRE_RESULT, [
//                        'tag_key' => $tagKey,
//                        'full_tag_key' => $fullTagKey,
//                        'ttl' => $tagsTtl,
//                        'result' => $result
//                    ]);
                }
            }
            catch (\Throwable $e)
            {
                app('trace')->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::REDIS_TAG_SET_TTL_ERROR
                );
            }
        }
        else
        {
            app('trace')->info(TraceCode::REDIS_TAG_SET_SKIP_TTL, [
                'ttl' => $ttl,
                'key' => $key
            ]);
        }
    }
}

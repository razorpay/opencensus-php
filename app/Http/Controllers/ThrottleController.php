<?php

namespace RZP\Http\Controllers;

use Request;
use Illuminate\Support\Facades\Redis;

use ApiResponse;
use RZP\Models\Key;
use RZP\Trace\TraceCode;
use RZP\Services\Throttle;
use RZP\Base\RuntimeManager;
use RZP\Http\Throttle\Constant;
use RZP\Http\Throttle\Constant as K;

class ThrottleController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = Throttle\Service::class;

    /**
     * Refer Key\Core's writeCache() method.
     */
    public function bootstrapKeyCache()
    {
        RuntimeManager::setMemoryLimit('256M');
        RuntimeManager::setTimeLimit(1000);

        $totalIteration = 0;
        $failedIteration = 0;
        $chunkSize = 1000;

        Key\Entity::select('id', 'merchant_id')
            ->orderBy(Key\Entity::ID)
            ->chunk($chunkSize, function($keys) use (&$totalIteration, &$failedIteration) {
                ++$totalIteration;
                $args = [];
                foreach ($keys as $key)
                {
                    $args[] = Constant::KEYID_MID_KEY_PREFIX . $key->getPublicId();
                    $args[] = $key->getMerchantId();
                }

                try
                {
                    Redis::connection('throttle')->client()->mset(...$args);
                }
                catch (\Throwable $e)
                {
                    ++$failedIteration;
                    $this->trace->traceException($e, null, null, compact($args));
                }
            });

        $this->trace->info(TraceCode::BOOTSTRAP_KEY_CACHE_SUMMARY, compact('totalIteration', 'failedIteration'));

        return ApiResponse::json([]);
    }

    public function createConfig()
    {
        $input = Request::all();

        $response = $this->service()->createConfig($input);

        return ApiResponse::json($response);
    }

    public function migrateThrottleKeysFromRedisLabs()
    {
        RuntimeManager::setMemoryLimit('256M');
        RuntimeManager::setTimeLimit(1000);

        $redisLabs = Redis::connection()->client();
        $throttleEC = Redis::connection('throttle')->client();
        $totalIteration = 0;
        $failedKeys = 0;

        $keysConfigMerchant = $redisLabs->keys("throttle:merchant:*");
        $keysConfigRoute = $redisLabs->keys("throttle:route:*");
        $oldKeys= $redisLabs->keys("throttle:t:*");

        $allKeys = array_merge($keysConfigMerchant, $keysConfigRoute);
        $allKeys = array_merge($allKeys, $oldKeys);

        $this->trace->info(TraceCode::THROTTLE_REDIS_KEY_MIGRATE, [
            'total_keys'            => count($allKeys),
        ]);

        $chunkedKeys = array_chunk($allKeys,1000);

        foreach ($chunkedKeys as $keys)
        {
            ++$totalIteration;
            foreach ($keys as $key)
            {
                $value = $redisLabs->hgetall($key);
                try
                {
                    $throttleEC->hmset($key, $value);
                }
                catch (\Throwable $e)
                {
                    ++$failedKeys;
                    $this->trace->traceException($e, null, null, compact("key"));
                }

            }
        }

        $this->trace->info(TraceCode::THROTTLE_REDIS_KEY_MIGRATE, compact('totalIteration', '$failedKeys'));

        return ApiResponse::json([]);

    }

    public function deleteConfig()
    {
        $input = Request::all();

        $response = $this->service()->deleteConfig($input);

        return ApiResponse::json($response);
    }

    public function fetchConfig()
    {
        $input = Request::all();

        $response = $this->service()->fetchConfig($input);

        return ApiResponse::json($response);
    }
}

<?php

namespace RZP\Http\Controllers;

use Request;
use Illuminate\Support\Facades\Redis;

use ApiResponse;
use RZP\Models\Admin\ConfigKey;
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
                foreach ($keys as $key)
                {
                    ++$totalIteration;
                    $mkey = Constant::KEYID_MID_KEY_PREFIX.$key->getPublicId();
                    $mvalue = $key->getMerchantId();
                    try
                    {
                        Redis::connection('throttle')->client()->set($mkey, $mvalue);
                    }
                    catch (\Throwable $e)
                    {
                        ++$failedIteration;
                        $this->trace->traceException($e, null, null, $key);
                    }

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

    protected function getRedisKey($input): string
    {
        if (empty($input['merchant_id']) === true)
        {

            return K::THROTTLE_PREFIX . K::CONFIGURATION_TYPE_ROUTE . ':' . $input['route'];
        }

        return K::THROTTLE_PREFIX . K::CONFIGURATION_TYPE_MERCHANT . ':' . $input['merchant_id'];
    }


    public function migrateThrottleKeysFromRedisLabs()
    {
        $redisLabs = Redis::connection();
        $throttleEC = Redis::connection('throttle');
        $configRedis = Redis::connection('query_cache_redis');

        // Merchant
        $merchants = $redisLabs->smembers(K::CUSTOM_MERCHANT_SET);

        $totalIteration = 0;
        $failedIteration = 0;

        foreach ($merchants as $merchantId)
        {
            ++$totalIteration;
            $key = $this->getRedisKey(['merchant_id' => $merchantId]);

            try
            {
                $rules = $redisLabs->hgetall($key);
                $key = "{".$key."}";
                $throttleEC->hmset($key, $rules);
            }
            catch (\Throwable $e)
            {
                ++$failedIteration;
                $this->trace->traceException($e, null, null, compact("key"));
            }

        }

        $this->trace->info(TraceCode::THROTTLE_REDIS_KEY_MIGRATE, compact('totalIteration', 'failedIteration'));

        //Routes
        $totalIteration = 0;
        $failedIteration = 0;

        $routes    = $redisLabs->smembers(K::CUSTOM_ROUTE_SET);

        foreach ($routes as $route)
        {
            ++$totalIteration;
            $key = $this->getRedisKey(['route' => $route]);

            try
            {
                $rules = $redisLabs->hgetall($key);
                $key = "{".$key."}";
                $throttleEC->hmset($key, $rules);
            }
            catch (\Throwable $e)
            {
                ++$failedIteration;
                $this->trace->traceException($e, null, null, compact("key"));
            }

        }

        $this->trace->info(TraceCode::THROTTLE_REDIS_KEY_MIGRATE, compact('totalIteration', 'failedIteration'));


        // Config
        $totalIteration = 0;
        $failedIteration = 0;

        foreach (ConfigKey::PUBLIC_KEYS as $key)
        {
            ++$totalIteration;
            try
            {
                $value = $redisLabs->get($key);
                $configRedis->set($key, $value);
            }
            catch (\Throwable $e)
            {
                ++$failedIteration;
                $this->trace->traceException($e, null, null, compact("key"));
            }

        }

        $this->trace->info(TraceCode::THROTTLE_REDIS_KEY_MIGRATE, compact('totalIteration', 'failedIteration'));

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

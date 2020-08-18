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
            ->chunk($chunkSize, function ($keys) use (&$totalIteration, &$failedIteration)
            {
                foreach ($keys as $key)
                {
                    ++$totalIteration;
                    $mkey = Constant::KEYID_MID_KEY_PREFIX . $key->getPublicId();
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

    protected function getRedisOldKey($input): string
    {
        if (empty($input['merchant_id']) === true) {

            return K::THROTTLE_PREFIX . K::CONFIGURATION_TYPE_ROUTE . ':' . $input['route'];
        }

        return K::THROTTLE_PREFIX . K::CONFIGURATION_TYPE_MERCHANT . ':' . $input['merchant_id'];
    }

    protected function getRedisNewKey($input): string
    {
        if (empty($input['merchant_id']) === true) {

            return K::THROTTLE_PREFIX . '{' . K::CONFIGURATION_TYPE_ROUTE . '}:' . $input['route'];
        }

        return K::THROTTLE_PREFIX . '{' . K::CONFIGURATION_TYPE_MERCHANT . '}:' . $input['merchant_id'];
    }


    public function migrateThrottleKeysFromRedisLabs()
    {
        $redisLabs = Redis::connection();

        $throttleEC = Redis::connection('throttle');

        // Merchant
        $merchants = $redisLabs->smembers('throttle:custom:merchant');
        $totalIteration = 0;
        $failedIteration = 0;

        foreach ($merchants as $merchantId)
        {
            ++$totalIteration;
            $key = $this->getRedisOldKey(['merchant_id' => $merchantId]);

            try
            {
                $rules = $redisLabs->hgetall($key);
                $key = $this->getRedisNewKey(['merchant_id' => $merchantId]);

                $throttleEC->hmset($key, $rules);

                $throttleEC->sadd(K::CUSTOM_MERCHANT_SET, $merchantId);
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

        $routes = $redisLabs->smembers('throttle:custom:route');

        foreach ($routes as $route)
        {
            ++$totalIteration;
            $key = $this->getRedisOldKey(['route' => $route]);

            try
            {
                $rules = $redisLabs->hgetall($key);
                $key = $this->getRedisNewKey(['route' => $route]);
                $throttleEC->hmset($key, $rules);

                $throttleEC->sadd(K::CUSTOM_ROUTE_SET, $route);
            }
            catch (\Throwable $e)
            {
                ++$failedIteration;
                $this->trace->traceException($e, null, null, compact("key"));
            }

        }

        $this->trace->info(TraceCode::THROTTLE_REDIS_KEY_MIGRATE, compact('totalIteration', 'failedIteration'));

        //Old settings
        $totalIteration = 0;
        $failedIteration = 0;

        $customSettings = $redisLabs->smembers('throttle:custom');

        foreach ($customSettings as $setting)
        {
            ++$totalIteration;
            $key = $setting;

            try
            {
                $rules = $redisLabs->hgetall($key);

                $key = str_replace("throttle:t:i:", "{throttle:t}:i:", $key);

                if ($rules != null && !empty($rules))
                {
                    $throttleEC->hmset($key, $rules);
                }

                $throttleEC->sadd(K::CUSTOM_SETTINGS_SET, $key);

            }
            catch (\Throwable $e)
            {
                ++$failedIteration;
                $this->trace->traceException($e, null, null, compact("key"));
            }

        }

        $this->trace->info(TraceCode::THROTTLE_REDIS_KEY_MIGRATE, compact('totalIteration', 'failedIteration'));

        // Migrate global settings
        $rules = $redisLabs->hgetall("throttle:t");

        if ($rules != null && !empty($rules))
        {
            $throttleEC->hmset(K::GLOBAL_SETTINGS_KEY, $rules);
        }

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

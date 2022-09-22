<?php


namespace RZP\Models\Merchant\Store;

use App;
use Cache;
use RZP\Trace\TraceCode;

class RedisStore extends Store
{

    const PREFIX = "merchant_store";

    protected $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
    }

    public function get(string $merchantId, string $namespace, string $key)
    {
        $cacheKey = $this->getCacheKey($merchantId, $namespace, $key);

        return Cache::get($cacheKey);
    }

    public function put(string $merchantId, string $namespace, string $key, $value)
    {
        $cacheKey = $this->getCacheKey($merchantId, $namespace, $key);

        $config = ConfigKey::NAMESPACE_KEY_CONFIG[$namespace][$key];

        if (empty($config[Constants::TTL]))
        {
            Cache::forever($cacheKey, $value);
        }
        else
        {
            Cache::put($cacheKey, $value, $config[Constants::TTL]);
        }

    }

    public function delete(string $merchantId, string $namespace, string $key)
    {
        $cacheKey = $this->getCacheKey($merchantId, $namespace, $key);

        Cache::forget($cacheKey);
    }

    protected function getCacheKey(string $merchantId, string $namespace, string $key)
    {
        return self::PREFIX . ':' . $merchantId . ':' . $namespace . ':' . $key;
    }
}

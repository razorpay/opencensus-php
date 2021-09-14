<?php


namespace RZP\Models\Merchant\Store;

use App;
use Cache;

class RedisStore extends Store
{

    const PREFIX = "merchant_store";

    public function __construct()
    {
        $app = App::getFacadeRoot();
    }

    public function get(string $merchantId, string $namespace, string $key)
    {
        $cacheKey = $this->getCacheKey($merchantId, $namespace, $key);

        return Cache::get($cacheKey);
    }

    public function put(string $merchantId, string $namespace, string $key, $value)
    {
        $cacheKey = $this->getCacheKey($merchantId, $namespace, $key);

        Cache::forever($cacheKey, $value);
    }

    public function getAll(string $merchantId, string $namespace = null)
    {
        $data = [];

        if(empty($namespace) === false)
        {
            $configKeys = array_keys(ConfigKey::NAMESPACE_KEY_CONFIG[$namespace] ?? []);

            foreach ($configKeys as $key)
            {
                $data[$key] = $this->get($merchantId, $namespace, $key);
            }
        }
        else
        {
            foreach (array_keys(ConfigKey::NAMESPACE_KEY_CONFIG) as $namespace)
            {
                $data[$namespace] = $this->getAll($merchantId, $namespace);
            }
        }

        return $data;
    }

    protected function getCacheKey(string $merchantId, string $namespace, string $key)
    {
        return self::PREFIX .':'. $merchantId . ':' . $namespace . ':' . $key;
    }
}

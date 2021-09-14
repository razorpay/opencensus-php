<?php


namespace RZP\Models\Merchant\Store;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function updateMerchantStore(string $merchantId, array $input)
    {
        (new Validator())->validateInput('update', $input);

        $namespace = $input[Constants::NAMESPACE];
        unset($input[Constants::NAMESPACE]);

        foreach ($input as $key => $value)
        {
            $store = Factory::getStoreForNamespaceAndKey($namespace, $key);

            $store->put($merchantId, $namespace, $key, $value);
        }

        return $input;
    }

    public function fetchMerchantStore(string $merchantId, array $input)
    {
        (new Validator())->validateInput('fetch', $input);
        $namespace = $input[Constants::NAMESPACE] ?? null;

        $redisStore = (new RedisStore());

        $data = $redisStore->getAll($merchantId, $namespace);

        return $data;
    }
}

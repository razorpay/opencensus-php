<?php


namespace RZP\Models\Merchant\Store;

use RZP\Models\Base;

class DefaultKey extends Base\Core
{
    /**
     * @param string $merchantId
     * @param string $namespace
     * @param string $key
     *
     * @return mixed
     * @throws \RZP\Exception\LogicException
     */
    public function getValue(string $merchantId, string $namespace, string $key)
    {
        $store = Factory::getStoreForNamespaceAndKey($namespace, $key);

        return $store->get($merchantId, $namespace, $key);
    }

}

<?php


namespace RZP\Models\Merchant\Store;


abstract class Store
{
    public abstract function get(string $merchantId, string $namespace, string $key);

    public abstract function delete(string $merchantId, string $namespace, string $key);

    public abstract function put(string $merchantId, string $namespace, string $key, $value);
}

<?php

namespace RZP\Models\DataStore\PrioritySet;

use RZP\Exception;
use RZP\Models\Base;

class Factory extends Base\Core
{
    /**
     * Returns the store implementation to be used as per the store tyoe
     * @param  string $storeType Type of store driver
     *
     * @return Store implementation to be used
     */
    public static function getStore(string $storeType, bool $mock)
    {
        $storeClassName = self::getStoreClass($storeType, $mock);

        return (new $storeClassName);
    }

    protected static function getStoreClass(string $storeType, bool $mock)
    {
        $namespace = __NAMESPACE__ . '\Implementation';

        if ($mock === true)
        {
            $namespace .= '\Mock';
        }

        $namespace .= '\\' . studly_case($storeType);

        return $namespace;
    }
}

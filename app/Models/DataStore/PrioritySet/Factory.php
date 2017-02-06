<?php

namespace RZP\Models\DataStore\PrioritySet;

use RZP\Exception;
use RZP\Models\Base;

class Factory extends Base\Core
{
    const REDIS = 'redis';

    protected $mock;

    protected $storeType;

    public function __construct($mock)
    {
        $this->mock = $mock;
    }

    /**
     * Returns the store implementation to be used as per the store tyoe
     * @param  string $storeType Type of store driver
     *
     * @return Store implementation to be used
     */
    public function getStore(string $storeType)
    {
        $this->storeType = $storeType;

        $storeClassName = $this->getStoreClass();

        return (new $storeClassName);
    }

    protected function getStoreClass()
    {
        $namespace = __NAMESPACE__ . '\Implementation';

        if ($this->mock === true)
        {
            $namespace .= '\Mock';
        }

        $namespace .= '\\' . studly_case($this->storeType);

        return $namespace;
    }
}

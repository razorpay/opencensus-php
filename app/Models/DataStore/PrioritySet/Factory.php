<?php

namespace RZP\Models\DataStore\PrioritySet;

use RZP\Exception;
use RZP\Models\Base;
// use RZP\Models\DataStore\PrioritySet\Implementation;

class Factory extends Base\Core
{
    const REDIS = 'redis';

    protected $mock;

    protected $storeType;

    public function __construct($mock)
    {
        $this->mock = $mock;
    }

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

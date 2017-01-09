<?php

namespace RZP\Models\Terminal\GatewayPriorities;

use RZP\Models\Base\Redis;

class Entity extends Redis\SortedSet
{
    protected static $keyPrefix = 'gateway_priorities';

    protected static $delimiter = ':';

    public function build(array $priorities)
    {
        $this->data = $priorities;

        return $this;
    }

    public function getMethod()
    {
        return $this->key;
    }

    public function getPriorities()
    {
        return $this->data;
    }

    public function getGateways()
    {
        if ($this->data !== null and count($this->data) > 0)
        {
            return array_keys($this->data);
        }

        return null;
    }
}

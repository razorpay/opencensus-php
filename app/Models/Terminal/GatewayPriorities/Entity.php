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
        return $this->getKey();
    }

    public function getPriorities()
    {
        return $this->getData();
    }

    public function getGateways()
    {
        return $this->getSetMembers();
    }
}

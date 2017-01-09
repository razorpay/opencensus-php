<?php

namespace RZP\Models\Terminal\GatewayPriorities;

use RZP\Models\Base\Redis;

class Entity extends Redis\SortedSet
{
    protected static $keyPrefix = 'gateway_priorities';

    protected static $delimiter = ':';

    public function build(string $method, array $priorities)
    {
        $this->key = $method;

        $this->data = $priorities;

        return $this;
    }

    public function fetchPrioritiesForMethod(string $method)
    {
        $this->key = $method;

        $this->fetchMembers();

        return $this;
    }

    public function removePrioritiesForMethod($method, array $gateways)
    {
        $this->key = $method;

        $this->removeMembers($gateways);
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
        if ($this->data !== null)
        {
            return array_keys($this->data);
        }

        return null;
    }
}

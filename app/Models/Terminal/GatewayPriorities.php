<?php

namespace RZP\Models\Terminal;

use RZP\Models\Base\Redis;

class GatewayPriorities extends Redis\SortedSet
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

    public function fetchOrderedGatewaysForMethod(string $method)
    {
        $this->key = $method;

        $withScores = false;

        $this->fetchMembers($withScores);

        return $this->data;
    }

    public function removePrioritiesForMethod($method, $gateways)
    {
        $this->key = $method;

        $this->removeMembers($gateways);
    }
}

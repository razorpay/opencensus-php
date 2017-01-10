<?php

namespace RZP\Models\Terminal\GatewayPriorities;

use RZP\Models\Base\Redis;

/**
 * Gateway Priorites is stored as aredis sorted set with key as the method
 * The data is an associative array of gateway => score . The ordering of gateways
 * is thus maintained by redis on the basis of score.
 */
class Entity extends Redis\SortedSet
{
    protected static $keyPrefix = 'gateway_priorities';

    protected static $delimiter = ':';

    public function build(array $priorities)
    {
        $validator = new Validator($this);

        $validator->validateInput('create', $priorities);

        $this->setData($priorities);

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

    /**
     * Get gateways ordered by priority in decreasing ordered
     * @return array list of gateways
     */
    public function getGateways()
    {
        return $this->getSetMembers();
    }
}

<?php

namespace RZP\Models\DataStore\PrioritySet\Implementation\Mock;

use RZP\Models\DataStore\PrioritySet\Implementation;

/**
 * Defines mock implementation to be used in tests
 */
class Redis extends Implementation\Redis
{
    /**
     * In tests we want to set the priority set data as an empty array
     * so that it falls back to the default gateway priority in the payment flow
     */
    public function fetchOrFail()
    {
        if ($this->getPrefix() === 'gateway_priority')
        {
            $this->setData([]);
        }

        return $this;
    }
}

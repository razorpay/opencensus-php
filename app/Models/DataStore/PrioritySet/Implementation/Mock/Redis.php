<?php

namespace RZP\Models\DataStore\PrioritySet\Implementation\Mock;

use RZP\Models\DataStore\PrioritySet\Implementation;

class Redis extends Implementation\Redis
{
    public function fetchOrFail()
    {
        if ($this->getPrefix() === 'gateway_priority')
        {
            $this->setData([]);
        }

        return $this;
    }
}

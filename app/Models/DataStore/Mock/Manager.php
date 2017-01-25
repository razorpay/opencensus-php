<?php

namespace RZP\Models\DataStore\Mock;

use RZP\Models\DataStore;

class Manager extends DataStore\Manager
{
    // Returns dummy data for tests
    public function fetchOrFail(DataStore\Base $store)
    {
        if (($store instanceof DataStore\PrioritySet) and
            ($store->getPrefix() === 'gateway_priority'))
        {
            $store->setData([]);
        }

        return $store;
    }
}

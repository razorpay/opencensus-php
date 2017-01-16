<?php

namespace RZP\Services\Mock;

use RZP\Models\Payment\Gateway;
use RZP\Models\DataStore;
use RZP\Services\DataStoreManager as BaseDataStoreManager;

class DataStoreManager extends BaseDataStoreManager
{
    // Returns dummy data for tests
    public function fetch(DataStore\Base $store)
    {
        if (($store instanceof DataStore\PrioritySet) and
            ($store->getKeyPrefix() === 'gateway_priority'))
        {
            $store->setData([]);
        }

        return $store;
    }
}

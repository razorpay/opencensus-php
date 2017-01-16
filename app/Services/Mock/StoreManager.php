<?php

namespace RZP\Services\Mock;

use RZP\Models\Payment\Gateway;
use RZP\Models\Store;
use RZP\Services\StoreManager as BaseStoreManager;

class StoreManager extends BaseStoreManager
{
    // Returns dummy data for tests
    public function fetch(Store\Base $store)
    {
        if (($store instanceof Store\PrioritySet) and
            ($store->getKeyPrefix() === 'gateway_priority'))
        {
            $store->setData([]);
        }

        return $store;
    }
}

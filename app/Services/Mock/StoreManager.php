<?php

namespace RZP\Services\Mock;

use RZP\Models\Payment\Gateway;
use RZP\Models\Store;
use RZP\Services\StoreManager as BaseStoreManager;

class  StoreManager extends BaseStoreManager
{
    // Returns dummy data for tests
    public function fetch(Store\Base $store)
    {
        if (($store instanceof Store\PriorityStore) and
            $store->getKeyPrefix() === 'gateway_priority')
        {
            if ($store->getKey() === 'card')
            {
                $store->setData([
                    Gateway::HDFC        => '50',
                    Gateway::AXIS_MIGS   => '40',
                    Gateway::AMEX        => '30',
                    Gateway::CYBERSOURCE => '20',
                    Gateway::FIRST_DATA  => '10'
                ]);
            }

            $store->setData([
                Gateway::BILLDESK => '50',
                Gateway::EBS      => '40'
            ]);
        }

        return $store;
    }
}

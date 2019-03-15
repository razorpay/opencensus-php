<?php

namespace RZP\Models\P2p\Device;

use RZP\Exception;
use RZP\Models\Customer;
use RZP\Models\P2p\Base;

class Repository extends Base\Repository
{
    protected $entity = 'p2p_device';

    public function findByAuthToken(string $authToken)
    {
        return $this->newQuery()
                    ->where(Entity::AUTH_TOKEN, $authToken)
                    ->firstOrFailPublic();
    }

    /**
     * @param array $properties
     * @return Entity|null
     */
    public function findByDeviceProperties(array $properties)
    {
        return $this->newP2pQuery()
                    ->where($properties)
                    ->first();
    }

    public function fetchAllByCustomer(Customer\Entity $customer)
    {
        return $this->newP2pQuery()
                    ->where(Entity::CUSTOMER_ID, $customer->getId())
                    ->get();
    }
}

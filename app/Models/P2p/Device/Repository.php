<?php

namespace RZP\Models\P2p\Device;

use RZP\Exception\P2p;
use RZP\Models\Customer;
use RZP\Models\P2p\Base;
use RZP\Error\P2p\ErrorCode;

class Repository extends Base\Repository
{
    protected $entity = 'p2p_device';

    public function findByAuthToken(string $authToken)
    {
        $device = $this->newQuery()
                    ->where(Entity::AUTH_TOKEN, $authToken)
                    ->first();

        if (is_null($device))
        {
            throw new P2p\BadRequestException(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET);
        }

        return $device;
    }

    /**
     * @param array $properties
     * @return Entity|null
     */
    public function findByDeviceProperties(array $properties)
    {
        return $this->newP2pQuery()
                    ->where($properties)
                    ->latest()
                    ->first();
    }

    public function fetchAllByCustomer(Customer\Entity $customer)
    {
        return $this->newP2pQuery()
                    ->where(Entity::CUSTOMER_ID, $customer->getId())
                    ->get();
    }
}

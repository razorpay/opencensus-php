<?php

namespace RZP\Models\Vpa;

use RZP\Models\Base;
use RZP\Models\Customer\Repository as CustomerRepo;

class Repository extends Base\Repository
{
    protected $entity = 'vpa';

    public function findByAddress($address, bool $withTrashed = false)
    {
        $query = $this->newQuery()
                      ->address($address);

        if ($withTrashed === true)
        {
            $query = $query->withTrashed();
        }

        return $query->first();
    }

    /**
     * @param  string $address
     * @param  string $merchantId
     * @return Entity|null
     */
    public function findLatestByAddressAndMerchantId(string $address, string $merchantId)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->address($address)
                    ->merchantId($merchantId)
                    ->latest()
                    ->first();
    }
}

<?php

namespace RZP\Models\Vpa;

use RZP\Models\Base;
use RZP\Models\Customer\Repository as CustomerRepo;

class Repository extends Base\Repository
{
    protected $entity = 'vpa';

    public function findByAddress($address)
    {
        return $this->newQuery()
                    ->address($address)
                    ->first();
    }

    /**
     * @param  string $address
     * @param  string $merchantId
     * @return Entity|null
     */
    public function findLatestByAddressAndMerchantId(string $address, string $merchantId)
    {
        return $this->newQuery()
                    ->address($address)
                    ->merchantId($merchantId)
                    ->latest()
                    ->first();
    }
}

<?php

namespace RZP\Models\Customer\Balance;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Customer;

class Repository extends Base\Repository
{
    protected $entity = 'customer_balance';

    public function findByCustomerAndMerchantSilent(Customer\Entity $customer, Merchant\Entity $merchant)
    {
        return $this->newQuery()
                    ->lockForUpdate()
                    ->merchantId($merchant->getId())
                    ->find($customer->getId());
    }

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::CUSTOMER_ID, 'desc');
    }
}

<?php

namespace RZP\Models\Customer\Balance;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Customer;

class Repository extends Base\Repository
{
    protected $entity = 'customer_balance';

    public function getCustomerBalanceLockForUpdate(string $customerId)
    {
        assert ($this->isTransactionActive());

        return Entity::lockForUpdate()->findOrFail($customerId);
    }

    public function findByIdAndMerchantSilent(string $customerId, Merchant\Entity $merchant)
    {
        return $this->newQuery()
                    ->merchantId($merchant->getId())
                    ->find($customerId);
    }
}

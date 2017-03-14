<?php

namespace RZP\Models\Customer\Balance;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Customer;

class Repository extends Base\Repository
{
    protected $entity = 'customer_balance';

    public function findByCustomerIdAndMerchantSilent(
        string $customerId,
        Merchant\Entity $merchant,
        bool $lock = false)
    {
        $query = $this->newQuery()
                      ->merchantId($merchant->getId());

        if ($lock === true)
        {
            $query->lockForUpdate();
        }

        return $query->find($customerId);
    }

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::CUSTOMER_ID, 'desc');
    }
}

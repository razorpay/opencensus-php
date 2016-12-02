<?php

namespace RZP\Models\Customer\Balance;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = 'customer_balance';

    public function getCustomerBalanceLockForUpdate($customerId, Merchant\Entity $merchant)
    {
        assert($this->isTransactionActive());

        return $this->findByCustomerIdAndMerchant($customerId, $merchant, true);
    }

    public function updateBalance($balance)
    {
        assert($this->isTransactionActive());

        $balance->saveOrFail();
    }

    public function findByCustomerIdAndMerchantSilent(string $customerId, Merchant\Entity $merchant)
    {
        $customerId = Entity::verifyIdAndSilentlyStripSign($customerId);

        return $this->newQuery()
                    ->where(Entity::CUSTOMER_ID, $customerId)
                    ->where(Entity::MERCHANT_ID, $merchant->getId())
                    ->first();
    }

    public function findByCustomerIdAndMerchant(string $customerId, Merchant\Entity $merchant, $lockForUpdate = false)
    {
        $customerId = Entity::verifyIdAndStripSign($customerId);

        $query = $this->newQuery()
                      ->where(Entity::CUSTOMER_ID, $customerId)
                      ->where(Entity::MERCHANT_ID, $merchant->getId());

        if ($lockForUpdate === true)
        {
            $query->lock(true);
        }

        return $query->firstOrFail();
    }

}

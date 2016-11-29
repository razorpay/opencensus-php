<?php

namespace RZP\Models\Wallet;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = 'wallets';

    public function findByCustomerIdAndMerchantSilent(string $customerId, Merchant\Entity $merchant)
    {
        $customerId = Entity::verifyIdAndSilentlyStripSign($customerId);

        return $this->newQuery()
                    ->where(Entity::CUSTOMER_ID, $customerId)
                    ->where(Entity::MERCHANT_ID, $merchant->getId())
                    ->first();
    }

    public function findByCustomerIdAndMerchant(string $customerId, Merchant\Entity $merchant)
    {
        $customerId = Entity::verifyIdAndStripSign($customerId);

        return $this->newQuery()
                    ->where(Entity::CUSTOMER_ID, $customerId)
                    ->where(Entity::MERCHANT_ID, $merchant->getId())
                    ->firstOrFail();
    }

}

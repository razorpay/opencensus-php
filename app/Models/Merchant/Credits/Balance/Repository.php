<?php

namespace RZP\Models\Merchant\Credits\Balance;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'credit_balance';

    /**
     * @param string      $merchantId
     * @param string      $balanceType
     * @param string      $product

     * @return mixed
     */
    public function getMerchantCreditBalanceByTypeAndProduct(string $merchantId, string $balanceType, string $product)
    {
        $query = $this->newQuery();

        return $query->where(Entity::MERCHANT_ID, $merchantId)
                     ->where(Entity::TYPE, $balanceType)
                     ->where(Entity::PRODUCT, $product)
                     ->first();
    }

    public function getMerchantCreditBalanceByProduct(string $merchantId, string $product)
    {
        $query = $this->newQuery();

        return $query->where(Entity::MERCHANT_ID, $merchantId)
                     ->where(Entity::PRODUCT, $product)
                     ->get();
    }
}

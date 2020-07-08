<?php

namespace RZP\Models\Merchant\Credits\Balance;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = 'credit_balance';

    /**
     * @param string      $merchantId
     * @param string      $balanceType
     * @param string      $product

     * @return mixed
     */
    public function findMerchantCreditBalanceByTypeAndProduct(string $merchantId, string $balanceType, string $product)
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
                     ->where(function ($query)
                     {
                         $query->where(Entity::EXPIRED_AT, '>', time())
                               ->orWhereNull(Entity::EXPIRED_AT);
                     })
                     ->get();
    }

    public function getMerchantCreditBalanceAggregatedByProductForEveryType(string $merchantId, string $product): array
    {
        $query = $this->newQuery()
                      ->selectRaw(Entity::TYPE . ', ' . 'SUM(' . Entity::BALANCE . ') AS sum')
                      ->merchantId($merchantId)
                      ->where(Entity::PRODUCT, $product)
                      ->where(function ($query)
                      {
                          $query->where(Entity::EXPIRED_AT, '>', time())
                                ->orWhereNull(Entity::EXPIRED_AT);
                      })
                     ->groupBy(Entity::TYPE)
                     ->get();

        $data = [];

        foreach ($query as $record)
        {
            $data[$record[Entity::TYPE]] = $record['sum'];
        }

        return $data;
    }

    public function getCreditBalanceByTypeAndProduct(Merchant\Entity $merchant, string $type, string $product)
    {
        $query = $this->newQuery();

        return $query->where(Entity::MERCHANT_ID, $merchant->getId())
                    ->where(Entity::PRODUCT, $product)
                    ->where(Entity::TYPE, $type)
                    ->where(Entity::BALANCE, '>', 0)
                    ->where(function ($query)
                    {
                        $query->where(Entity::EXPIRED_AT, '>', time())
                            ->orWhereNull(Entity::EXPIRED_AT);
                    })
                    ->get();
    }
}

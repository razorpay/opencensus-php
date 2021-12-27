<?php

namespace RZP\Models\Merchant\Promotion;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Promotion;

class Repository extends Base\Repository
{
    protected $entity = 'merchant_promotion';

    protected $appFetchParamRules = [
        Entity::PROMOTION_ID => 'sometimes|alpha_num',
        Entity::MERCHANT_ID  => 'sometimes|alpha_num',
    ];

    public function findByMerchantAndPromotionId(string $merchantId, string $promotionId)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where(Entity::PROMOTION_ID, $promotionId)
                    ->first();
    }

    public function fetchMerchantsWithPromotion(string $promotionId, $from, $to)
    {
        return $this->newQuery()
            ->select(Entity::MERCHANT_ID)
            ->whereBetween(Entity::CREATED_AT, [$from, $to])
            ->where(Entity::PROMOTION_ID, $promotionId)
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }

    public function fetchMerchantIdsWithAnyPromotion(array $merchantIdList)
    {
        return $this->newQuery()
            ->whereIn(Entity::MERCHANT_ID, $merchantIdList)
            ->get()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }


    public function getCountByPromotionId(string $promotionId)
    {
        $count = $this->newQuery()
                      ->where(Entity::PROMOTION_ID, $promotionId)
                      ->count();

        return $count;
    }

    public function getByMerchantId(string $merchantId)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->get();
    }

    public function checkIfMerchantPromotionAlreadyExists(Merchant\Entity $merchant, Promotion\Entity $promotion)
    {
        return $this->newQuery()
                    ->merchantId($merchant->getId())
                    ->where(Entity::PROMOTION_ID, $promotion->getId())
                    ->first();
    }

    public function isMerchantAssociatedWithPromoCode(string $merchantId, string $couponCode) : bool
    {
        $merchantPromotionId = $this->dbColumn(Entity::PROMOTION_ID);
        $promotionId = $this->repo->promotion->dbColumn(Promotion\Entity::ID);

        return $this->newQuery()
            ->join(TABLE::PROMOTION, $promotionId, '=', $merchantPromotionId)
            ->where(Promotion\Entity::NAME, '=', $couponCode)
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->exists();
    }
}

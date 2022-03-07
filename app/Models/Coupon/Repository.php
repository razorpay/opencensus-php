<?php

namespace RZP\Models\Coupon;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Timezone;

class Repository extends Base\Repository
{
    protected $entity = 'coupon';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID         => 'sometimes|alpha_num|max:14',
        Entity::ENTITY_ID           => 'sometimes|alpha_num|max:14',
        Entity::ENTITY_TYPE         => 'sometimes|string|in:promotion',
        Entity::CODE                => 'sometimes|string',
    ];

    public function findByEntityIdAndEntityType(string $entityId,string $entityType)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $entityId)
                    ->where(Entity::ENTITY_TYPE,'=',$entityType)
                    ->first();
    }

    public function isPromoCodeActiveForMerchant(string $merchantId, string $couponCode) : bool
    {
        return $this->newQuery()
            ->where(Entity::CODE, '=', $couponCode)
            ->whereIn(Entity::MERCHANT_ID, [$merchantId, Merchant\Account::SHARED_ACCOUNT])
            ->where(Entity::ENTITY_TYPE, '=', Entity::ENTITY_TYPE_PROMOTION)
            ->where(Entity::END_AT, '>=', Carbon::now(Timezone::IST)->getTimestamp())
            ->exists();
    }

    public function fetchByCodeWithRelations(string $code, string $merchantId)
    {
        $allowedMerchantIds = [Merchant\Account::SHARED_ACCOUNT, $merchantId];

        return $this->newQuery()
                    ->where(Entity::CODE, '=', $code)
                    ->whereIn(Entity::MERCHANT_ID, $allowedMerchantIds)
                    ->with(['source'])
                    ->first();
    }

    public function fetchCouponsByExpiry(array $dateRanges)
    {
        $query = $this->newQuery();
        $count=0;
        foreach ($dateRanges as $dates)
        {
            if($count ===0)
                $query->where(Entity::END_AT, '>=', $dates[0])->where(Entity::END_AT, '<', $dates[1]);
            else
                $query->orWhere(Entity::END_AT, '>=', $dates[0])->where(Entity::END_AT, '<', $dates[1]);
            $count++;
        }
        return $query->get();
    }


}

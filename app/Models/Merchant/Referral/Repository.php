<?php

namespace RZP\Models\Merchant\Referral;

use RZP\Models\Base;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'referrals';

    /**
     * These are admin allowed params to search on.
     *
     * @var array
     */
    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'sometimes|string|unsigned_id',
        Entity::REF_CODE    => 'sometimes|string|max:14',
        Entity::URL         => 'sometimes|string',
    ];

    /**
     * @param string $merchantId
     *
     * @return mixed
     */
    public function getReferralByMerchantId(string $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->get();
    }


    public function getReferralsByMerchantIds(array $merchantIds)
    {
        return $this->newQuery()
                    ->whereIn(Entity::MERCHANT_ID, $merchantIds)
                    ->get();
    }

    public function getReferralByMerchantIdAndProduct(string $merchantId, string $product)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::PRODUCT, $product)
                    ->get();
    }

    public function getReferralByReferralCode(string $referralCode)
    {
        return $this->newQuery()
                    ->where(Entity::REF_CODE, $referralCode)
                    ->first();
    }

}

<?php


namespace RZP\Models\Merchant\M2MReferral;

use RZP\Models\Base\RepositoryUpdateTestAndLive;
use RZP\Models\Base;
class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive
    {
        saveOrFail as saveOrFailTestAndLive;
    }

    protected $entity = 'm2m_referral';

    /**
     * @param string $merchantId
     *
     * @return mixed
     */
    public function getReferralCount(string $merchantId)
    {
        return $this->newQueryOnSlave()
                    ->where(Entity::REFERRER_ID, '=', $merchantId)
                    ->where(Entity::REFERRER_STATUS, '=', Status::REWARDED)
                    ->count();
    }

    /**
     * @param string $merchantId
     *
     * @return mixed
     */
    public function getReferralDetailsFromMerchantId(string $merchantId)
    {
        return $this->newQueryOnSlave()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->first();

    }
}

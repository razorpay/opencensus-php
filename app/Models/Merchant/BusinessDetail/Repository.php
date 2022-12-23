<?php


namespace RZP\Models\Merchant\BusinessDetail;

use RZP\Models\Base;
use RZP\Gateway\Base\Entity;
use RZP\Models\Base\RepositoryUpdateTestAndLive;
use RZP\Modules\Acs\Wrapper\MerchantBusinessDetail as MerchantBusinessDetailWrapper;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_business_detail';

    public function getBusinessDetailsForMerchantId(string $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->first();
    }

    public function __getBusinessDetailsForMerchantId(string $merchantId)
    {
        $businessDetailFromApi = $this->getBusinessDetailsForMerchantId($merchantId);
        if ($businessDetailFromApi === null) {
            return $businessDetailFromApi;
        }
        return (new MerchantBusinessDetailWrapper())->GetMerchantBusinessDetailForMerchantId($merchantId, $businessDetailFromApi);
    }

    public function __saveOrFail($businessDetail) {
        return $this->repo->transactionOnLiveAndTest(function () use ($businessDetail) {
            $this->saveOrFail($businessDetail);
            (new MerchantBusinessDetailWrapper())->SaveOrFail($businessDetail);
        });
    }
}

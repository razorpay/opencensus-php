<?php


namespace RZP\Models\Merchant\Website;

use RZP\Models\Base;
use RZP\Base\ConnectionType;
use RZP\Models\Base\RepositoryUpdateTestAndLive;
use RZP\Models\Merchant\Website\Entity as MerchantWebsiteEntity;
use RZP\Modules\Acs\Wrapper\MerchantWebsite as MerchantWebsiteWrapper;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_website';

    public function getWebsiteDetailsForMerchantId(string $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->first();
    }

    public function getAllWebsiteDetailsForMerchantId(string $merchantId)
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->get();
    }

    /*
     * Important: This function is to be used to migrate reads to Account Service
     * Only use this if you are trying to do read on account service database.
     */
    public function __getWebsiteDetailsForMerchantId(string $merchantId)
    {
        $apiMerchantWebsiteEntity = $this->getWebsiteDetailsForMerchantId($merchantId);
        if ($apiMerchantWebsiteEntity === null) {
            return $apiMerchantWebsiteEntity;
        }
        return (new MerchantWebsiteWrapper())->processGetWebsiteDetailsForMerchantId($merchantId, $apiMerchantWebsiteEntity);
    }

    /**
     * __saveOrFail -  Keeping the method name not same with base repository method, this to be renamed  and used in merchant website core while ramp-up
     * @param MerchantWebsiteEntity $entity
     * @throws \Throwable
     */
    public function __saveOrFail(MerchantWebsiteEntity $entity)
    {
        $this->repo->transactionOnLiveAndTest(function () use ($entity) {
            $this->saveOrFail($entity);
            $merchantWebsiteWrapper = new MerchantWebsiteWrapper();
            $merchantWebsiteWrapper->SaveOrFail($entity);
        });
    }

    /*
 * Important: This function is to be used to migrate reads to Account Service
 * Only use this if you are trying to do read on account service database.
 */
    public function __findOrFail(string $id)
    {
        $apiMerchantWebsiteEntity = $this->findOrFail($id);
        return (new MerchantWebsiteWrapper())->processGetWebsiteDetailsForId($id, $apiMerchantWebsiteEntity);
    }
}

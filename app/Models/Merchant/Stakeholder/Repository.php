<?php

namespace RZP\Models\Merchant\Stakeholder;

use RZP\Models\Base;
use RZP\Models\Base\RepositoryUpdateTestAndLive;
use RZP\Models\Merchant\Stakeholder\Entity as MerchantStakeholderEntity;
use RZP\Modules\Acs\Wrapper\MerchantStakeholder as MerchantStakeholderWrapper;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'stakeholder';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID     => 'sometimes|string|size:14',
    ];

    public function fetchStakeholders(string $merchantId): Base\PublicCollection
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->get();
    }

    public function fetchEsignCompletedMerchants(array $merchantIdList)
    {
        return $this->newQuery()
            ->whereIn(Entity::MERCHANT_ID, $merchantIdList)
            ->where(Entity::AADHAAR_ESIGN_STATUS, '=', 'verified')
            ->get()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }
    /**
     * Important: This function is for migration reads to account service.
     * Can be used to fetch stakeholder from Account Service given the merchantId
     *
     * @throws \Throwable
     */
    public function __fetchStakeholders(string $merchantId): Base\PublicCollection
    {
        $apiStakeholders = $this->fetchStakeholders($merchantId);
        if (count($apiStakeholders) === 0) {
            return $apiStakeholders;
        }
        return (new MerchantStakeholderWrapper())->processFetchStakeholdersByMerchantId($merchantId, $apiStakeholders);
    }

    /**
     * Important: This function is for migration reads to account service.
     * Can be used to fetch stakeholder from Account Service
     *
     * @throws \Throwable
     */
    public function __findOrFailPublic(string $id)
    {
        $apiStakeholder = $this->findOrFailPublic($id);
        $id = Entity::stripDefaultSign($id);
        return (new MerchantStakeholderWrapper())->processFetchStakeholderById($id, $apiStakeholder);
    }

    /**
     * Important: This function is for migration reads to account service.
     * Can be used to fetch stakeholder from Account Service
     *
     * @throws \Throwable
     */
    public function __findOrFail(string $id) {
        return $this->repo->transactionOnLiveAndTest(function () use ($id) {
            $apiStakeholder = $this->findOrFail($id);
            return (new MerchantStakeholderWrapper())->processFetchStakeholderById($id, $apiStakeholder);
        });
    }

    /**
     * __saveOrFail -  Keeping the method name not same with base repository method, this to be renamed  and used in stakeholder core while ramp-up
     *Once stakeholder saveOrFail is migrated to Account service only this method should be used while saving the stakeholder entity any save on stakeholder has to be called at any new place
     * @param MerchantStakeholderEntity $entity
     * @param bool $testAndLive - If true saveEntity on both test and live db else only live db
     * @throws \Throwable
     */
    public function __saveOrFail(MerchantStakeholderEntity $entity, bool $testAndLive)
    {
        $this->repo->transactionOnLiveAndTest(function () use ($testAndLive, $entity) {
            if ($testAndLive === true) {
                $this->saveOrFail($entity);
            } else {
                $this->repo->saveOrFail($entity);
            }
            (new MerchantStakeholderWrapper())->SaveOrFail($entity);
        });
    }
}

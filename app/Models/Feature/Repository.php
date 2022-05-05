<?php

namespace RZP\Models\Feature;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Base\EsRepository;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\QueryCache\CacheQueries;
use RZP\Models\Settlement\OndemandFundAccount;

class Repository extends Base\Repository
{
    use CacheQueries;

    protected $entity = 'feature';

    protected $appFetchParamRules = array(
        Entity::ENTITY_ID   => 'sometimes|string|max:14',
        Entity::ENTITY_TYPE => 'sometimes|string|max:255',
        Entity::NAME        => 'sometimes|string|max:25'
    );

    public function fetchByEntityTypeAndEntityId(string $entityType, string $entityId, string $mode = null)
    {
        $cacheTtl = $this->getCacheTtl();
        $cacheTags = Entity::getCacheTagsForEntities($entityType, $entityId);

        $query = ($mode === null) ? $this->newQuery() : $this->newQueryWithConnection($mode);
        return $query->where(Entity::ENTITY_TYPE, $entityType)
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->remember($cacheTtl)
                    ->cacheTags($cacheTags)
                    ->get();
    }

    public function findByEntityTypeEntityIdAndNameOrFail(string $entityType, string $entityId, string $featureName)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_TYPE, $entityType)
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::NAME, $featureName)
                    ->firstOrFailPublic();
    }

    public function findByEntityTypeEntityIdAndName(string $entityType, string $entityId, string $featureName)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_TYPE, $entityType)
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::NAME, $featureName)
                    ->first();
    }

    public function findByEntityIdAndNameOnConnection(string $entityId, string $featureName, string $mode)
    {
        return $this->newQueryWithConnection($mode)
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::NAME, $featureName)
                    ->first();
    }

    public function findMerchantsHavingFeatures(array $featureNames, $limit = null, $afterId = null)
    {
        $query = $this->newQuery()
                      ->whereIn(Entity::NAME, $featureNames)
                      ->where(Entity::ENTITY_TYPE, 'merchant')
                      ->orderBy(Entity::ID);

        if (empty($limit) === false)
        {
            $query->take($limit);
        }

        if (empty($afterId) === false)
        {
            $query->where(Entity::ID, '>', $afterId);
        }

        return $query->get();
    }

    public function findMerchantWithFeatures(string $merchantId, array $featureNames)
    {
        return $this->newQuery()
                    ->select(Entity::NAME)
                    ->whereIn(Entity::NAME, $featureNames)
                    ->where(Entity::ENTITY_TYPE, 'merchant')
                    ->where(Entity::ENTITY_ID, $merchantId)
                    ->get();
    }

    public function findMerchantWithFeaturesOnConnection(string $merchantId, array $featureNames, $mode)
    {
        return $this->newQueryWithConnection($mode)
                    ->whereIn(Entity::NAME, $featureNames)
                    ->where(Entity::ENTITY_TYPE, 'merchant')
                    ->where(Entity::ENTITY_ID, $merchantId)
                    ->pluck(Entity::NAME)
                    ->toArray();
    }

    /**
     * Get a list a merchant ids that have the given features enabled.
     *
     * @param string[] $featureNames
     *
     * @return string[]
     */
    public function findMerchantIdsHavingFeatures(array $featureNames): array
    {
        return $this->newQuery()
            ->select(Entity::ENTITY_ID)
            ->whereIn(Entity::NAME, $featureNames)
            ->where(Entity::ENTITY_TYPE, Constants::MERCHANT)
            ->pluck(Entity::ENTITY_ID)
            ->toArray();
    }

    public function fetchMerchantIdsWithFeatureInChunks(string $featureName, $skip, $limit)
    {
        return $this->newQuery()
                    ->where(Entity::NAME, $featureName)
                    ->where(Entity::ENTITY_TYPE, Constants::MERCHANT)
                    ->skip($skip)
                    ->take($limit)
                    ->pluck(Entity::ENTITY_ID)
                    ->toArray();
    }

    public function fetchMerchantIdsWithFeatureAndNoFundAccountInChunks(string $featureName)
    {
        return $this->newQuery()
                    ->where(Entity::NAME, $featureName)
                    ->where(Entity::ENTITY_TYPE, Constants::MERCHANT)
                    ->leftJoin(Table::SETTLEMENT_ONDEMAND_FUND_ACCOUNT, OndemandFundAccount\Entity::MERCHANT_ID, Entity::ENTITY_ID)
                    ->where(function ($query) {
                        $query->whereNull(OndemandFundAccount\Entity::FUND_ACCOUNT_ID)
                              ->orWhere(OndemandFundAccount\Entity::FUND_ACCOUNT_ID, '=', '');
                    })
                    ->pluck(Entity::ENTITY_ID)
                    ->toArray();
    }

    public function getMerchantIdsHavingFeature(string $featureName, array $merchantIds)
    {
        return $this->newQuery()
                    ->select(Entity::ENTITY_ID)
                    ->whereIn(Entity::ENTITY_ID, $merchantIds)
                    ->where(Entity::NAME, $featureName)
                    ->where(Entity::ENTITY_TYPE, 'merchant')
                    ->pluck(Entity::ENTITY_ID)
                    ->toArray();
    }

    public function saveAndSyncIfApplicableOrFail(Entity $feature, array $assignedFeatureNames, bool $shouldSync)
    {
        if ($shouldSync === true)
        {
            $this->saveAndSyncOrFail($feature);
        }
        else
        {
            $feature->getValidator()->validateFeatureIsNotAlreadyAssigned($assignedFeatureNames);

            $this->repo->saveOrFail($feature);
        }
    }

    public function deleteAndSyncIfApplicableOrFail(Entity $feature, bool $shouldSync)
    {
        if ($shouldSync === true)
        {
            $this->deleteAndSyncOrFail($feature);
        }
        else
        {
            $this->deleteOrFail($feature);
        }
    }

    /**
     * Fetch features assigned to an application_id
     *
     * @param string $applicationId
     *
     * @return PublicCollection
     */
    public function getApplicationFeatureNames(string $applicationId): PublicCollection
    {
        $cacheTtl = $this->getCacheTtl();

        $cacheTags = Entity::getCacheTagsForNames(Constants::APPLICATION , $applicationId);

        return new PublicCollection($this->newQuery()
                                         ->where(Entity::ENTITY_TYPE, Constants::APPLICATION )
                                         ->where(Entity::ENTITY_ID, $applicationId)
                                         ->remember($cacheTtl)
                                         ->cacheTags($cacheTags)
                                         ->pluck(Entity::NAME)
                                         ->toArray());
    }

    /**
     * Save feature with sync: Adds features to test and live
     * DB's if they don't already exist
     *
     * @param Entity $entity
     */
    protected function saveAndSyncOrFail(Entity $entity)
    {
        $this->repo->transactionOnLiveAndTest(function () use ($entity)
        {
            $featureName = $entity->getName();
            $entityId    = $entity->getEntityId();

            $testEntity = $this->findByEntityIdAndNameOnConnection($entityId, $featureName, Mode::TEST);
            $liveEntity = $this->findByEntityIdAndNameOnConnection($entityId, $featureName, Mode::LIVE);

            if ($testEntity === null)
            {
                $this->cloneAndSaveToModeOrFail($entity, Mode::TEST);
            }

            if ($liveEntity === null)
            {
                $this->cloneAndSaveToModeOrFail($entity, Mode::LIVE);
            }
        });
    }

    /**
     * Delete a feature with sync: removes the record
     * from both test/live DB's if present
     *
     * @param Entity $entity
     */
    protected function deleteAndSyncOrFail(Entity $entity)
    {
        $this->repo->transactionOnLiveAndTest(function () use ($entity)
        {
            $featureName = $entity->getName();
            $entityId    = $entity->getEntityId();

            $testEntity = $this->findByEntityIdAndNameOnConnection($entityId, $featureName, Mode::TEST);
            $liveEntity = $this->findByEntityIdAndNameOnConnection($entityId, $featureName, Mode::LIVE);

            if ($testEntity !== null)
            {
                $testEntity->deleteOrFail();

                $this->syncToEs($entity, EsRepository::DELETE, null, Mode::TEST);
            }

            if ($liveEntity !== null)
            {
                $liveEntity->deleteOrFail();

                $this->syncToEs($entity, EsRepository::DELETE, null, Mode::LIVE);
            }
        });
    }

    private function cloneAndSaveToModeOrFail(Entity $entity, string $mode)
    {
        $modeEntity = clone $entity;
        $modeEntity->setConnection($mode);

        $modeEntity->saveOrFail();

        $this->syncToEs($modeEntity, EsRepository::CREATE, null, $mode);
    }

    /***
     * @param array $entityIds
     * @param string $featureName
     * @return mixed
     * This query is run to get the EntityId which are not in $entityId and have
     * Feature as passed by the $featureName
     * and Entity type is 'merchant'
     */
    public function findMerchantNotInEntityIdHavingFeature(array $entityIds, string $featureName)
    {
        return $this->newQuery()
                    ->whereNotIn(Entity::ENTITY_ID, $entityIds)
                    ->where(Entity::NAME, $featureName)
                    ->where(Entity::ENTITY_TYPE, Constants::MERCHANT)
                    ->get();
    }

    public function merchantOnEarlySettlement(Merchant\Entity $merchant)
    {
        $merchantId = $merchant->getId();

        $featureList = $this->findMerchantWithFeatures(
            $merchantId,
            [
                Constants::ES_AUTOMATIC,
                Constants::ES_AUTOMATIC_THREE_PM,
            ])
                            ->pluck(Entity::NAME);

        if($featureList->isEmpty() === true)
        {
            return [false, null];
        }

        return [true, $featureList->toArray()];
    }
}

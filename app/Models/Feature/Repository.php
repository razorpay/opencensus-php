<?php

namespace RZP\Models\Feature;

use DB;

use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Models\Base\EsRepository;
use RZP\Models\Merchant\Detail as MerchantDetail;
use RZP\Models\Base\Repository as BaseRepository;

class Repository extends BaseRepository
{
    protected $entity = 'feature';

    protected $appFetchParamRules = array(
        Entity::ENTITY_ID   => 'sometimes|string|max:14',
        Entity::ENTITY_TYPE => 'sometimes|string|max:255',
        Entity::NAME        => 'sometimes|string|max:25'
    );

    public function findByEntityId(string $entityId)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->get();
    }

    public function findByEntityIdAndNameOrFail(string $entityId, string $featureName)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::NAME, $featureName)
                    ->firstOrFailPublic();
    }

    public function findByEntityIdAndNameOnConnection(string $entityId, string $featureName, string $mode)
    {
        return $this->newQueryWithConnection($mode)
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::NAME, $featureName)
                    ->first();
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

    public function backfillFeatureActivationStatus()
    {
        $requests = DB::Connection('live')->select("
          SELECT * FROM
            (SELECT SUBSTRING_INDEX(`key`, '.', 1) AS product, entity_id
              FROM settings
              GROUP BY product, entity_id) AS products
        ");

        $merchantRequests = [];

        // Generate a merchant to products map
        foreach($requests as $request)
        {
            $merchantId = $request->entity_id;

            $product = $request->product;

            $merchantRequests[$merchantId] = $merchantRequests[$merchantId] ?? [];

            array_push($merchantRequests[$merchantId], $product);
        }

        $featureCore = new Feature\Core;

        foreach($merchantRequests as $merchantId => $productRequests)
        {
            $merchantId = strval($merchantId);

            $merchant = $this->repo->merchant->findByPublicId($merchantId);

            $merchantDetail = $merchant->merchantDetail;

            foreach($productRequests as $product)
            {
                $getProductActivationStatus = camel_case('get_' . $product . '_activation_status');

                $productStatus = $merchantDetail->$getProductActivationStatus();

                if ($productStatus === null)
                {
                    $featureEnabled = $featureCore->isFeatureEnabledInMode(
                        Mode::LIVE,
                        $merchantId,
                        $product);

                    if ($featureEnabled === true)
                    {
                        $status = MerchantDetail\Entity::APPROVED;
                    }
                    else
                    {
                        $status = MerchantDetail\Entity::PENDING;
                    }

                    $featureCore->updateFeatureActivationStatus($merchantId, $product, $status);
                }
            }
        }
    }

    /**
     * Returns an array of features enabled in the mode
     *
     * @param string $mode
     * @param string $merchantId
     *
     * @return array
     */
    public function getEnabledFeaturesInMode(string $mode, string $merchantId): array
    {
        $features = $this->newQueryWithConnection($mode)
                         ->select(Entity::NAME)
                         ->where(Entity::ENTITY_ID, $merchantId)
                         ->get()
                         ->pluck(Entity::NAME)
                         ->toArray();

        return $features;
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
}

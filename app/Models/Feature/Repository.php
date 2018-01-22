<?php

namespace RZP\Models\Feature;

use DB;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Base\EsRepository;
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

    public function updateOnboardingSubmissionTimestamp(
        string $merchantId,
        string $featureName,
        int $epochTimestamp): bool
    {
        $query = "SELECT * FROM settings 
                      WHERE `entity_type`='merchant' 
                      AND `entity_id`='$merchantId'
                      AND `module`='onboarding'
                      AND `key` LIKE '$featureName%'
                      AND `created_at`=1505957400";

        $results = DB::Connection('live')->select($query);

        if (count($results) > 0)
        {
            $updatedAt = '';

            // Update the timestamp only if it was not updated
            if (intval($results[0]->updated_at) === 1505957400)
            {
                $updatedAt = ', updated_at=' . $epochTimestamp;
            }

            $query = "UPDATE settings 
                          SET created_at='$epochTimestamp'$updatedAt
                          WHERE `entity_type`='merchant' 
                          AND `entity_id`='$merchantId'
                          AND `module`='onboarding'
                          AND `key` LIKE '$featureName%'
                          AND `created_at`=1505957400";

            $result = DB::Connection('live')->update($query);

            return $result;
        }

        $this->trace->info(TraceCode::FEATURE_ONBOARDING_TIMESTAMP_UPDATE_SKIPPED);

        return false;
    }

    private function cloneAndSaveToModeOrFail(Entity $entity, string $mode)
    {
        $modeEntity = clone $entity;
        $modeEntity->setConnection($mode);

        $modeEntity->saveOrFail();

        $this->syncToEs($modeEntity, EsRepository::CREATE, null, $mode);
    }
}

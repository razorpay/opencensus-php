<?php

namespace RZP\Models\Feature;

use DB;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Base\Repository as BaseRepository;
use RZP\Trace\TraceCode;

class Repository extends BaseRepository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'feature';

    protected $appFetchParamRules = array(
        Entity::ENTITY_ID   => 'sometimes|string|max:14',
        Entity::ENTITY_TYPE => 'sometimes|string|max:255',
        Entity::NAME        => 'sometimes|string|max:25'
    );

    public function findByEntityId(string $entityId)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $entityId)
                    ->get();
    }

    public function findByEntityIdAndNameOrFail(string $entityId, string $featureName)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $entityId)
                    ->where(Entity::NAME, '=', $featureName)
                    ->firstOrFailPublic();
    }

    /**
     * Features added to live should sync and be added to test.
     * Features when removed from live should not be removed from test.
     *
     * If the selected mode is live, then the features will be enabled for both
     * test as well as live mode. The features table in both the dbs will be populated.
     * Deleting a feature from test mode will only delete the feature from the
     * api_test.features table.
     * Deleting a feature from live mode will only delete the feature from the
     * api_live.features table.
     *
     * When a feature is enabled on test and request is received to enable it on live,
     * shouldSync() returns false, to avoid the duplicate entry constraint error.
     *
     * The AUTH used will determine the mode selected
     *
     * @param      $entity
     * @param null $action
     *
     * @return bool
     */
    public function shouldSync($entity, $action = null): bool
    {
        if (($this->isLiveMode() === true) and ($action !== BaseRepository::DELETE))
        {
            $entityId = $entity->getEntityId();

            $entityName = $entity->getName();

            // Sync if the feature is not already enabled on test
            $feature = $this->newQueryWithConnection(Mode::TEST)
                            ->where(Entity::ENTITY_ID,  '=', $entityId)
                            ->where(Entity::NAME,       '=', $entityName)
                            ->first();

            if ($feature === null)
            {
                $this->trace->info(TraceCode::FEATURE_SYNCED, [
                    $entityId,
                    $entityName
                ]);
                return true;
            }
        }

        return false;
    }
}

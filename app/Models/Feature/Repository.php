<?php

namespace RZP\Models\Feature;

use DB;

use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Base\Repository as BaseRepository;

class Repository extends Base\Repository
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
     * The AUTH used will determine the mode selected
     *
     * @param      $entity
     * @param null $action
     *
     * @return bool
     */
    public function shouldSync($entity, $action = null): bool
    {
        if (($this->app['rzp.mode'] === Mode::LIVE) and ($action !== BaseRepository::DELETE))
        {
            return true;
        }
        return false;
    }
}

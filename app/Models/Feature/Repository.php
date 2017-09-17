<?php

namespace RZP\Models\Feature;

use DB;

use RZP\Constants\Mode;
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

    public function findByEntityIdAndName(string $entityId, string $featureName, string $mode = Mode::TEST): Entity
    {
        return $this->newQueryWithConnection($mode)
                    ->where(Entity::ENTITY_ID, '=', $entityId)
                    ->where(Entity::NAME, '=', $featureName)
                    ->first();
    }
}

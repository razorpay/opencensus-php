<?php

namespace RZP\Models\Feature;

use DB;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Constants\Entity as EntityConstants;
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
     * Merchant features are de-synced by default.
     * The AUTH used will determine the mode selected
     * To sync them while insertion, $options should have the key 'should_sync' set to 1.
     *
     * @param       $entity
     * @param array $options
     *
     * @return bool
     */
    public function shouldSync($entity, $options = array()): bool
    {
        $shouldSync = EntityConstants::SHOULD_SYNC;

        $entityId = $entity->getEntityId();

        $entityName = $entity->getName();

        if ((isset($options[$shouldSync])) and ($options[$shouldSync] === 1))
        {
            if ($this->isTestMode() === true)
            {
                $findInMode = Mode::LIVE;
            }
            else
            {
                $findInMode = Mode::TEST;
            }

            $feature = $this->newQueryWithConnection($findInMode)
                ->where(Entity::ENTITY_ID,  '=', $entityId)
                ->where(Entity::NAME,       '=', $entityName)
                ->first();

            if ($feature === null)
            {
                $this->trace->info(TraceCode::FEATURE_SYNCED, [
                    $entityId,
                    $entityName,
                    $options
                ]);

                return true;
            }
        }

        $this->trace->info(TraceCode::FEATURE_NOT_SYNCED, [
            $entityId,
            $entityName,
            $options
        ]);

        return false;
    }
}

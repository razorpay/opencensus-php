<?php

namespace RZP\Models\Workflow\Action\State;

use RZP\Models\Workflow\Base;
use RZP\Models\State\Entity as ActionState;

class Repository extends Base\Repository
{
    protected $entity = 'action_state';

    protected $appFetchParamRules = [
        Entity::ADMIN_ID  => 'sometimes|string|max:14',
        Entity::ACTION_ID => 'sometimes|string|max:14',
    ];

    public function getLatestActionStateByEntityIdAndType(string $entityId, string $entityType)
    {
        return  $this->newQueryWithConnection($this->getMasterReplicaConnection())
                     ->where(ActionState::ENTITY_TYPE, $entityType)
                     ->where(ActionState::ENTITY_ID, $entityId)
                     ->orderByDesc(Entity::UPDATED_AT)
                     ->first();
    }

    public function isActionNameExistsForEntityIdAndType(string $entityId, string $entityType, $actionName) : bool
    {
        if (is_array($actionName) === false){
            $actionName = [$actionName];
        }
        return  $this->newQueryWithConnection($this->getMasterReplicaConnection())
            ->where(ActionState::ENTITY_TYPE, $entityType)
            ->where(ActionState::ENTITY_ID, $entityId)
            ->whereIn(ActionState::NAME, $actionName)
            ->exists();
    }
}

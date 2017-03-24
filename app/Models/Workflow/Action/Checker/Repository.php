<?php

namespace RZP\Models\Workflow\Action\Checker;

use RZP\Models\Workflow\Base;

class Repository extends Base\Repository
{
    protected $entity = 'action_checker';

    protected $adminFetchParamRules = [
        Entity::STEP_ID   => 'sometimes|string|max:14',
        Entity::ADMIN_ID  => 'sometimes|string|max:14',
        Entity::ACTION_ID => 'sometimes|string|max:14',
    ];

    public function fetchByActionId(string $actionId)
    {
        return $this->newQuery()
                    ->where(Entity::ACTION_ID, '=', $actionId)
                    ->whereNotNull(Entity::APPROVED)
                    ->get();
    }

    public function fetchCountByActionId()
    {
        return $this->newQuery()
                    ->where(Entity::ACTION_ID, '=', $actionId)
                    ->whereNotNull(Entity::APPROVED)
                    ->count();
    }

    public function findByIdAndActionId($checkerId, $actionId)
    {
        Action\Entity::verifyIdAndSilentlyStripSign($actionId);

        return $this->newQuery()
                    ->where(Entity::ID, '=', $checkerId)
                    ->where(Entity::ACTION_ID, '=', $actionId)
                    ->firstOrFail();
    }
}

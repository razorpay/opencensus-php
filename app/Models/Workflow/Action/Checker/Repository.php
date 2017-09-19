<?php

namespace RZP\Models\Workflow\Action\Checker;

use DB;
use RZP\Models\Workflow\Base;

class Repository extends Base\Repository
{
    protected $entity = 'action_checker';

    protected $adminFetchParamRules = [
        Entity::STEP_ID   => 'sometimes|string|max:14',
        Entity::ADMIN_ID  => 'sometimes|string|max:14',
        Entity::ACTION_ID => 'sometimes|string|max:14',
    ];

    public function fetchByActionIdWithRelations(string $actionId, $relations = [])
    {
        return $this->newQuery()
                    ->where(Entity::ACTION_ID, '=', $actionId)
                    ->with($relations)
                    ->get();
    }

    public function fetchCountByActionIdForStep($actionId, $stepId)
    {
        return $this->newQuery()
                    ->where(Entity::ACTION_ID, '=', $actionId)
                    ->where(Entity::STEP_ID, '=', $stepId)
                    // ->whereNotNull(Entity::APPROVED)
                    ->count();
    }

    public function fetchApprovedCountByActionIdAndStepIds(
        string $actionId,
        array $stepIds)
    {
        $countRaw = DB::raw('count(*) as total');

        return $this->newQuery()
                    ->select(Entity::STEP_ID, $countRaw)
                    ->where(Entity::ACTION_ID, '=', $actionId)
                    ->whereIn(Entity::STEP_ID, $stepIds)
                    ->where(Entity::APPROVED, '=', 1) // checked
                    ->groupBy(Entity::STEP_ID)
                    ->get();
    }

    public function findManyByStepIds(array $stepIds)
    {
        return $this->newQuery()
                    ->whereIn(Entity::STEP_ID, $stepIds)
                    ->get();
    }
}

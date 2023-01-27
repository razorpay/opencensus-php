<?php

namespace RZP\Models\Workflow\Action;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Illuminate\Database\Query\JoinClause;

use RZP\Base\BuilderEx;
use RZP\Models\State;
use RZP\Constants\Table;
use RZP\Models\Admin\Org;
use RZP\Models\Workflow\Base;
use RZP\Models\Workflow\Step;
use RZP\Constants\Entity as E;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Workflow\Constants;
use RZP\Models\Workflow\Action\Checker;
use RZP\Models\Workflow\Action\State\Entity as ActionStateEntity;

class Repository extends Base\Repository
{
    protected $entity = 'workflow_action';

    protected $adminFetchParamRules = [
        Entity::MAKER_ID            => 'sometimes|string|max:14',
        Entity::ENTITY_ID           => 'sometimes|string|max:14',
        Entity::MAKER_TYPE          => 'sometimes|string|max:11',
        Entity::WORKFLOW_ID         => 'sometimes|string|max:14',
        Entity::ORG_ID              => 'sometimes|string|max:14',
        self::EXPAND . '.*'         => 'filled|string|in:workflow,maker,stateChanger,tagged,owner',
        Entity::OWNER_ID            => 'sometimes|string|max:14',
        Constants::TYPE             => 'sometimes|string|max:10',
        Entity::PERMISSION          => 'sometimes|boolean|in:0,1',
        Constants::CLOSED_ACTIONS   => 'sometimes|boolean|in:0,1',
        Constants::CHECKER_ACTIONS  => 'sometimes|boolean|in:0,1',
        Constants::ACTIONS_CHECKED  => 'sometimes|boolean|in:0,1',
        Entity::TAGS                => 'sometimes|array',
        Constants::CREATED_START    => 'required_with:created_end|integer',
        Constants::CREATED_END      => 'required_with:created_start|integer',
        Constants::ORDER            => 'sometimes|string|in:asc,desc',
    ];

    public function addQueryParamTags($query, $params)
    {
        $tags = $params[Entity::TAGS];

        $tags = array_unique(array_map('mb_strtolower', array_map('str_slug', $tags)));

        $tagsTable = 'tagging_tagged';

        $query->join($tagsTable, $tagsTable . '.taggable_id', 'workflow_actions.id')
              ->where($tagsTable . '.taggable_type', '=', E::WORKFLOW_ACTION)
              ->whereIn($tagsTable . '.tag_slug', $tags)
              ->distinct();
    }

    public function addQueryParamPermission($query, $params)
    {
        $permission = Table::PERMISSION;

        $query->select(
                    Table::WORKFLOW_ACTION . '.*',
                    'permissions.name AS permission_name',
                    'permissions.description AS permission_description')
                ->join($permission, function ($join) {
                    $join->on('permissions.id', '=', 'workflow_actions.permission_id');
                });
    }

    public function addQueryParamOrgId($query, $params)
    {
        $orgId = $params['org_id'];

        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $query->OrgId($orgId);
    }

    public function addQueryParamType($query, $params)
    {
        if ($params['type'] === 'open')
        {
            $openStates = State\Name::OPEN_ACTION_STATES;

            $query->whereIn(Entity::STATE, $openStates);
        }
    }

    public function addQueryParamCreatedStart($query, $params)
    {
        if ((empty($params[Constants::CREATED_START]) === false) and
            (empty($params[Constants::CREATED_END]) === false))
        {
            $query->where('workflow_actions.created_at' , '>=', $params[Constants::CREATED_START])
                  ->where('workflow_actions.created_at', '<=', $params[Constants::CREATED_END]);
        }
    }

    public function addQueryParamCreatedEnd($query, $params)
    {
        return;
    }

    public function addQueryOrder($query)
    {
        $query->orderBy(Entity::CREATED_AT, 'desc');
    }

    public function addQueryParamOrder($query, $params)
    {
        if (empty($params[Constants::ORDER]) === false)
        {
            $query->orderBy(Entity::CREATED_AT, $params[Constants::ORDER]);
        }
    }

    /**
     * Function to add closed state of the action to the query.
     * @param $query
     * @param $params
     */
    public function addQueryParamClosedActions($query, $params)
    {
        $adminId = $this->auth->getAdmin()->getId();

        $acsDao = $this->repo->state;

        $acsTable = $acsDao->getTableName();

        $aId = $this->dbColumn(Entity::ID);
        $acsActionId = $acsDao->dbColumn(State\Entity::ACTION_ID);

        $acsState = $acsDao->dbColumn(State\Entity::NAME);

        // CLOSED is the absolute last state, We can expect unique entries.
        $acsAdminId = $acsDao->dbColumn(State\Entity::ADMIN_ID);

        $query->join($acsTable, $aId, '=', $acsActionId)
              ->where($acsState, '=', State\Name::CLOSED)
              ->where($acsAdminId, '=', $adminId);
    }

    public function fetchOpenActionsByWorkflowId(string $workflowId)
    {
        $openStates = State\Name::OPEN_ACTION_STATES;

        return $this->newQuery()
                    ->where(Entity::WORKFLOW_ID, '=', $workflowId)
                    ->whereIn(Entity::STATE, $openStates)
                    ->get();
    }

    public function fetchActionsByMakerId($makerId, $expands, $input)
    {
        $query = $this->newQuery()
                      ->with($expands);

        if (array_key_exists('state', $input))
        {
            $query = $query->where(Entity::STATE, '=', $input[Entity::STATE]);
        }

        if (array_key_exists(Entity::ID, $input))
        {
            $query = $query->where(Entity::ID, '=', $input[Entity::ID]);
        }

        return $query->where(Entity::MAKER_ID, '=', $makerId)
                    ->get();
    }

    public function getOpenActionOnEntityOperation(
        string $entityId,
        string $entityName,
        string $permissionId)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::ENTITY_NAME, $entityName)
                    ->where(Entity::PERMISSION_ID, $permissionId)
                    ->whereIn(Entity::STATE, State\Name::OPEN_ACTION_STATES)
                    ->get();
    }

    public function getApprovedActionOnEntityOperation(
        string $entityId,
        string $entityName,
        string $permissionId)
    {
        return $this->newQuery()
            ->where(Entity::ENTITY_ID, $entityId)
            ->where(Entity::ENTITY_NAME, $entityName)
            ->where(Entity::PERMISSION_ID, $permissionId)
            ->where(Entity::APPROVED, '=', 1)
            ->get();
    }

    public function getOpenActionOnEntityOperationWithPermissionList(
        string $entityId,
        string $entityName,
        array $permissionIdList)
    {
        return $this->newQuery()
            ->where(Entity::ENTITY_ID, $entityId)
            ->where(Entity::ENTITY_NAME, $entityName)
            ->whereIn(Entity::PERMISSION_ID, $permissionIdList)
            ->whereIn(Entity::STATE, State\Name::OPEN_ACTION_STATES)
            ->get();
    }

    public function getOpenActionOnEntityListOperation(
        array $entityIdList,
        string $entityName,
        string $permissionId)
    {
        return $this->newQuery()
            ->whereIn(Entity::ENTITY_ID, $entityIdList)
            ->where(Entity::ENTITY_NAME, $entityName)
            ->where(Entity::PERMISSION_ID, $permissionId)
            ->whereIn(Entity::STATE, State\Name::OPEN_ACTION_STATES)
            ->get();
    }

    /**
     * Will filter our checker actions which needs to be checked.
     * This will provide awaiting for your approval actions.
     * @param $query
     * @param $params
     */
    public function addQueryParamCheckerActions($query, $params)
    {
        $wStep = Table::WORKFLOW_STEP;

        $adminRoleIds = $this->auth->getAdmin()->roles()->allRelatedIds()->toArray();

        $query->join($wStep, function ($join) {
                    $join->on('workflow_actions.workflow_id', '=', 'workflow_steps.workflow_id')
                         ->on('workflow_actions.current_level', '=', 'workflow_steps.level');
                })
              ->where('workflow_actions.state', '=', State\Name::OPEN)
              ->whereIn('workflow_steps.role_id', $adminRoleIds);
    }

    public function getPendingActionsOnRoleIds(string $userId, PublicEntity $entity, string $permissionId, array $roleIds = [])
    {
        $workflowStepTable  = $this->repo->workflow_step->getTableName();
        $actionCheckerTable = $this->repo->action_checker->getTableName();

        $idColumn     = $this->dbColumn(Entity::ID);
        $stateColumn  = $this->dbColumn(Entity::STATE);
        $workflowId   = $this->dbColumn(Entity::WORKFLOW_ID);
        $currentLevel = $this->dbColumn(Entity::CURRENT_LEVEL);

        $wfStepIdColumn         = $this->repo->workflow_step->dbColumn(Step\Entity::ID);
        $wfStepLevelColumn      = $this->repo->workflow_step->dbColumn(Step\Entity::LEVEL);
        $wfStepRoleIdColumn     = $this->repo->workflow_step->dbColumn(Step\Entity::ROLE_ID);
        $wfStepWorkflowIdColumn = $this->repo->workflow_step->dbColumn(Step\Entity::WORKFLOW_ID);

        /** @var BuilderEx $query */
        $query = $this->newQuery()
                        ->where(Entity::ENTITY_ID, $entity->getId())
                        ->where(Entity::ENTITY_NAME, $entity->getEntity())
                        ->where($stateColumn, State\Name::OPEN)
                        ->where(Entity::PERMISSION_ID, $permissionId);

        $query->join(
            $workflowStepTable,
            function(JoinClause $join) use ($wfStepWorkflowIdColumn, $wfStepLevelColumn, $currentLevel, $workflowId)
                {
                    $join->on($workflowId, '=', $wfStepWorkflowIdColumn)
                         ->on($currentLevel, '=', $wfStepLevelColumn);
                })
              ->whereIn($wfStepRoleIdColumn, $roleIds);

        $actionCheckerId = $this->repo->action_checker->dbColumn(Checker\Entity::ID);

        $query->leftJoin(
            $actionCheckerTable,
            function (JoinClause $join) use ($idColumn, $wfStepIdColumn, $userId)
            {
                $actionCheckerStepId    = $this->repo->action_checker->dbColumn(Checker\Entity::STEP_ID);
                $actionCheckerActionId  = $this->repo->action_checker->dbColumn(Checker\Entity::ACTION_ID);
                $actionCheckerCheckerId = $this->repo->action_checker->dbColumn(Checker\Entity::CHECKER_ID);

                $join->on($idColumn, '=', $actionCheckerActionId)
                     ->on($wfStepIdColumn, '=', $actionCheckerStepId)
                     ->where($actionCheckerCheckerId, '=', $userId);

            })
              ->whereNull($actionCheckerId);

        return $query->get();
    }

    public function addQueryParamActionsChecked($query, $params)
    {
        $adminId = $this->auth->getAdmin()->getId();

        $checkerRepo = $this->repo->action_checker;

        $attributes = $this->dbColumn('*');
        $aId = $this->repo->workflow_action->dbColumn(Entity::ID);

        $cActionId = $checkerRepo->dbColumn(Checker\Entity::ACTION_ID);

        $cAdminId = $checkerRepo->dbColumn(Checker\Entity::ADMIN_ID);

        $checkerTable = Table::ACTION_CHECKER;

        $query->select($attributes)
              ->join($checkerTable, $aId, '=', $cActionId)
              ->where($cAdminId, '=', $adminId);
    }

    public function fetchUserComment(string $entityId, string $entityName)
    {
        $actionIdColumn = $this->repo->action_checker->dbColumn(Checker\Entity::ACTION_ID);
        $userCommentColumn = $this->repo->action_checker->dbColumn(Checker\Entity::USER_COMMENT);
        $actionCheckerCreatedAt = $this->repo->action_checker->dbColumn(Checker\Entity::CREATED_AT);

        $workflowActionIdColumn = $this->repo->workflow_action->dbColumn(Entity::ID);
        $workflowActionEntityId = $this->repo->workflow_action->dbColumn(Entity::ENTITY_ID);
        $workflowActionEntityName = $this->repo->workflow_action->dbColumn(Entity::ENTITY_NAME);

        return $this->newQuery()
                    ->select($userCommentColumn)
                    ->join(Table::ACTION_CHECKER, $workflowActionIdColumn, $actionIdColumn)
                    ->where($workflowActionEntityId, '=', $entityId)
                    ->where($workflowActionEntityName, '=', $entityName)
                    ->latest($actionCheckerCreatedAt)
                    ->pluck(Checker\Entity::USER_COMMENT)
                    ->first();
    }

    public function joinQueryWorkflowStep(BuilderEx $query)
    {
        $workflowStepTable = $this->repo->workflow_step->getTableName();

        if ($query->hasJoin($workflowStepTable) === true)
        {
            return;
        }

        $query->join(
            $workflowStepTable,
            function(JoinClause $join)
            {
                $workflowId           = $this->dbColumn(Entity::WORKFLOW_ID);
                $currentLevel         = $this->dbColumn(Entity::CURRENT_LEVEL);

                $wfStepLevelColumn      = $this->repo->workflow_step->dbColumn(Step\Entity::LEVEL);
                $wfStepWorkflowIdColumn = $this->repo->workflow_step->dbColumn(Step\Entity::WORKFLOW_ID);

                $join->on($workflowId, '=', $wfStepWorkflowIdColumn)
                     ->on($currentLevel, '=', $wfStepLevelColumn);
            });
    }

    /**
     * Get action entity with its relations like workflow,
     * workflow.steps, workflow.steps.role, maker, permission, etc.
     *
     * Important to note about workflow.steps relation is that
     * we may have previously executed workflow actions for which the
     * steps may have been soft deleted due to workflow steps change/edit.
     *
     * In such cases we select steps with action.created_at lying between
     * step.created_at and step.deleted_at.
     *
     * For actions that are open or executed but the workflow steps haven't
     * been modified (and hence soft deleted) since the action was created
     * we just select the rows with step.deleted_at IS NULL and obviously
     * step.created_at >= action.created_at.
     */
    public function getActionDetails(string $id, string $orgId)
    {
        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $action = $this->newQuery()
                       ->orgId($orgId)
                       ->where(Entity::ID, '=', $id);

        $actionEntity = $action->first();

        // If no entity is returned then return
        // the query builder object which will be handled
        // aptly in the service
        if (empty($actionEntity) === true)
        {
            return $action;
        }

        $relations = [
            'workflow.steps' => function ($query) use ($actionEntity)
            {
                // Get all steps where action.created_at is between
                // step.created_at AND step.deleted_at (deleted/old steps) or it is more
                // than step.created_at but step.deleted_at is NULL (active steps)
                $query->withTrashed()
                      ->where(Entity::CREATED_AT, '<=', $actionEntity->getCreatedAt())
                      ->where(function ($query) use ($actionEntity)
                        {
                            $query->where(Entity::DELETED_AT, '>=', $actionEntity->getCreatedAt())
                                  ->orWhereNull(Entity::DELETED_AT);
                        });
            },
            'workflow.steps.role',
            'maker' => function ($query) use ($actionEntity)
            {
                // Since only Admin uses soft deletes
                if ($actionEntity->getMakerType() === MakerType::ADMIN)
                {
                    $query->withTrashed();
                }
            },
            'stateChanger' => function ($query) use ($actionEntity)
            {
                // Since only Admin uses soft deletes
                if ($actionEntity->getMakerType() === MakerType::ADMIN)
                {
                    $query->withTrashed();
                }
            },
            'stateChangerRole' => function ($query)
            {
                $query->withTrashed();
            },
            'owner' => function ($query)
            {
                $query->withTrashed();
            },
            'permission',
            'tagged'
        ];

        return $action->with($relations)
                      ->get();
    }

    public function getActionDetailsPublic(string $id, string $orgId)
    {
        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $action = $this->newQuery()
                       ->orgId($orgId)
                       ->where(Entity::ID, '=', $id);

        $actionEntity = $action->first();

        // If no entity is returned then return
        // the query builder object which will be handled
        // aptly in the service
        if (empty($actionEntity) === true)
        {
            return $action;
        }

        $relations = [
            'workflow',
            'workflow.steps' => function ($query) use ($actionEntity)
            {
                $query->withTrashed()
                      ->where(Entity::CREATED_AT, '<=', $actionEntity->getCreatedAt())
                      ->where(function ($query) use ($actionEntity)
                      {
                          $query->where(Entity::DELETED_AT, '>=', $actionEntity->getCreatedAt())
                                ->orWhereNull(Entity::DELETED_AT);
                      });
            },
            'workflow.steps.role',
            'workflow.steps.checkers' => function ($query) use ($actionEntity)
            {
                $query->where(Entity::ACTION_ID, $actionEntity->getId());
            },
            'workflow.steps.checkers.checker',
        ];

        return $action->with($relations)
                      ->get();
    }

    public function fetchWorkflowAction(
        string $entityId,
        string $entityName,
        string $permissionId)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::ENTITY_NAME, $entityName)
                    ->where(Entity::PERMISSION_ID, $permissionId)
                    ->get();
    }

    protected function filterByApprovalTimestampRange($input, $query)
    {
        if (isset($input[Constants::APPROVAL_START_TIME]) === true)
        {
            $query->where(Table::WORKFLOW_ACTION . '.' . Entity::UPDATED_AT, '>=', (int)$input[Constants::APPROVAL_START_TIME]);
        }

        if (isset($input[Constants::APPROVAL_END_TIME]) === true)
        {
            $query->where(Table::WORKFLOW_ACTION . '.' . Entity::UPDATED_AT, '<=', (int)$input[Constants::APPROVAL_END_TIME]);
        }
    }

    protected function getCountAndSkip($input): array
    {
        $count = $input['count'] ?? 20;

        if ($count <= 1) {
            $count = 1;
        }

        if ($count >= 100) {
            $count = 100;
        }

        $skip = $input['skip'] ?? 0;

        if ($skip <= 0) {
            $skip = 0;
        }

        return [$count, $skip];
    }

    public function getActionIdsForRiskAudit($merchantId, $input)
    {
        $riskAuditWfIds = Constants::getRiskAuditWorkflowIds();

        $workflowIds = $input[Constants::WORKFLOW_IDS] ?? $riskAuditWfIds;

        list($count, $skip) = $this->getCountAndSkip($input);

        $workflowIds = array_filter($workflowIds, function($value) use ($riskAuditWfIds)
        {
            return in_array($value, $riskAuditWfIds) === true;
        });

        $query = $this->newQuery()
                    ->select(Table::WORKFLOW_ACTION . '.' . Entity::ID)
                    ->distinct()
                    ->where(Entity::ENTITY_NAME, 'merchant')
                    ->where(Entity::ENTITY_ID, $merchantId)
                    ->where(Entity::STATE, ActionStateEntity::EXECUTED)
                    ->where(Entity::APPROVED, 1)
                    ->whereIn(Entity::WORKFLOW_ID, $workflowIds);

        $this->filterByApprovalTimestampRange($input, $query);

        return $query->skip($skip)->take($count)->get()->pluck(Entity::ID)->toArray();
    }

    public function fetchLastUpdatedWorkflowActionInPermissionIds(
        string $entityId,
        string $entityName,
        array $permissionIdList)
    {
        return $this->newQuery()
            ->where(Entity::ENTITY_ID, $entityId)
            ->where(Entity::ENTITY_NAME, $entityName)
            ->whereIn(Entity::PERMISSION_ID, $permissionIdList)
            ->orderBy(Entity::UPDATED_AT, 'desc')
            ->first();
    }

    public function fetchWorkFlowActionForWorkFlows(
        int $limit,
        int $skip,
        array $adminIdList,
        array $permissionIdList,
        array $workflowIdList,
        int $currenTimestamp,
        int $timestamp)
    {
        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
            ->select(Entity::ENTITY_ID)
            ->whereIn(Entity::STATE_CHANGER_ID, $adminIdList)
            ->whereIn(Entity::PERMISSION_ID, $permissionIdList)
            ->whereIn(Entity::WORKFLOW_ID, $workflowIdList)
            ->where(Entity::APPROVED, '=', 1)
            ->where(Entity::CREATED_AT, '<=', $currenTimestamp)
            ->where(Entity::CREATED_AT, '>=', $timestamp)
            ->take($limit)
            ->skip($skip)
            ->distinct()
            ->get()
            ->pluck(Entity::ENTITY_ID)
            ->toarray();
    }
}

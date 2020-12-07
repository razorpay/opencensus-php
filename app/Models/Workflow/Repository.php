<?php

namespace RZP\Models\Workflow;

use RZP\Models\Admin;
use RZP\Base\BuilderEx;
use RZP\Constants\Table;
use RZP\Models\Workflow\Step;
use RZP\Models\Base\PublicCollection;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Workflow\PayoutAmountRules\Entity as ParEntity;

class Repository extends Base\Repository
{
    protected $entity = 'workflow';

    const DEFAULT_FETCH_LIMIT = 2000;
    const DEFAULT_FETCH_OFFSET = 0;

    protected $adminFetchParamRules = [
        Entity::ORG_ID        => 'sometimes|string|max:14',
    ];

    public function addQueryParamPermissionId($query, $permissionId)
    {
        if (empty($permissionId) === false)
        {
            $query->whereIn(Entity::ID, function ($q) use ($permissionId) {
                                            $q->select(Step\Entity::WORKFLOW_ID)
                                              ->from(Admin\Org\Entity::WORKFLOW_PERMISSIONS)
                                              ->where(Entity::PERMISSION_ID, $permissionId);
            });
        }

        return $query;
    }

    /**
     * Fetches workflows by orgId and permission name
     *
     * @param string $orgId
     * @param array $params
     * @return PublicCollection
     */
    public function findByOrgIdAndPermissionName(string $orgId, array $params)
    {
        $limit = $params[self::COUNT] ?? self::DEFAULT_FETCH_LIMIT;

        $offset = $params[self::SKIP] ?? self::DEFAULT_FETCH_OFFSET;

        $permissionName = $params[Entity::PERMISSIONS] ?? null;

        $query = $this->newQuery()
                      ->where(Entity::ORG_ID, '=', $orgId);

        if (empty($permissionName) === false)
        {
            $permissionId = $this->repo->permission->retrieveIdsByNamesAndOrg($permissionName, $orgId);

            $query = $this->addQueryParamPermissionId($query, $permissionId);
        }

        $results = $query->skip($offset)
                         ->take($limit)
                         ->get();

        return $results;
    }

    public function fetchWorkflowsByPermissionsOrgAndMerchant(
        string $permissionId,
        string $orgId,
        string $merchantId = null,
        array $relations = [])
    {
        /** @var BuilderEx $query */
        $query = $this->newQuery()
                      ->join(Table::WORKFLOW_PERMISSION, Entity::ID, '=', 'workflow_permissions.workflow_id')
                      ->with($relations)
                      ->where(Entity::ORG_ID, '=', $orgId)
                      ->where('workflow_permissions.permission_id', $permissionId);

        if ($merchantId !== null)
        {
            $query->merchantId($merchantId);
        }

        return $query->get();
    }

    public function fetchWorkflow(Step\Entity $step)
    {
        $workflowId = $step->getWorkflowId();

        $workflow = $this->findOrFail($workflowId);

        $step->workflow()->associate($workflow);

        return $workflow;
    }

    public function getWorkflowIdsForPermissionsAndOrgId(
        string $orgId, array $permissionIds)
    {
        $pid = 'workflow_permissions.permission_id';

        $wOrgId = $this->dbColumn(Entity::ORG_ID);
        $wid = $this->dbColumn(Entity::ID);

        return $this->newQuery()
                    ->join(Table::WORKFLOW_PERMISSION, $wid, '=', 'workflow_permissions.workflow_id')
                    ->where($wOrgId, '=', $orgId)
                    ->whereIn($pid, $permissionIds)
                    ->whereNull(Entity::DELETED_AT)
                    ->pluck(Entity::ID);
    }

    public function getWorkflowsForPermissionNameAndCategory($permissionName, $permissionCategory, $orgId, $merchantId)
    {
        $permissionIdColumnName = $this->repo->permission->dbColumn(Admin\Permission\Entity::ID);

        $permissionMapTable = Table::PERMISSION_MAP;

        $permissionId =  $this->repo->permission->newQuery()
                                                ->join($permissionMapTable,
                                                       $permissionIdColumnName,
                                                       '=',
                                                       $permissionMapTable . '.'. Entity::PERMISSION_ID)
                                                ->where($permissionMapTable . '.' . ParEntity::ENTITY_ID, '=', $orgId)
                                                ->where($permissionMapTable . '.' . ParEntity::ENTITY_TYPE,
                                                        '=',
                                                        EntityConstants::ORG)
                                                ->where(Admin\Permission\Entity::NAME, $permissionName)
                                                ->where(Admin\Permission\Entity::CATEGORY, $permissionCategory)
                                                ->pluck(Entity::ID)
                                                ->first();

        if (empty($permissionId) === true)
        {
            return new PublicCollection();
        }

        // Implicit check for workflow in the organisation against permission ids.
        $workflows = $this->repo->workflow->fetchWorkflowsByPermissionsOrgAndMerchant($permissionId,
                                                                                      $orgId,
                                                                                      $merchantId);

        return $workflows;
    }
}

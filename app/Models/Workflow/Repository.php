<?php

namespace RZP\Models\Workflow;

use RZP\Models\Admin;
use RZP\Base\BuilderEx;
use RZP\Constants\Table;
use RZP\Models\Workflow\Step;

class Repository extends Base\Repository
{
    protected $entity = 'workflow';

    const DEFAULT_FETCH_LIMIT = 10;
    const DEFAULT_FETCH_OFFSET = 0;

    protected $adminFetchParamRules = [
        Entity::ORG_ID        => 'sometimes|string|max:14',
    ];

    public function findByOrgId(string $orgId, $params)
    {
        $limit = $params[self::COUNT] ?? self::DEFAULT_FETCH_LIMIT;

        $offset = $params[self::SKIP] ?? self::DEFAULT_FETCH_OFFSET;

        $permissionName = $params[\RZP\Models\Workflow\Entity::PERMISSIONS] ?? null;

        $query = $this->repo->permission->newQuery()
            ->where(Admin\Permission\Entity::NAME, $permissionName);

        $permission = $query->pluck(Entity::ID)->toArray();

        if(empty($permission) === false)
        {
            $permissionId = $permission[0];
        }

        $query = $this->newQuery()
                      ->where(Entity::ORG_ID, '=', $orgId);

        if(empty($permission) === false)
        {
            $query->whereIn('id', function ($q) use ($permissionId) {
                                      $q->select(Step\Entity::WORKFLOW_ID)
                                        ->from(Admin\Org\Entity::WORKFLOW_PERMISSIONS)
                                        ->where(Entity::PERMISSION_ID, $permissionId);
                                  });
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

}

<?php

namespace RZP\Models\Workflow;

use RZP\Models\Admin;
use RZP\Base\BuilderEx;
use RZP\Constants\Table;
use RZP\Models\Workflow\Step;

class Repository extends Base\Repository
{
    protected $entity = 'workflow';

    protected $adminFetchParamRules = [
        Entity::ORG_ID        => 'sometimes|string|max:14',
    ];

    public function findByOrgId(string $orgId, $permission = null, $limit = 10, $offset = 0)
    {
        if($permission)
        {
            $query =  $this->newQuery()
                ->orderBy(Entity::MERCHANT_ID)
                ->take($limit)
                ->skip($offset)
                ->where(Entity::ORG_ID, '=', $orgId)
                ->whereHas('permissions', function($q) use($permission)
                {
                    $q->where('name', '=', $permission);
                })->get();
        }
        else
        {
            $query =  $this->newQuery()
                ->where(Entity::ORG_ID, '=', $orgId)
                ->get();
        }

        return $query;
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

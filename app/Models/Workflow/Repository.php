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

    public function findByOrgId(string $orgId)
    {
        return $this->newQuery()
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->get();
    }

    public function fetchWorkflowsByPermissionsAndOrgId(string $permissionId, string $orgId, array $relations = [])
    {
        return $this->newQuery()
                    ->join(Table::WORKFLOW_PERMISSION, Entity::ID, '=', 'workflow_permissions.workflow_id')
                    ->with($relations)
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->where('workflow_permissions.permission_id', $permissionId)
                    ->get();
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

    public function fetchBankingWorkflowSummaryForPermissionId(string $permissionId)
    {
        $permissionTable               = $this->repo->permission->getTableName();
        $workflowStepTable             = $this->repo->workflow_step->getTableName();
        $workflowPayoutAmountRuleTable = $this->repo->workflow_payout_amount_rules->getTableName();

        // Workflow table columns
        $workflowId = $this->dbColumn(Entity::ID);
        $orgId      = $this->dbColumn(Entity::ORG_ID);

        // Workflow Permission table columns
        $workflowPermissionsWorkflowId   = Table::WORKFLOW_PERMISSION. '.workflow_id';
        $workflowPermissionsPermissionId = Table::WORKFLOW_PERMISSION. '.permission_id';

        /** @var BuilderEx $query */
        $query = $this->newQuery()
                      ->with('steps', 'steps.role', 'payoutAmountRules')
                      ->join(Table::WORKFLOW_PERMISSION, $workflowId, '=', $workflowPermissionsWorkflowId)
                      ->where($orgId, Admin\Org\Entity::RAZORPAY_ORG_ID)
                      ->whereNull(Entity::DELETED_AT)
                      ->where($workflowPermissionsPermissionId, $permissionId);

        return $query->get();
    }
}

<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use Illuminate\Database\Query\JoinClause;
use RZP\Models\Admin;
use RZP\Base\BuilderEx;
use RZP\Constants\Table;
use RZP\Models\Workflow\Base;

class Repository extends Base\Repository
{
    protected $entity = 'workflow_payout_amount_rules';

    public function fetchWorkflowRulesForMerchant(string $merchantId)
    {
        return $this->newQuery()
            ->merchantId($merchantId)
            ->get();
    }

    public function fetchAllWorkflowRulesForOrg($limit, $offset, $orgId)
    {
        return $this->newQuery()
//            ->whereHas('workflow', function($q) use($orgId)
//            {
//                $q->where(Entity::ORG_ID, $orgId);
//            })
            ->get()
            ->groupBy(Entity::MERCHANT_ID)
            ->slice($offset,$limit);
    }

    public function fetchBankingWorkflowSummaryForPermissionId(string $permissionId, string $merchantId)
    {
        /** @var BuilderEx $query */
        $query = $this->newQuery();

        $query->with('workflow', 'workflow.steps', 'workflow.steps.role', 'workflow.steps.checkers')
              ->leftJoin(Table::WORKFLOW_PERMISSION,
                  function(JoinClause $join) use ($permissionId)
                  {
                      $workflowId = $this->dbColumn(Entity::WORKFLOW_ID);

                      // Workflow Permission table columns
                      $workflowPermissionsWorkflowId   = Table::WORKFLOW_PERMISSION. '.workflow_id';
                      $workflowPermissionsPermissionId = Table::WORKFLOW_PERMISSION. '.permission_id';

                      $join->on($workflowId, '=', $workflowPermissionsWorkflowId)
                           ->where($workflowPermissionsPermissionId, '=', $permissionId);
                  })
              ->merchantId($merchantId)
              ->whereNull(Entity::DELETED_AT);

        return $query->get();
    }
}

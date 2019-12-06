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

    public function fetchAllWorkflowRulesForOrg($params, $orgId)
    {
        // Taking count and skip params here instead of using inbuilt fetch() because fetch() returns collection
        // which cannot be filtered further according to merchant to which it belongs which is required here.
        $merchantId = $params[Entity::MERCHANT_ID] ?? null;

        $limit = $params[self::COUNT] ?? Entity::DEFAULT_FETCH_LIMIT;

        $offset = $params[self::SKIP] ?? Entity::DEFAULT_FETCH_OFFSET;

        $query = $this->newQuery()
            ->with('steps','steps.role')
            ->whereHas('workflow', function($q) use($orgId)
            {
                $q->where(Entity::ORG_ID, $orgId);
            });

        // Filter by merchant if merchant id passed as query parameter
        if($merchantId)
        {
            $query = $query->merchantId($merchantId);
        }

        $results = $query->get()
            ->groupBy(Entity::MERCHANT_ID);

        // Implementing pagination
        $results = $results->slice($offset,$limit);

        return $results;
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

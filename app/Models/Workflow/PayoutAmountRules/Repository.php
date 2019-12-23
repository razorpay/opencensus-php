<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Models\Admin;
use RZP\Base\BuilderEx;
use RZP\Constants\Table;
use RZP\Models\Workflow\Base;
use Illuminate\Database\Query\JoinClause;

class Repository extends Base\Repository
{
    protected $entity = 'workflow_payout_amount_rules';

    const DEFAULT_FETCH_LIMIT = 10;
    const DEFAULT_FETCH_OFFSET = 0;

    public function fetchWorkflowRulesForMerchant(string $merchantId)
    {
        $query =  $this->newQuery();

        // If adminAuth is used pass retrieve additional infomration such as steps and roles in the workflow
        if ($this->app['basicauth']->isAdminAuth())
        {
            $query = $query->with('steps','steps.role');
        }

        $query->merchantId($merchantId);

        $results = $query->get();

        return $results;
    }

    public function getMerchantIdsForWorkflowPermission($orgId, $params)
    {
        // Taking count and skip params here instead of using inbuilt fetch() because fetch() returns collection
        // which cannot be filtered further according to permission which is required here.
        $limit = $params[self::COUNT] ?? self::DEFAULT_FETCH_LIMIT;

        $offset = $params[self::SKIP] ?? self::DEFAULT_FETCH_OFFSET;

        $permission = $params[\RZP\Models\Workflow\Entity::PERMISSIONS] ?? Admin\Permission\Name::CREATE_PAYOUT;

        $merchantId = $params[Entity::MERCHANT_ID] ?? null;

        $query = $this->repo->permission->newQuery()
                                        ->where(Admin\Permission\Entity::NAME, $permission);

        $permissionIdArray = $query->pluck(Entity::ID)
                                   ->toArray();

        $permissionId = $permissionIdArray[0];

        $query = $this->repo->workflow->newQuery()
                                        ->whereIn('id', function ($q) use ($permissionId) {
                                                            $q->select(Entity::WORKFLOW_ID)
                                                              ->from(Table::WORKFLOW_PERMISSION)
                                                              ->where(Entity::PERMISSION_ID, $permissionId);
                                                        })
                                        ->distinct();

        if (empty($merchantId) === false)
        {
            $query->merchantId($merchantId);
        }
        else
        {
            $query->skip($offset)
                  ->take($limit);
        }

        $results = $query->pluck(Entity::MERCHANT_ID);

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

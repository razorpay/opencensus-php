<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use RZP\Models\Admin;
use RZP\Base\BuilderEx;
use RZP\Constants\Table;
use RZP\Models\Workflow\Base;
use RZP\Models\Base\Collection;
use RZP\Models\Base\PublicCollection;
use Illuminate\Database\Query\JoinClause;

class Repository extends Base\Repository
{
    protected $entity = 'workflow_payout_amount_rules';

    const DEFAULT_FETCH_LIMIT = 10;
    const DEFAULT_FETCH_OFFSET = 0;

    /**
     * Gets payout amount rules for a single merchant
     *
     * @param string $merchantId
     * @return array
     */
    public function fetchWorkflowRulesForMerchant(string $merchantId, array $relations = [])
    {
        return $this->newQuery()
                    ->with($relations)
                    ->merchantId($merchantId)
                    ->get();
    }

    /**
     * Returns list of merchant ids which have a workflow with create_payout permission
     * On selecting a certain merchant id, a second api shall be called which will return the rules for that merchant
     *
     * @param string $orgId
     * @param array $params
     * @return Collection
     */
    public function getMerchantIdsForCreatePayoutWorkflowPermission($orgId, $params)
    {
        // Taking count and skip params here instead of using inbuilt fetch() because fetch() returns collection
        // which cannot be filtered further according to permission which is required here.
        $limit = $params[self::COUNT] ?? self::DEFAULT_FETCH_LIMIT;

        $offset = $params[self::SKIP] ?? self::DEFAULT_FETCH_OFFSET;

        $merchantId = $params[Entity::MERCHANT_ID] ?? null;

        $query = $this->repo->permission->newQuery()
                                        ->where(Admin\Permission\Entity::NAME, Admin\Permission\Name::CREATE_PAYOUT)
                                        ->where(Admin\Permission\Entity::CATEGORY, Admin\Permission\Category::PAYOUTS);

        $permissionIdArray = $query->pluck(Entity::ID)->toArray();

        $createPayoutPermissionId = $permissionIdArray[0];

        $query = $this->repo->workflow->newQuery()
                                      ->whereIn(Entity::ID,
                                                function ($query) use ($createPayoutPermissionId)
                                                {
                                                    $query->select(Entity::WORKFLOW_ID)
                                                          ->from(Table::WORKFLOW_PERMISSION)
                                                          ->where(Entity::PERMISSION_ID, $createPayoutPermissionId);
                                                })
                                      ->orgId($orgId)
                                      ->distinct();

        if (empty($merchantId) === false)
        {
            $query->merchantId($merchantId);
        }

        $query->skip($offset)
              ->take($limit);

        $resultArray = $query->pluck(Entity::MERCHANT_ID);

        $results = new PublicCollection($resultArray);

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

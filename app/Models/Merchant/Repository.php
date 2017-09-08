<?php

namespace RZP\Models\Merchant;

use Closure;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Base\Common;
use RZP\Constants\Mode;
use RZP\Models\Pricing;
use RZP\Constants\Table;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Balance;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    const SUB_ACCOUNTS_ONLY_VALUE = '1';

    protected $entity = 'merchant';

    protected $sharedMerchant = null;

    protected $appFetchParamRules = [
        Entity::ACTIVATED               => 'sometimes|boolean',
        Entity::HOLD_FUNDS              => 'sometimes|boolean',
        Entity::LIVE                    => 'sometimes|boolean',
        Entity::EMAIL                   => 'sometimes|string|max:255',
        Entity::PARENT_ID               => 'sometimes|string|size:14',
        Entity::CATEGORY                => 'sometimes|string|max:4',
        Entity::CATEGORY2               => 'sometimes|string|max:20',
        Entity::INTERNATIONAL           => 'sometimes|boolean',
        Entity::RECEIPT_EMAIL_ENABLED   => 'sometimes|boolean',
        Entity::METHODS                 => 'sometimes|string',
        Entity::PRICING_PLAN_ID         => 'sometimes|string',
        Entity::FEE_BEARER              => 'sometimes|in:platform,customer',
        Entity::FEE_MODEL               => 'sometimes|in:prepaid,postpaid',
        Entity::HOLD_FUNDS              => 'sometimes|in:0,1',
        Entity::RISK_RATING             => 'sometimes|integer|max:5|min:1',
    ];

    protected $adminFetchParamRules = [
        EsRepository::SEARCH_HITS       => 'filled|boolean',
        EsRepository::QUERY             => 'filled|string|min:2|max:100',
        Entity::ORG_ID                  => 'sometimes|string|size:14',
        Entity::ACCOUNT_STATUS          => 'filled|string|in:all,suspended,archived,activated,pending,dead',
        Entity::SUB_ACCOUNTS            => 'filled|custom',
        Entity::GROUPS                  => 'sometimes|array',
        Entity::ADMINS                  => 'sometimes|array|min:1|max:1',
    ];

    protected function validateSubAccounts($attribute, $value)
    {
        ($value === self::SUB_ACCOUNTS_ONLY_VALUE) or Entity::verifyIdAndStripSign($value);
    }

    public function fetchActivatedMerchantsBeforeTimestamp(
      int $limit,
      int $skip,
      int $end,
      array $merchantIds = []): Base\PublicCollection
    {
        $query = $this->newQuery()
                    ->where(Entity::ACTIVATED, '=', 1)
                    ->where(Entity::ACTIVATED_AT, '<=', $end)
                    ->take($limit)
                    ->skip($skip)
                    ->with('merchantDetail');

        if (empty($merchantIds) === false)
        {
            $query = $query->whereIn(Entity::ID, $merchantIds);
        }

        return $query->get();
    }

    public function getSharedAccount()
    {
        if ($this->sharedMerchant === null)
        {
            $this->sharedMerchant = $this->newQuery()
                                         ->where(Entity::ID, '=', Account::SHARED_ACCOUNT)
                                         ->firstOrFail();
        }

        return $this->sharedMerchant;
    }

    public function getPricingPlanOrFailPublic($merchant)
    {
        $pricing = $merchant->getPricingPlanId();

        if ($pricing === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_NOT_DEFINED_FOR_MERCHANT);
        }

        return (new Pricing\Repository)->getPricingPlanById($pricing, true, true);
    }

    public function fetchMerchantsWithPositiveBalance()
    {
        return $this->newQuery()
                    ->whereHas('balance', function($q)
                    {
                        $q->where('balance', '>', 0);
                    })->get();
    }

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

    public function fetchRecentMerchants()
    {
        // 00:00 Today
        $today = \Carbon\Carbon::today("Asia/Kolkata")->getTimestamp();

        $start = \Carbon\Carbon::today("Asia/Kolkata")->subWeeks(3);

        return $this->newQuery()->whereBetween(Entity::CREATED_AT, [$start, $today]);
    }

    public function getFewMerchantsWithNoCorrespondingScheduleTasks()
    {
        $mercIds = $this->db->select(
            'SELECT DISTINCT id
             FROM merchants
                WHERE merchants.id NOT IN
                    (SELECT DISTINCT merchant_id
                     FROM schedule_tasks)
                LIMIT 1000');

        $mercIds2 = array_column($mercIds, 'id');

        return $this->newQuery()
                    ->whereIn(Entity::ID, $mercIds2)
                    ->get();
    }
    public function getCountOfMerchantsActivatedBetween($from, $to)
    {
        return $this->newQuery()
                    ->whereBetween(Entity::ACTIVATED_AT, [$from, $to])
                    ->count();
    }

    public function addQueryParamMethods($query, $params)
    {
        $query->join(
            $this->repo->methods->getTableName(),
            function ($join) use ($params)
            {
                $merchantId = $this->repo->merchant->dbColumn(Merchant\Entity::ID);
                $methodsMerchantId = $this->repo->methods->dbColumn(Methods\Entity::MERCHANT_ID);

                $methods = json_decode($params[Entity::METHODS], true);

                $join->on($methodsMerchantId, '=', $merchantId);

                foreach ($methods as $method => $value)
                {
                    $queryValue = null;

                    if ($value === 'true')
                        $queryValue = '1';
                    else if ($value === 'false')
                        $queryValue = '0';

                    $join->where($method, '=', $queryValue);
                }
            });

        $query->select($query->getModel()->getTable().'.*');
    }

    protected function addQueryParamFeeBearer($query, $params)
    {
        $feeBearer = $this->dbColumn(Entity::FEE_BEARER);

        $query->where($feeBearer, '=', FeeBearer::getValueForBearerString($params[Entity::FEE_BEARER]));
    }

    protected function addQueryParamFeeModel($query, $params)
    {
        $feeModel = $this->dbColumn(Entity::FEE_MODEL);

        $query->where($feeModel, '=', FeeModel::getValueForFeeModelString($params[Entity::FEE_MODEL]));
    }


    /**
     * Returns all the emails and names for all Merchants
     * No limits
     * @return [type] [description]
     */
    public function fetchAllMerchantContacts()
    {
        return $this->newQuery()
                    ->all(['name', 'email', 'transaction_report_email']);
    }

    public function fetchMerchantWhereTestBankIsNull()
    {
        return $this->newQueryWithConnection(Mode::TEST)
                    ->has('bankAccount', '<', 1)
                    ->get();
    }

    public function fetchAllLiveMerchants()
    {
        return $this->newQuery()
                    ->where(Entity::LIVE, '=', 1);
    }

    public function fetchMerchantFromEntity($entity)
    {
        if ($entity->hasRelation('merchant'))
        {
            return $entity->merchant;
        }

        $merchantId = $entity->getMerchantId();

        $merchant = $this->findOrFail($merchantId);

        $entity->merchant()->associate($merchant);

        return $merchant;
    }

    /**
     * Fetches merchant records which have features assigned in chunks of 200
     * records and passes that to the closure argument for processing
     * @param  Closure $processData Function to process the merchant records
     */
    public function fetchMerchantsWithoutFeatureEntries()
    {
        $merchantIds = $this->db->select(
           'SELECT DISTINCT id
            FROM merchants
            WHERE features IS NOT NULL
              AND merchants.id NOT IN
                (SELECT DISTINCT merchants.id
                 FROM merchants
                 JOIN features ON merchants.id = features.entity_id) LIMIT 200');

        $merchantIds = json_decode(json_encode($merchantIds), true);

        $merchantIds = array_map(function ($mid)
        {
            return $mid['id'];
        }, $merchantIds);

        return $this->newQuery()
                    ->whereIn(Entity::ID, $merchantIds)
                    ->get();
    }

    public function fetchMerchantsByOrgId($orgId)
    {
        return $this->newQuery()
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->get();
    }

    public function fetchReferredMerchants($merchantId)
    {
        $tag = "ref-$merchantId";

        return $this->newQuery()
                    ->select(
                        Entity::ID,
                        Entity::NAME,
                        Entity::ACTIVATED,
                        Entity::CREATED_AT,
                        Entity::EMAIL)
                    ->withAnyTag($tag)
                    ->whereNull(Entity::SUSPENDED_AT)
                    ->get();
    }

    /**
     * @deprecated Ref: #4216
     */
    public function fetchMerchantsByFilter(array $merchantIds, array $input)
    {
        $merchantCreatedAt = $this->repo->merchant->dbColumn(Entity::CREATED_AT);
        $merchantUpdatedAt = $this->repo->merchant->dbColumn(Entity::CREATED_AT);

        $merchantId = $this->repo
                           ->merchant_detail
                           ->dbColumn(Merchant\Detail\Entity::MERCHANT_ID);

        $submittedAt = $this->repo
                            ->merchant_detail
                            ->dbColumn(Merchant\Detail\Entity::SUBMITTED_AT);

        $stepsFinished = $this->repo
                              ->merchant_detail
                              ->dbColumn(Merchant\Detail\Entity::STEPS_FINISHED);

        $activationProgress = $this->repo
                                   ->merchant_detail
                                   ->dbColumn(Merchant\Detail\Entity::ACTIVATION_PROGRESS);

        $submitted = $this->repo
                          ->merchant_detail
                          ->dbColumn(Merchant\Detail\Entity::SUBMITTED);

        $updatedAt = $this->repo
                          ->merchant_detail
                          ->dbColumn(Merchant\Detail\Entity::UPDATED_AT);

        $query = $this->newQuery()
                      ->with('features')
                      ->select(Entity::ID,
                               Entity::NAME,
                               Entity::EMAIL,
                               Entity::ACTIVATED,
                               Entity::PARENT_ID,
                               $merchantCreatedAt,
                               $merchantUpdatedAt,
                               Entity::ARCHIVED_AT,
                               Entity::SUSPENDED_AT,
                               $stepsFinished,
                               $activationProgress,
                               $submitted,
                               $submittedAt,
                               $updatedAt)
                      ->join(Table::MERCHANT_DETAIL, Entity::ID, '=', $merchantId)
                      ->whereIn(Entity::ID, $merchantIds);

        $this->modifyQuery($query, $input);

        // Marketplace accounts filter
        if (empty($input['sub_accounts']) === false)
        {
            if ($input['sub_accounts'] === '1')
            {
                $query = $query->whereNotNull(Entity::PARENT_ID);
            }
            else
            {
                $query = $query->where(Entity::PARENT_ID, $input['sub_accounts']);
            }
        }

        return $query->get();
    }

    public function fetchByAccountIdAndMerchant(string $accountId, Entity $marketplace)
    {
        AccountEntity::verifyIdAndStripSign($accountId);

        $account =  $this->newQuery()
                         ->where(Entity::PARENT_ID, $marketplace->getId())
                         ->find($accountId);

        if ($account !== null)
        {
            $account->parent()->associate($marketplace);
        }

        return $account;
    }

    /**
     * Used for Marketplace, dashboard:
     * Fetch entities for a CSV report of all linked accounts under a marketplace merchant
     *
     * @todo: Move this to Merchant/Account/Repository when account onboarding is merged.
     *
     * @param       $merchantId
     * @param       $from       (unused)
     * @param       $to         (unused)
     * @param       $count
     * @param       $skip
     * @param array $relations
     *
     * @return mixed
     */
    public function fetchEntitiesForReport($merchantId, $from, $to, $count, $skip, $relations = [])
    {
        $query =  $this->newQuery()
                       ->where(Entity::PARENT_ID, $merchantId);

        if (count($relations) > 0)
        {
            $query->with(...$relations);
        }

        return $query->take($count)
                     ->skip($skip)
                     ->get();
    }

    /**
     * @deprecated Ref: #4216
     */
    protected function modifyQuery($query, array $input)
    {
        $submittedAt = $this->repo
                            ->merchant_detail
                            ->dbColumn(Merchant\Detail\Entity::SUBMITTED_AT);

        switch (true)
        {
            case (empty($input['suspended']) === false):
                $query = $query->whereNotNull(Entity::SUSPENDED_AT);
                break;

            case (empty($input['archived']) === false):
                $query = $query->whereNotNull(Entity::ARCHIVED_AT);
                break;

            case (empty($input['activated']) === false):
                $query = $query->whereNotNull(Entity::ACTIVATED_AT)
                               ->whereNull(Entity::SUSPENDED_AT)
                               ->whereNull(Entity::ARCHIVED_AT);
                break;

            case (empty($input['pending']) === false):
                $query = $query->whereNull(Entity::ACTIVATED_AT)
                               ->whereNotNull($submittedAt)
                               ->whereNull(Entity::SUSPENDED_AT)
                               ->whereNull(Entity::ARCHIVED_AT);
                break;

            case (empty($input['dead']) === false):
                $query = $query->where($merchantCreatedAt, '<', time() - 24 * 7 * 3600)
                               ->whereNull($submittedAt)
                               ->whereNull(Entity::SUSPENDED_AT)
                               ->whereNull(Entity::ARCHIVED_AT);
                break;

            default:
                $query = $query->whereNull(Entity::ARCHIVED_AT)
                               ->whereNull(Entity::SUSPENDED_AT);
                break;
        }
    }

    /**
     * Modifies query to eager load details, admins, groups and features.
     * Also projects to find only needed attributes.
     *
     * @param \RZP\Base\BuilderEx $query
     *
     */
    protected function modifyQueryForIndexing(\RZP\Base\BuilderEx $query)
    {
        $detailSelector = function ($query)
                          {
                              $fields = $this->esRepo->getMerchantDetailIndexedFields();

                              $query->select($fields);
                          };

        $groupSelector = function ($query)
                         {
                              $fields = $this->esRepo->getGroupIndexedFields();

                              $query->select($fields);
                         };

        $adminSelector = function ($query)
                         {
                              $fields = $this->esRepo->getAdminIndexedFields();

                              $query->select($fields);
                         };

        $with = [
            camel_case(Entity::MERCHANT_DETAIL) => $detailSelector,
            Entity::GROUPS                      => $groupSelector,
            Entity::ADMINS                      => $adminSelector,
            Entity::FEATURES                    => function () {},
        ];

        //
        // Following 5 queries are run in total (dumps from indexing command):
        //
        // - SELECT * FROM merchants
        //
        // - SELECT <fields> FROM merchant_details
        //   WHERE merchant_details.merchant_id IN (?)
        //
        // - SELECT <fields> FROM groups
        //   INNER JOIN merchant_map
        //   ON groups.id = merchant_map.entity_id
        //   WHERE merchant_map.merchant_id IN (?)
        //      AND merchant_map.entity_type = ?
        //      AND groups.deleted_at IS NULL
        //
        // - SELECT <fields> FROM admins
        //   INNER JOIN merchant_map
        //   ON admins.id = merchant_map.entity_id
        //   WHERE merchant_map.merchant_id IN (?)
        //      AND merchant_map.entity_type = ?
        //      AND admins.deleted_at IS NULL
        //
        // - SELECT * FROM features
        //   WHERE features.entity_id IN (?)
        //      AND features.entity_type = ?
        //

        $query->with($with);
    }

    /**
     * Overrides method to fill in formatted data in merchant index against
     * given merchant entity. Merchant entity has some relations and so this
     * handling.
     *
     * @param Base\PublicEntity $entity
     *
     * @return array
     */
    protected function serializeForIndexing(Base\PublicEntity $entity): array
    {
        $serialized = parent::serializeForIndexing($entity);

        //
        // The serialized merchant document in ES contains following
        // additional values:
        // - List of tag names
        // - List of admins who have access to this merchant,
        // - List of groups which this merchant belongs to as well as their
        //   recursive parents hierarchy.
        // - Few additional attributes consumed by clients.
        //

        $serialized[Entity::TAG_LIST]        = $entity->tagNames();
        $serialized[Entity::MERCHANT_DETAIL] = $entity->merchantDetail ? $entity->merchantDetail->toArray() : [];
        $serialized[Entity::ADMINS]          = $entity->admins->pluck(Common::ID)->all();

        $groups = $this->repo->group->getParentsRecursively($entity->groups, true);

        $serialized[Entity::GROUPS]         = $groups->pluck(Common::ID)->all();
        $serialized[Entity::IS_MARKETPLACE] = $entity->isMarketplace();

        $firstAdmin = $entity->admins->first();

        $serialized[Entity::REFERRER] = empty($firstAdmin) ? null : $firstAdmin->getName();

        return $serialized;
    }

    protected function postProcessForHydration(Base\PublicEntity $model, array & $item)
    {
        $attributes = $item[Entity::MERCHANT_DETAIL];

        $merchantDetail = (new Detail\Entity)->newFromBuilder($attributes);

        $model->setRelation('merchantDetail', $merchantDetail);

        $model->__unset(Entity::GROUPS);
        $model->__unset(Entity::ADMINS);
        $model->__unset(Entity::MERCHANT_DETAIL);
    }
}

<?php

namespace RZP\Models\Merchant;

use Carbon\Carbon;
use DB;
use Closure;

use RZP\Exception;
use RZP\Base\Common;
use RZP\Models\Base;
use RZP\Base\BuilderEx;
use RZP\Constants\Mode;
use RZP\Models\Pricing;
use RZP\Constants\Table;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Models\Admin\Group;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\BusinessDetail;
use RZP\Models\Partner\Activation;
use RZP\Models\Base\QueryCache\CacheQueries;
use RZP\Models\Partner\Config as PartnerConfig;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Fraud\HealthChecker as HealthChecker;
use RZP\Models\Terminal\Category;

class Repository extends Base\Repository
{
    use CacheQueries;

    use Base\RepositoryUpdateTestAndLive;

    //
    // Possible values for sub_accounts(other than merchant id) query:
    // - '1': Include only sub accounts in searched results
    // - '0': Exclude sub accounts from searched results
    //
    const SUB_ACCOUNTS_ONLY_VALUE     = '1';
    const SUB_ACCOUNTS_EXCLUDED_VALUE = '0';

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
        Entity::FEE_BEARER              => 'sometimes|in:platform,customer,dynamic',
        Entity::FEE_MODEL               => 'sometimes|in:prepaid,postpaid',
        Entity::HOLD_FUNDS              => 'sometimes|in:0,1',
        Entity::RISK_RATING             => 'sometimes|integer|max:5|min:1',
        Entity::EXTERNAL_ID             => 'sometimes|string',
        Entity::ACTIVATION_SOURCE       => 'sometimes|string',
        Entity::ACCOUNT_CODE            => 'sometimes|string|min:3|max:20',
    ];

    protected $adminFetchParamRules = [
        EsRepository::SEARCH_HITS       => 'filled|boolean',
        EsRepository::QUERY             => 'filled|string|min:2|max:100',
        Entity::ORG_ID                  => 'sometimes|string|size:14',
        Entity::ACCOUNT_STATUS          => 'filled|custom',
        Entity::PARTNER_TYPE            => 'sometimes|string|custom',
        Detail\Entity::REVIEWER_ID      => 'sometimes|string|max:14',
        Entity::SUB_ACCOUNTS            => 'filled|custom',
        Entity::GROUPS                  => 'sometimes|array',
        Entity::ADMINS                  => 'sometimes|array|min:1|max:1',
        Constants::INSTANT_ACTIVATION   => 'sometimes|boolean',
        Constants::BUSINESS_TYPE_BUCKET => 'sometimes|custom',
        Constants::TAGS                 => 'sometimes|array'
    ];

    public function addQueryParamTags($query, $params)
    {
        $tags = $params[Constants::TAGS];

        $tags = array_unique(array_map('mb_strtolower', array_map('str_slug', $tags)));

        $tagsTable = 'tagging_tagged';

        $query->join($tagsTable, $tagsTable . '.taggable_id', 'merchants.id')
            ->where($tagsTable . '.taggable_type', '=', E::MERCHANT)
            ->whereIn($tagsTable . '.tag_slug', $tags)
            ->distinct();
    }

    protected function validateAccountStatus($attribute, $value)
    {
        AccountStatus::validate($value);
    }

    /**
     * @param $attribute
     * @param $value
     *
     * @throws BadRequestValidationFailureException
     */
    protected function validateBusinessTypeBucket($attribute, $value)
    {
        if (Detail\BusinessType::isValidBusinessTypeBucket($value) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid business type bucket: ' . $value);
        }
    }

    protected function validateSubAccounts($attribute, $value)
    {
        ($value === self::SUB_ACCOUNTS_ONLY_VALUE) or
            ($value === self::SUB_ACCOUNTS_EXCLUDED_VALUE) or
            Entity::verifyIdAndStripSign($value);
    }

    protected function validatePartnerType($attribute, $value)
    {
        if ($value === 'all')
        {
            return true;
        }

        (new Validator)->validatePartnerType($value);
    }

    public function fetchActivatedMerchantsBeforeTimestamp(
      int $limit,
      int $skip,
      int $end,
      array $merchantIds = [],
      array $merchantIdsExcluded = []): array
    {
        $query = $this->newQuery()
                      ->select(Entity::ID)
                      ->where(Entity::ACTIVATED, '=', 1)
                      ->where(Entity::ACTIVATED_AT, '<=', $end)
                      ->where(function ($query)
                      {
                          $query->whereNotIn(Entity::PARENT_ID, Preferences::NO_MERCHANT_INVOICE_PARENT_MIDS)
                                ->orWhereNull(Entity::PARENT_ID);
                      })
                      ->take($limit)
                      ->skip($skip);

        if (empty($merchantIds) === false)
        {
            $query = $query->whereIn(Entity::ID, $merchantIds);
        }

        if (empty($merchantIdsExcluded) === false)
        {
            $query = $query->whereNotIn(Entity::ID, $merchantIdsExcluded);
        }

        return $query->get()
                     ->pluck(Entity::ID)
                     ->toArray();
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

    public function getMerchantOrg(string $merchantId)
    {
        $orgId = $this->dbColumn(Entity::ORG_ID);

        $query = $this->newQuery()
            ->select($orgId)
            ->where(Entity::ID, '=', $merchantId)
            ->firstOrFail();

        return $query->org_id;
    }

    public function getPricingPlanOrFailPublic($merchant)
    {
        $pricing = $merchant->getPricingPlanId();

        if ($pricing === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_NOT_DEFINED_FOR_MERCHANT);
        }

        return (new Pricing\Repository)->getPricingPlanByIdOrFailPublic($pricing);
    }

    public function fetchMerchantsWithPositiveBalance()
    {
        return $this->newQuery()
                    ->whereHas('primaryBalance', function($q)
                    {
                        $q->where('balance', '>', 0);
                    })->get();
    }

    public function fetchMerchantsWithPricingPlan($planId)
    {
        return $this->newQuery()
                    ->where(Entity::PRICING_PLAN_ID, '=', $planId)
                    ->get();
    }

    public function fetchFeeBearersForPlanId($planId)
    {
        return $this->newQuery()
                    ->where(Entity::PRICING_PLAN_ID, '=', $planId)
                    ->select(Entity::FEE_BEARER)
                    ->distinct()
                    ->get()
                    ->pluck(Entity::FEE_BEARER)
                    ->toArray();
    }

    public function fetchMerchantsCountWithPricingPlanId($planId)
    {
        return $this->newQuery()
                    ->where(Entity::PRICING_PLAN_ID, '=', $planId)
                    ->count();
    }

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

    public function fetchRecentMerchants()
    {
        // 00:00 Today
        $today = \Carbon\Carbon::today(Timezone::IST)->getTimestamp();

        $start = \Carbon\Carbon::today(Timezone::IST)->subWeeks(3);

        return $this->newQuery()
                    ->whereBetween(Entity::CREATED_AT, [$start, $today])
                    ->whereNull(Entity::SUSPENDED_AT);
    }

    public function fetchMerchantsCreatedBetweenForOrg($from, $to, $orgId)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::ORG_ID, $orgId)
            ->whereBetween(Entity::CREATED_AT, [$from, $to])
            ->get()
            ;
    }

    public function fetchMerchantsActivatedBetweenForOrg($from, $to, $orgId)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::ORG_ID, $orgId)
            ->whereBetween(Entity::ACTIVATED_AT, [$from, $to])
            ->whereNull(Entity::SUSPENDED_AT)
            ->get()
            ->toArray();
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
                $merchantId = $this->repo->merchant->dbColumn(Entity::ID);
                $methodsMerchantId = $this->repo->methods->dbColumn(Methods\Entity::MERCHANT_ID);

                $methods = json_decode($params[Entity::METHODS], true);

                $join->on($methodsMerchantId, '=', $merchantId);

                foreach ($methods as $method => $value)
                {
                    // Filter can accept 'true'/'false' along with 0/1 & true/false
                    $queryValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                    if (in_array($method,Methods\Entity::getAllAdditionalWalletNames()))
                    {
                        if ((bool)$queryValue) {
                            $join->where(Methods\Entity::ADDITIONAL_WALLETS, 'like', '%'.$method.'%');
                        } else
                        {
                            $join->where(Methods\Entity::ADDITIONAL_WALLETS, 'not like', '%'.$method.'%');
                        }
                    } else
                    {
                        $join->where($method, '=', $queryValue);
                    }
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
    public function fetchAllMerchantContacts(array $merchantIds = [])
    {
        if (empty($merchantIds) === false)
        {
            return $this->newQuery()
                        ->whereIn(Entity::ID, $merchantIds)
                        ->select(
                            Entity::NAME,
                            Entity::EMAIL,
                            Entity::TRANSACTION_REPORT_EMAIL);
        }
        return $this->newQuery()
                    ->all(['name', 'email', 'transaction_report_email']);
    }

    public function fetchMerchantIdsInChunk($skip, $limit)
    {
        return $this->newQuery()
                    ->where(Entity::LIVE, '=', 1)
                    ->whereNull(Entity::SUSPENDED_AT)
                    ->skip($skip)
                    ->take($limit)
                    ->pluck(Entity::ID)
                    ->toArray();
    }

    public function getLiveMerchantCount()
    {
        return $this->newQuery()
                    ->where(Entity::LIVE, '=', 1)
                    ->whereNull(Entity::SUSPENDED_AT)
                    ->count();
    }

    public function fetchLiveMerchantContacts(array $merchantIds)
    {
        return $this->newQuery()
                    ->whereIn(Entity::ID, $merchantIds)
                    ->where(Entity::LIVE, '=', 1)
                    ->whereNull(Entity::SUSPENDED_AT)
                    ->select(
                        Entity::NAME,
                        Entity::EMAIL,
                        Entity::TRANSACTION_REPORT_EMAIL);
    }

    public function fetchMerchantWhereTestBankIsNull()
    {
        return $this->newQueryWithConnection(Mode::TEST)
                    ->has('bankAccount', '<', 1)
                    ->get();
    }

    public function fetchMerchantOnConnection($merchantId, $mode)
    {
        return $this->newQueryWithConnection($mode)
                    ->findOrFail($merchantId);
    }

    public function fetchAllLiveMerchants()
    {
        return $this->newQuery()
                    ->where(Entity::LIVE, '=', 1)
                    ->whereNull(Entity::SUSPENDED_AT);
    }

    public function fetchAllLiveAndActivatedMerchants(int $from, int $to)
    {
        return $this->newQueryWithConnection($this->getDataWarehouseConnection())
                    ->select(Entity::ID)
                    ->where(Entity::LIVE, '=', 1)
                    ->where(Entity::ACTIVATED, '=', 1)
                    ->whereBetween(Entity::ACTIVATED_AT,[$from, $to])
                    ->whereNull(Entity::SUSPENDED_AT)
                    ->get()
                    ->pluck(Entity::ID)
                    ->toArray();
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

    public function fetchMerchantsWithTag($tagName)
    {
        $tagsTable = 'tagging_tagged';
        return $this->newQuery()
            ->select(Table::MERCHANT.'.*')
            ->join($tagsTable, $tagsTable . '.taggable_id', Table::MERCHANT.'.id')
            ->where($tagsTable . '.taggable_type', '=', E::MERCHANT)
            ->where($tagsTable . '.tag_name', '=', $tagName)
            ->get();
    }

    public function findByAccountIdAndParent(
        string $accountId,
        Entity $parent,
        bool $fail = false)
    {
        Account\Entity::verifyIdAndStripSign($accountId);

        $query   = $this->newQuery()->where(Entity::PARENT_ID, $parent->getId());
        $account = $fail ? $query->findOrFailPublic($accountId) : $query->find($accountId);

        if ($account !== null)
        {
            $account->parent()->associate($parent);
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
     * Modifies query to eager load details, admins, groups and features,
     * Unsettled balance.
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

        $balanceSelector = function($query)
                           {
                              $fields = $this->esRepo->getBalanceIndexedFields();

                              $query->select($fields);
                           };

        $with = [
            camel_case(Entity::MERCHANT_DETAIL) => $detailSelector,
            Entity::GROUPS                      => $groupSelector,
            Entity::ADMINS                      => $adminSelector,
            Entity::FEATURES                    => function () {},
            'primaryBalance'                    => $balanceSelector,
        ];

        //
        // Following 6 queries are run in total (dumps from indexing command):
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
        // - SELECT <fields> FROM balance
        //   WHERE balance.id IN (?)
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
        // - Unsettled balance to merchant
        //

        $serialized[Entity::TAG_LIST]        = $entity->tagNames();
        $serialized[Entity::MERCHANT_DETAIL] = $entity->merchantDetail ? $entity->merchantDetail->toArray() : [];
        $serialized[Entity::ADMINS]          = $entity->admins->pluck(Common::ID)->all();

        $groups = $this->repo->group->getParentsRecursively($entity->groups, true);

        $serialized[Entity::GROUPS]         = $groups->pluck(Common::ID)->all();
        $serialized[Entity::IS_MARKETPLACE] = $entity->isMarketplace();

        $firstAdmin = $entity->admins->first();

        $serialized[Entity::REFERRER] = empty($firstAdmin) ? null : $firstAdmin->getName();

        $serialized[Entity::BALANCE] = optional($entity->primaryBalance)->getBalance() ?: 0;

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

    public function getMerchantUserMapping(string $merchantId,
                                           string $userId,
                                           string $role = null,
                                           string $product = null)
    {
        $product = $product ?? $this->auth->getRequestOriginProduct();

        $query = $this->newQuery()
                      ->find($merchantId)
                      ->users()
                      ->where(Entity::ID, $userId);

        if (empty($role) === false)
        {
            $query->where(Entity::ROLE, $role);
        }

        if (empty($product) === false)
        {
            $query->where(Entity::PRODUCT, $product);
        }

        return $query->first();
    }

    public function findByIdAndOrgId(string $id, string $orgId)
    {
        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        return $this->newQuery()
                    ->orgId($orgId)
                    ->findOrFailPublic($id);
    }

    public function fetchByEmailAndOrgId(string $email, string $orgId = Org\Entity::RAZORPAY_ORG_ID)
    {
        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        //
        // Order by created_at asc so that the partner merchant makes it to
        // the top of the list followed by submerchants
        //
        return $this->newQuery()
                    ->orgId($orgId)
                    ->where(Entity::EMAIL, $email)
                    ->orderBy(Entity::CREATED_AT, 'asc')
                    ->get();
    }

    /**
     * If the submerchant belongs to a pure platform type partner,
     *      $appId should be one of the oauth apps created by the partner.
     * If the submerchant belongs to a non pure platform type partner,
     *      $appId should be the id of the internal partner app created.
     *
     * @param string $submerchantId
     * @param string $appId
     *
     * @return Entity
     */
    public function findSubmerchantByIdAndConnectedAppId(string $submerchantId, string $appId): Entity
    {
        //
        // To make use of existing function (buildQueryToFetchSubmerchantsByAppIds) which uses an array for appIds,
        // we convert the only app id and submerchant id that we have to an array.
        //
        $appIds         = [$appId];
        $submerchantIds = [$submerchantId];

        $submerchant = $this->buildQueryToFetchSubmerchantsByAppIds($appIds, $submerchantIds)
                            ->firstOrFail();

        return $submerchant;
    }

    /**
     * @param array $applicationIds
     * @param array $params
     *
     * @param array $relations
     *
     * @return Base\PublicCollection
     */
    public function fetchSubmerchantsByAppIds(array $applicationIds, array $params = [], array $relations = []): Base\PublicCollection
    {
        if (empty($applicationIds) === true)
        {
            return new Base\PublicCollection;
        }

        $submerchantIds = $params[Entity::MERCHANT_ID] ?? [];

        unset($params[Entity::MERCHANT_ID]);

        $query = $this->buildQueryToFetchSubmerchantsByAppIds($applicationIds, $submerchantIds, $relations);

        $this->buildQueryWithParams($query, $params);

        $query->orderBy(Table::MERCHANT . '.' . Entity::CREATED_AT, 'desc')
              ->orderBy(Table::MERCHANT . '.' . Entity::ID, 'desc');

        $submerchants = $query->get();

        return $submerchants;
    }

    /**
     * Used to filter the list of submerchants fetched for partners
     *
     * @param $query
     * @param $params
     *
     * @return mixed
     */
    protected function addQueryParamActivationStatus($query, $params)
    {
        $query->where(Detail\Entity::ACTIVATION_STATUS, $params[Detail\Entity::ACTIVATION_STATUS]);

        return $query;
    }

    /**
     * @param array $applicationIds
     *
     * @param array $submerchantIds
     *
     * @param array $relations
     *
     * @return Base\BuilderEx
     */
    protected function buildQueryToFetchSubmerchantsByAppIds(array $applicationIds, array $submerchantIds = [], array $relations = [])
    {
        $accessMapRepo       = $this->repo->merchant_access_map;
        $merchantDetailsRepo = $this->repo->merchant_detail;

        $merchantsMerchantId = $this->dbColumn(Entity::ID);

        $accessMapsEntityId   = $accessMapRepo->dbColumn(AccessMap\Entity::ENTITY_ID);
        $accessMapsDeletedAt  = $accessMapRepo->dbColumn(AccessMap\Entity::DELETED_AT);
        $accessMapsEntityType = $accessMapRepo->dbColumn(AccessMap\Entity::ENTITY_TYPE);
        $accessMapsMerchantId = $accessMapRepo->dbColumn(AccessMap\Entity::MERCHANT_ID);

        $merchantDetailsColumns    = $merchantDetailsRepo->dbColumn('*');
        $merchantDetailsMerchantId = $merchantDetailsRepo->dbColumn(Detail\Entity::MERCHANT_ID);

        $attributes = [
            $merchantDetailsColumns,
            $this->dbColumn('*'),
            $accessMapsEntityId . ' as ' . Constants::APPLICATION_ID,
        ];

        $relations = array_unique(array_merge(['users', 'owners'], $relations));

        //
        // merchantDetail is not fetched as a relation below because
        // a filter has to be added for merchantDetail.activation_status in the query
        //
        $query = $this->newQuery()
                      ->with($relations)
                      ->select($attributes)
                      ->join(Table::MERCHANT_ACCESS_MAP, $merchantsMerchantId, $accessMapsMerchantId)
                      ->leftJoin(Table::MERCHANT_DETAIL, $merchantsMerchantId, $merchantDetailsMerchantId)
                      ->where($accessMapsEntityType, AccessMap\Entity::APPLICATION)
                      ->whereIn($accessMapsEntityId, $applicationIds)
                      ->whereNull($accessMapsDeletedAt);

        if (empty($submerchantIds) === false)
        {
            $query->whereIn($accessMapsMerchantId, $submerchantIds);
        }

        return $query;
    }

    public function fetchMerchantsForSettlement(array $inMerchantIds = [], array $notInMerchantIds = [])
    {
        $merchantId             = $this->dbColumn(Entity::ID);
        $colHoldFunds           = $this->dbColumn(Entity::HOLD_FUNDS);
        $colActivatedAt         = $this->dbColumn(Entity::ACTIVATED_AT);

        $activatedMerchants = $this->repo->merchant
                                   ->newQuery()
                                   ->select($merchantId)
                                   ->where($colHoldFunds, 0)
                                   ->whereNotNull($colActivatedAt);

        if (empty($inMerchantIds) === false)
        {
            $activatedMerchants->whereIn($merchantId, $inMerchantIds);
        }

        if (empty($notInMerchantIds) === false)
        {
            $activatedMerchants->whereNotIn($merchantId, $notInMerchantIds);
        }

        return $activatedMerchants;
    }

    /**
     * This will give the query object for fetching active merchants
     *
     * @return mixed
     */
    public function getQueryForActiveMerchants()
    {
        $merchantId    = $this->dbColumn(Entity::ID);
        $activated     = $this->dbColumn(Entity::ACTIVATED);
        $activatedAt   = $this->dbColumn(Entity::ACTIVATED_AT);

        $activeMerchants = $this->newQuery()
                                ->select($merchantId)
                                ->whereNotNull($activatedAt)
                                ->where($activated, 1);

        return $activeMerchants;
    }

    public function getPartnerMerchantFromSubMerchantId(string $subMerchantId)
    {
        $accessMapRepo = $this->repo->merchant_access_map;

        // db columns
        $accessMapOwnerId    = $accessMapRepo->dbColumn(AccessMap\Entity::ENTITY_OWNER_ID);
        $accessMapMerchantId = $accessMapRepo->dbColumn(AccessMap\Entity::MERCHANT_ID);

        $merchantId          = $this->dbColumn(Entity::ID);

        $query = $this->newQuery()
                      ->select($this->getTableName() . '.*')
                      ->join(Table::MERCHANT_ACCESS_MAP, $accessMapOwnerId, '=', $merchantId)
                      ->where($accessMapMerchantId, '=', $subMerchantId);

        return $query->firstOrFail();
    }

    public function getAllPartnerBankAccountsForSubmerchants(array $submerchantIds): Base\PublicCollection
    {
        // filter mIds so that we get only merchantIds which are mapped to at least one partner
        $submerchantIds = $this->repo->merchant_access_map->fetchMerchantsMappedToPartner($submerchantIds);

        if (empty($submerchantIds) === true)
        {
            return new Base\PublicCollection;
        }

        // Repo class instances
        $accessMapRepo     = $this->repo->merchant_access_map;
        $partnerConfigRepo = $this->repo->partner_config;
        $bankAccountRepo   = $this->repo->bank_account;

        // Merchants columns
        $merchantsMerchantId = $this->dbColumn(Entity::ID);

        // Access map columns
        $accessMapsEntityId      = $accessMapRepo->dbColumn(AccessMap\Entity::ENTITY_ID);
        $accessMapsDeletedAt     = $accessMapRepo->dbColumn(AccessMap\Entity::DELETED_AT);
        $accessMapsEntityType    = $accessMapRepo->dbColumn(AccessMap\Entity::ENTITY_TYPE);
        $accessMapsMerchantId    = $accessMapRepo->dbColumn(AccessMap\Entity::MERCHANT_ID);
        $accessMapsEntityOwnerId = $accessMapRepo->dbColumn(AccessMap\Entity::ENTITY_OWNER_ID);

        // Partner columns
        $partnerTable        = 'partners'; // 'merchants' is aliased below as 'partners'
        $partnersMerchantId  = $partnerTable . '.' . Entity::ID;
        $partnersPartnerType = $partnerTable . '.' . Entity::PARTNER_TYPE;

        // Bank account columns
        $bankAccountId          = $bankAccountRepo->dbColumn(BankAccount\Entity::ID);
        $bankAccountsType       = $bankAccountRepo->dbColumn(BankAccount\Entity::TYPE);
        $bankAccountsMerchantId = $bankAccountRepo->dbColumn(BankAccount\Entity::MERCHANT_ID);
        $bankAccountsDeletedAt  = $bankAccountRepo->dbColumn(BankAccount\Entity::DELETED_AT);

        $partnerConfigOriginId        = $partnerConfigRepo->dbColumn(PartnerConfig\Entity::ORIGIN_ID);
        $partnerConfigSettleToPartner = $partnerConfigRepo->dbColumn(PartnerConfig\Entity::SETTLE_TO_PARTNER);

        $attributes = [
            $this->dbColumn('*'),
            $partnerConfigSettleToPartner . ' as partner_config_settle_to_partner',
            $bankAccountId . ' as partner_bank_account_id',
            $partnerConfigOriginId . ' as partner_config_origin_id',
        ];

        $chunkedIdsList = array_chunk($submerchantIds, 5000);

        $aggregateResults = new Base\PublicCollection;

        foreach ($chunkedIdsList as $chunkedIds)
        {
            $appConfig = $this->newQuery()
                              ->select($attributes)
                              ->join(
                                  Table::MERCHANT_ACCESS_MAP,
                                  $merchantsMerchantId,
                                  $accessMapsMerchantId)
                              ->join(
                                  Table::MERCHANT . ' as ' . $partnerTable,
                                  $accessMapsEntityOwnerId,
                                  $partnersMerchantId)
                              ->join(
                                  Table::BANK_ACCOUNT,
                                  $partnersMerchantId,
                                  $bankAccountsMerchantId)
                              ->where($partnersPartnerType, '!=', Constants::PURE_PLATFORM)
                              ->where($bankAccountsType, BankAccount\Type::MERCHANT)
                              ->where($accessMapsEntityType, AccessMap\Entity::APPLICATION)
                              ->whereNull($accessMapsDeletedAt)
                              ->whereNull($bankAccountsDeletedAt)
                              ->whereIn($merchantsMerchantId, $chunkedIds);

            $submerchantConfig = clone $appConfig;

            $this->joinPartnerConfigForApp($appConfig);

            $this->joinPartnerConfigForSubmerchant($submerchantConfig);

            $results = $submerchantConfig->union($appConfig)->get();

            $aggregateResults = $aggregateResults->concat($results);
        }

        return $aggregateResults;
    }

    private function joinPartnerConfigForApp(BuilderEx & $query)
    {
        // Access map columns
        $accessMapRepo     = $this->repo->merchant_access_map;
        $partnerConfigRepo = $this->repo->partner_config;

        // Partner config columns
        $partnerConfigEntityType = $partnerConfigRepo->dbColumn(PartnerConfig\Entity::ENTITY_TYPE);
        $partnerConfigEntityId   = $partnerConfigRepo->dbColumn(PartnerConfig\Entity::ENTITY_ID);

        // Other columns
        $accessMapsEntityId = $accessMapRepo->dbColumn(AccessMap\Entity::ENTITY_ID);

        $query->join(
            Table::PARTNER_CONFIG,
            function ($join) use (
                $partnerConfigEntityType,
                $partnerConfigEntityId,
                $accessMapsEntityId
            )
            {
                // Join with entity_id as application id
                $join->on($partnerConfigEntityId, $accessMapsEntityId)
                     ->where($partnerConfigEntityType, PartnerConfig\Constants::APPLICATION);
            });
    }

    private function joinPartnerConfigForSubmerchant(BuilderEx & $query)
    {
        // Repo instances
        $accessMapRepo     = $this->repo->merchant_access_map;
        $partnerConfigRepo = $this->repo->partner_config;

        // Partner config columns
        $partnerConfigEntityType = $partnerConfigRepo->dbColumn(PartnerConfig\Entity::ENTITY_TYPE);
        $partnerConfigEntityId   = $partnerConfigRepo->dbColumn(PartnerConfig\Entity::ENTITY_ID);
        $partnerConfigOriginType = $partnerConfigRepo->dbColumn(PartnerConfig\Entity::ORIGIN_TYPE);
        $partnerConfigOriginId   = $partnerConfigRepo->dbColumn(PartnerConfig\Entity::ORIGIN_ID);

        // Other columns
        $merchantsMerchantId = $this->dbColumn(Entity::ID);
        $accessMapsEntityId  = $accessMapRepo->dbColumn(AccessMap\Entity::ENTITY_ID);

        $query->join(
            Table::PARTNER_CONFIG,
            function ($join) use (
                $partnerConfigEntityType,
                $partnerConfigEntityId,
                $partnerConfigOriginType,
                $partnerConfigOriginId,
                $accessMapsEntityId,
                $merchantsMerchantId
            )
            {
                // Join with entity_id as merchant id and origin_id as application id
                $join->on($partnerConfigEntityId, $merchantsMerchantId)
                     ->on($partnerConfigOriginId, $accessMapsEntityId)
                     ->where($partnerConfigEntityType, PartnerConfig\Constants::MERCHANT)
                     ->where($partnerConfigOriginType, PartnerConfig\Constants::APPLICATION);
            });
    }

    public function fetchAllSuspendedMerchants($input)
    {
        $merchants = $this->newQuery()
                          ->where(Entity::LIVE, '=', 0)
                          ->whereNotNull(Entity::SUSPENDED_AT);

        if(isset($input['limit']) === true )
        {
            $merchants->take($input['limit']);
        }

        if(isset($input['skip']) === true )
        {
            $merchants->skip($input['skip']);
        }

        $merchants = $merchants->select(['id', 'email', 'transaction_report_email'])
                               ->get();
        return $merchants;
    }

    public function fetchAllMerchantIDs($input)
    {
        $query = $this->newQuery()->select([Entity::ID])->orderBy(Entity::ID);

        if (isset($input['afterId']) === true)
        {
            $query->where(Entity::ID, '>', $input['afterId']);
        }

        if (isset($input['count']) === true)
        {
            $query->take($input['count']);
        }

        return $query->get();
    }

    public function fetchHistoricalClaimedMerchantIds($mode)
    {
        $historicalClaimedMerchantIds = \DB::connection($mode)->table(Table::MERCHANT_MAP)
                                           ->select('merchant_id')
                                           ->where('entity_id', Group\Constant::SF_CLAIMED_MERCHANTS_GROUP_ID)
                                           ->get()
                                           ->pluck('merchant_id')
                                           ->toArray();

        return $historicalClaimedMerchantIds;
    }

    public function fetchLinkedAccountMids($merchantId)
    {
        $childMerchantIds = $this->newQuery()
                                 ->select(Entity::ID)
                                 ->where('parent_id', $merchantId)
                                 ->get()
                                 ->pluck(Entity::ID)
                                 ->toArray();

        return $childMerchantIds;
    }

    public function fetchLinkedAccountsCount($merchantId)
    {
        $childMerchantIds = $this->newQuery()
                                 ->select(Entity::ID)
                                 ->where('parent_id', $merchantId)
                                 ->count();

        return $childMerchantIds;
    }

    /**
     * @param  array  $ids
     * @return Base\PublicCollection
     * @throws \RZP\Exception\BadRequestException
     */
    public function findManyOrFailPublic(array $ids): Base\PublicCollection
    {
        return $this->newQuery()->findManyOrFailPublic($ids);
    }

    public function countAccountCodeForMerchant(string $accountCode, string $parentId)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->where(Entity::PARENT_ID, $parentId)
                    ->where(Entity::ACCOUNT_CODE, $accountCode)
                    ->limit(1)
                    ->count(Entity::ACCOUNT_CODE);
    }

    public function getIdByAccountCodeAndParent(string $accountCode, string $parentId)
    {
        $id = $this->dbColumn(Entity::ID);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->select($id)
                      ->where(Entity::PARENT_ID, $parentId)
                      ->where(Entity::ACCOUNT_CODE, $accountCode)
                      ->limit(1)
                      ->get()
                      ->pluck(Entity::ID);

        return $query->pop();
    }

    public function getAccountCodeById(string $id)
    {
        $accountCode = $this->dbColumn(Entity::ACCOUNT_CODE);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->select($accountCode)
                      ->where(Entity::ID, $id)
                      ->limit(1)
                      ->get()
                      ->pluck(Entity::ACCOUNT_CODE);

        return $query->pop();
    }

    public function fetchPartnerIdsInBatches($merchantIds = null, $limit = null, $afterId = null)
    {
        $query = $this->newQuery()
                      ->select(Entity::ID)
                      ->whereNotNull(Entity::PARTNER_TYPE)
                      ->orderBy(Entity::ID);;

        if (empty($limit) === false)
        {
            $query->take($limit);
        }

        if (empty($afterId) === false)
        {
            $query->where(Entity::ID, '>', $afterId);
        }

        if (empty($merchantIds) === false)
        {
            $query->whereIn(Entity::ID, $merchantIds);
        }

        return $query->get();
    }

    public function fetchAggregatorAndFullManagedPartners($merchantIds = null, $limit = null, $afterId = null)
    {
        $allowedPartnerTypes = [Constants::AGGREGATOR, Constants::FULLY_MANAGED];

        $query = $this->newQuery()
                      ->select(Entity::ID)
                      ->whereIn(Entity::PARTNER_TYPE, $allowedPartnerTypes)
                      ->orderBy(Entity::ID);

        if (empty($limit) === false)
        {
            $query->take($limit);
        }

        if (empty($afterId) === false)
        {
            $query->where(Entity::ID, '>', $afterId);
        }

        if (empty($merchantIds) === false)
        {
            $query->whereIn(Entity::ID, $merchantIds);
        }

        return $query->get();
    }

    public function findPartnersWithoutPartnerActivation($limit, $afterId = null)
    {
        $merchantIdColumn                  = $this->dbColumn(Entity::ID);
        $merchantPartnerType               = $this->dbColumn(Entity::PARTNER_TYPE);
        $partnerActivationMerchantIdColumn = $this->repo->partner_activation->dbColumn(Activation\Entity::MERCHANT_ID);

        $query = $this->newQuery()->select($merchantIdColumn)
                      ->leftJoin(Table::PARTNER_ACTIVATION, $partnerActivationMerchantIdColumn, '=', $merchantIdColumn)
                      ->whereNull($partnerActivationMerchantIdColumn)
                      ->whereNotNull($merchantPartnerType);

        if (empty($limit) === false)
        {
            $query->limit($limit);
        }

        if (empty($afterId) === false)
        {
            $query->where($merchantIdColumn, '>', $afterId);
        }

        return $query->orderBy($merchantIdColumn, 'asc')->get()->pluck(Entity::ID)->toArray();
    }

    public function getMerchantListForPeriodicHealthCheck($checkerType)
    {
        $merchantList = [];
        switch ($checkerType)
        {
            case HealthChecker\Constants::WEBSITE_CHECKER:
                $merchantList = $this->getMerchantListForWebsiteCheckerPeriodic($checkerType);
                break;
            case HealthChecker\Constants::APP_CHECKER:
                $merchantList = $this->getMerchantListForAppCheckerPeriodic($checkerType);
                break;
            default:
                break;
        }
        return $merchantList;
    }

    public function getMerchantListForWebsiteCheckerPeriodic()
    {
        $query = $this->newQuery()
            ->leftJoin(Table::MERCHANT_DETAIL, Entity::ID, Detail\Entity::MERCHANT_ID)
            ->select(Entity::ID)
            ->where(Entity::HOLD_FUNDS, '=', 0)
            ->where(Entity::ACTIVATED, '=', 1)
            ->where(Detail\Entity::BUSINESS_WEBSITE, '!=', '')
            ->whereNotNull(Detail\Entity::BUSINESS_WEBSITE)
            ->whereRaw('DATEDIFF(current_date(), from_unixtime(activated_at)) % 30 = 1');

        return $query->get();
    }

    public function getMerchantListForAppCheckerPeriodic()
    {
        $query = $this->newQuery()
            ->leftJoin(Table::MERCHANT_BUSINESS_DETAIL, Table::MERCHANT . '.' . Entity::ID, BusinessDetail\Entity::MERCHANT_ID)
            ->select(Table::MERCHANT . '.' . Entity::ID)
            ->where(Entity::HOLD_FUNDS, '=', 0)
            ->where(Entity::ACTIVATED, '=', 1)
            ->whereNotNull(BusinessDetail\Entity::APP_URLS)
            ->whereRaw('DATEDIFF(current_date(), from_unixtime(activated_at)) % 30 = 1');
        return $query->get();
    }

    public function getMerchantListEligibleForRTB($blacklistedMIDs)
    {
        $merchantId = $this->dbColumn(Entity::ID);
        $orgId = $this->dbColumn(Entity::ORG_ID);
        $activatedAt = $this->dbColumn(Entity::ACTIVATED_AT);
        $category2 = $this->dbColumn(Entity::CATEGORY2);

        $merchantDetailRepo = $this->repo->merchant_detail;
        $activationStatus = $merchantDetailRepo->dbColumn(Detail\Entity::ACTIVATION_STATUS);
        $businessType = $merchantDetailRepo->dbColumn(Detail\Entity::BUSINESS_TYPE);

        $excludedCategoryList = [Category::LENDING, Category::GOVERNMENT, Category::GOVT_EDUCATION];
        $excludedBusinessTypeList =  Detail\BusinessType::getIndexForUnregisteredBusiness();
        $fourMonthsAgoTimestamp = Carbon::today()->subDays(120)->getTimestamp();

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->join(Table::MERCHANT_DETAIL, Entity::ID, Detail\Entity::MERCHANT_ID)
            ->select($merchantId)
            ->where($orgId, '=', Org\Entity::RAZORPAY_ORG_ID)
            ->where($activatedAt, '<', $fourMonthsAgoTimestamp)
            ->where($activationStatus, '=', Detail\Status::ACTIVATED)
            ->whereNotIn($category2, $excludedCategoryList)
            ->whereNotIn($businessType, $excludedBusinessTypeList)
            ->whereNotIn($merchantId, $blacklistedMIDs);

        return $query->pluck($merchantId);
    }
}

<?php

namespace RZP\Models\Merchant;

use DB;
use Closure;
use Carbon\Carbon;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use RZP\Exception;
use RZP\Base\Common;
use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Constants\Es;
use RZP\Base\BuilderEx;
use RZP\Constants\Mode;
use RZP\Models\Pricing;
use Rzp\Wda_php\Symbol;
use RZP\Trace\TraceCode;
use RZP\Constants\Table;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Constants\Product;
use RZP\Models\BankAccount;
use RZP\Constants\Timezone;
use RZP\Models\Admin\Group;
use RZP\Services\WDAService;
use RZP\Base\ConnectionType;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Detail;
use Rzp\Wda_php\WDAQueryBuilder;
use RZP\Models\Terminal\Category;
use RZP\Models\Base\EsRepository;
use RZP\Models\TrustedBadge\Constants as TrustedBadgeConstants;
use RZP\Models\Partner\Activation;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Merchant\BusinessDetail;
use RZP\Models\State\Entity as ActionState;
use RZP\Models\Base\QueryCache\CacheQueries;
use RZP\Models\Partner\Config as PartnerConfig;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Fraud\HealthChecker as HealthChecker;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Modules\Acs\Wrapper\Merchant as MerchantWrapper;

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

    protected $totalMerchantOnboarded;

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
        Constants::TAGS                 => 'sometimes|array',
        BusinessDetail\Entity::MIQ_SHARING_DATE => 'sometimes|integer',
        BusinessDetail\Entity::TESTING_CREDENTIALS_DATE => 'sometimes|integer',
    ];

    public function __findOrFail($id) {

        return $this->repo->transactionOnLiveAndTest(function () use ($id) {
            $merchantFromApi = $this->findOrFail($id);
            return (new MerchantWrapper())->FindOrFail($id, $merchantFromApi);
        });
    }

    public function __findOrFailPublic($id) {

        return $this->repo->transactionOnLiveAndTest(function () use ($id) {
            $merchantFromApi = $this->findOrFailPublic($id);
            $id = Entity::stripDefaultSign($id);
            return (new MerchantWrapper())->FindOrFail($id, $merchantFromApi);
        });
    }

    public function __findOrFailPublicTemp($id) {
        $merchantFromApi = $this->findOrFailPublic($id);
        return (new MerchantWrapper())->FindOrFail($id, $merchantFromApi);
    }

    /**
     * Select merchants from the query that have requested tags
     *
     * @param $query
     * @param $params
     *
     * @return void
     */
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

    /**
     * Select merchants from the query that do not have requested tags
     *
     * @param $query
     * @param $params
     *
     * @return void
     */
    public function addQueryParamWithoutTags($query, $params): void
    {
        $tags = $params[Constants::WITHOUT_TAGS];

        $tags = array_unique(array_map('mb_strtolower', array_map('str_slug', $tags)));

        $tagsTable = 'tagging_tagged';

        $query->whereNotIn(
            'merchants.id',
            function($query)
            use ($tags, $tagsTable) {
                $query->select($tagsTable . '.taggable_id')
                      ->from($tagsTable)
                      ->where($tagsTable . '.taggable_type', '=', E::MERCHANT)
                      ->whereIn($tagsTable . '.tag_slug', $tags);
            });
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
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
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

    public function getSharedAccount(): Entity
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

    public function getMerchant(string $merchantId)
    {
        return $this->newQuery()
            ->where(Entity::ID, '=', $merchantId)
            ->firstOrFail();
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
        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
                    ->where(Entity::PRICING_PLAN_ID, '=', $planId)
                    ->get();
    }

    public function fetchFeeBearersForPlanId($planId)
    {
        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
                    ->where(Entity::PRICING_PLAN_ID, '=', $planId)
                    ->select(Entity::FEE_BEARER)
                    ->distinct()
                    ->get()
                    ->pluck(Entity::FEE_BEARER)
                    ->toArray();
    }

    public function fetchMerchantsCountWithPricingPlanId($planId)
    {
        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
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

    public function fetchMerchantsCreatedBetween($from, $to)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->whereBetween(Entity::CREATED_AT, [$from, $to])
            ->get()
            ->pluck(Entity::ID)
            ->toArray();
    }

    public function fetchMerchantsCreatedBetweenForOrg($from, $to, $orgId)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::ORG_ID, $orgId)
            ->whereBetween(Entity::CREATED_AT, [$from, $to])
            ->get();
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
                    } else if ($method === Methods\Entity::IN_APP)
                    {
                        $join->where(Methods\Entity::ADDON_METHODS . '->' . Methods\Entity::UPI . '->' . Methods\Entity::IN_APP,'=', $value);
                    } else if ($method === Methods\Entity::SODEXO)
                    {
                        $join->where(Methods\Entity::ADDON_METHODS . '->' . Methods\Entity::CARD . '->' . Methods\Entity::SODEXO,'=', $value);
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

    protected function addQueryParamProduct($query, $params)
    {
        $this->joinMerchantUsers($query, $params[Entity::PRODUCT]);
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

    public function fetchMerchantIdsByOrgId($orgId)
    {
        return $this->newQuery()
            ->where(Entity::ORG_ID, '=', $orgId)
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

    public function filterLiveMerchants(array $merchantIdList)
    {
        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
                    ->where(Entity::LIVE, '=', 1)
                    ->whereIn(Entity::ID, $merchantIdList)
                    ->whereNull(Entity::SUSPENDED_AT)
                    ->get()
                    ->pluck(Entity::ID)
                    ->toArray();
    }
    public function fetchAllLiveActivatedRegularMerchantsOfOrg(int $from, int $to, $org = Org\Entity::RAZORPAY_ORG_ID)
    {
        $experimentResult = (new Detail\Core)->getSplitzResponse(UniqueIdEntity::generateUniqueId(),
            Constants::WDA_MIGRATION_ACQUISITION_SPLITZ_EXP_ID);

        $isWDAExperimentEnabled = ( $experimentResult === 'live' ) ? true : false;

        try
        {
            if(($this->app['api.route']->isWDAServiceRoute() === true) and ($isWDAExperimentEnabled === true))
            {
                return $this->fetchAllLiveActivatedRegularMerchantsOfOrgFromWda($from, $to, $org);
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->error(TraceCode::WDA_MIGRATION_ERROR, [
                'wda_migration_error' => $ex->getMessage(),
                'route_name'          => $this->app['api.route']->getCurrentRouteName(),
            ]);
        }

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->select(Entity::ID)
            ->where(Entity::LIVE, '=', 1)
            ->where(Entity::ACTIVATED, '=', 1)
            ->where(Entity::ORG_ID, '=', $org)
            ->whereBetween(Entity::ACTIVATED_AT,[$from, $to])
            ->whereNull(Entity::SUSPENDED_AT)
            ->where(Entity::BUSINESS_BANKING, '=', false)
            ->whereNull(Entity::PARENT_ID)
            ->whereNull(Entity::PARTNER_TYPE)
            ->get()
            ->pluck(Entity::ID)
            ->toArray();
    }

    /**
     * Fetches all live and activated regular merchants of org from query through wda service layer
     *
     * @param int $from
     * @param int $to
     * @param string $org
     *
     * @return array
     *
     * @throws \Exception
     */
    public function fetchAllLiveActivatedRegularMerchantsOfOrgFromWda(int $from, int $to, $org = Org\Entity::RAZORPAY_ORG_ID): array
    {
        $this->trace->info(TraceCode::WDA_SERVICE_REQUEST, [
            'method_name'  => __FUNCTION__,
            'from'         => $from,
            'to'           => $to,
            'org'          => $org,
        ]);

        $startTimeMs = round(microtime(true) * 1000);

        $wdaClient = $this->app['wda-client']->wdaClient;

        $wdaQueryBuilder = new WDAQueryBuilder();

        $wdaQueryBuilder->addQuery($this->getTableName(), Entity::ID);

        $wdaQueryBuilder->resources($this->getTableName());

        $wdaQueryBuilder->filters($this->getTableName(), Entity::LIVE, [1], Symbol::EQ)
            ->filters($this->getTableName(), Entity::ACTIVATED, [1], Symbol::EQ)
            ->filters($this->getTableName(), Entity::ORG_ID, [$org], Symbol::EQ)
            ->filters($this->getTableName(), Entity::ACTIVATED_AT, [$from, $to], Symbol::BETWEEN)
            ->filters($this->getTableName(), Entity::SUSPENDED_AT, [], Symbol::NULL)
            ->filters($this->getTableName(), Entity::BUSINESS_BANKING, [false], Symbol::EQ)
            ->filters($this->getTableName(), Entity::PARENT_ID, [], Symbol::NULL)
            ->filters($this->getTableName(), Entity::PARTNER_TYPE, [], Symbol::NULL);

        $wdaQueryBuilder->namespace($this->getEntityObject()->getConnection()->getDatabaseName());

        $wdaQueryBuilder->cluster(WDAService::ADMIN_CLUSTER);

        $this->trace->info(TraceCode::WDA_SERVICE_QUERY, [
            'wda_query_builder' => $wdaQueryBuilder->build()->serializeToJsonString(),
            'route_name'        => $this->app['api.route']->getCurrentRouteName(),
        ]);

        $response = $wdaClient->fetchMultipleWithExpand($wdaQueryBuilder->build(), $this->newQuery()->getModel(), []);

        $liveActivatedRegularMerchantsOfOrg = $this->convertWdaResponseToArray($response, Entity::ID);

        $endTimeMs = round(microtime(true) * 1000);

        $queryDuration = $endTimeMs - $startTimeMs;

        $this->trace->info(TraceCode::WDA_SERVICE_RESPONSE, [
            'route_name'       => $this->app['api.route']->getCurrentRouteName(),
            'method_name'      => __FUNCTION__,
            'merchants_count'  => count($liveActivatedRegularMerchantsOfOrg),
            'duration_ms'      => $queryDuration,
        ]);

        return $liveActivatedRegularMerchantsOfOrg;
    }

    public function convertWdaResponseToArray(array $response, $Id): array
    {
        $arrayResponse = [];

        foreach ($response as $result) {
            $arrayResponse[] = $result[$Id];
        }

        return $arrayResponse;
    }

    public function fetchAllInstantlyActivatedMerchants(int $from, int $to)
    {
        return $this->newQuery()
            ->leftJoin(Table::MERCHANT_DETAIL, Entity::ID, Detail\Entity::MERCHANT_ID)
            ->select(Entity::ID)
            ->where(Entity::LIVE, '=', 1)
            ->where(Entity::ACTIVATED, '=', 1)
            ->whereBetween(Entity::ACTIVATED_AT,[$from, $to])
            ->where(Detail\Entity::ACTIVATION_STATUS, '=', Detail\Status::INSTANTLY_ACTIVATED)
            ->whereNull(Entity::SUSPENDED_AT)
            ->where(Entity::BUSINESS_BANKING, '=', false)
            ->where(Entity::ORG_ID, '=', Org\Entity::RAZORPAY_ORG_ID)
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

    public function fetchMerchantFromId($merchantId)
    {
        return  $this->findOrFail($merchantId);
    }

    public function getCreatedAtForTheMerchant($merchantId)
    {
        return $this->newQuery()
                    ->select(Entity::CREATED_AT)
                    ->where(Entity::ID, $merchantId)
                    ->get()
                    ->pluck(Entity::CREATED_AT)
                    ->pop();
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
        $tag = Constants::PARTNER_REFERRAL_TAG_PREFIX.$merchantId;

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

        $businessDetailSelector = function($query)
        {
            $fields = $this->esRepo->getMerchantBusinessDetailsIndexedFields();

            $query->select($fields);
        };

        $with = [
            camel_case(Entity::MERCHANT_DETAIL) => $detailSelector,
            Entity::GROUPS                      => $groupSelector,
            Entity::ADMINS                      => $adminSelector,
            Entity::FEATURES                    => function () {},
            'primaryBalance'                    => $balanceSelector,
            camel_case(Entity::MERCHANT_BUSINESS_DETAIL) => $businessDetailSelector,
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
        //- SELECT <fields> from merchant_business_details
        //  WHERE merchant_business_details.merchant_id IN (?);
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
        // - Two fields from merchant_business_details.

        $serialized[Entity::TAG_LIST]        = $entity->tagNames();
        $serialized[Entity::MERCHANT_DETAIL] = $entity->merchantDetail ? $entity->merchantDetail->toArray() : [];
        $serialized[Entity::MERCHANT_BUSINESS_DETAIL] = $entity->merchantDetail ? $entity->merchantDetail->getBusinessAttributes() : [];
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
                                           string $product = null,
                                           bool $useWritePdo = false)
    {
        $product = $product ?? $this->auth->getRequestOriginProduct();

        $mode = $this->app['rzp.mode'] ?? MODE::LIVE;

        $query = $useWritePdo === true ?  $this->newQueryWithConnection($mode)->useWritePdo() : $this->newQuery();

        $query = $query->find($merchantId)
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
     * @param string     $submerchantId
     * @param string     $appId
     * @param array|null $params
     *
     * @return Entity
     */
    public function findSubmerchantByIdAndConnectedAppId(string $submerchantId, string $appId, array $params = null): Entity
    {
        //
        // To make use of existing function (buildQueryToFetchSubmerchantsByAppIds) which uses an array for appIds,
        // we convert the only app id and submerchant id that we have to an array.
        //
        $appIds         = [$appId];
        $submerchantIds = [$submerchantId];

        $query = $this->buildQueryToFetchSubmerchantsByAppIds($appIds, $submerchantIds);

        $this->buildQueryWithParams($query, $params);

        $query->orderBy(Table::MERCHANT . '.' . Entity::CREATED_AT, 'desc')
              ->orderBy(Table::MERCHANT . '.' . Entity::ID, 'desc');

        return $query->firstOrFailPublic();

    }

    /**
     * @param array $applicationIds
     * @param array $params
     *
     * @param array $relations
     *
     * @return PublicCollection
     */
    public function listSubmerchantsDetailsAndUsers(
        array $applicationIds, array $params = [], array $relations = ['owners']
    ): Base\PublicCollection
    {
        $merchantDetailsRepo = $this->repo->merchant_detail;
        if (empty($applicationIds) === true)
        {
            return new Base\PublicCollection;
        }

        $submerchantIds = $params[Entity::MERCHANT_ID] ?? [];
        unset($params[Entity::MERCHANT_ID]);

        $query = $this->buildQueryToFetchSubmerchantDetailsByAppIds($applicationIds, $submerchantIds);

        // add contact no filter
        if (empty($params[Detail\Entity::CONTACT_MOBILE]) === false ) {
            $query->where($merchantDetailsRepo->dbColumn(Detail\Entity::CONTACT_MOBILE),$params[Detail\Entity::CONTACT_MOBILE]);
            unset($params[Detail\Entity::CONTACT_MOBILE]);
        }

        $this->buildQueryWithParams($query, $params);

        $query->orderBy(Table::MERCHANT . '.' . Entity::CREATED_AT, 'desc')
            ->orderBy(Table::MERCHANT . '.' . Entity::ID, 'desc');

        $submerchants = $query->get();

        return $submerchants;
    }

    /**
     * Builds the query to fetch sub-merchants' details of a Partner for FetchSubMerchantMultiple API.
     *
     * @param array $applicationIds
     * @param array $submerchantIds
     *
     */
    protected function buildQueryToFetchSubmerchantDetailsByAppIds(array $applicationIds, array $submerchantIds = [])
    {
        $accessMapRepo       = $this->repo->merchant_access_map;
        $merchantDetailsRepo = $this->repo->merchant_detail;

        $merchantsMerchantId = $this->dbColumn(Entity::ID);

        $accessMapsEntityId   = $accessMapRepo->dbColumn(AccessMap\Entity::ENTITY_ID);
        $accessMapsDeletedAt  = $accessMapRepo->dbColumn(AccessMap\Entity::DELETED_AT);
        $accessMapsEntityType = $accessMapRepo->dbColumn(AccessMap\Entity::ENTITY_TYPE);
        $accessMapsMerchantId = $accessMapRepo->dbColumn(AccessMap\Entity::MERCHANT_ID);

        $merchantDetailsColumns    = [$merchantDetailsRepo->dbColumn(Detail\Entity::ACTIVATION_STATUS)];
        $merchantDetailsMerchantId = $merchantDetailsRepo->dbColumn(Detail\Entity::MERCHANT_ID);

        $merchantAttributes = [
            $this->dbColumn(Entity::ID),    $this->dbColumn(Entity::NAME),
            $this->dbColumn(Entity::EMAIL), $this->dbColumn(Entity::HOLD_FUNDS),
            $this->dbColumn(Entity::CREATED_AT)
        ];
        $attributes = array_merge(
            $merchantAttributes,
            $merchantDetailsColumns,
            [$accessMapsEntityId . ' as ' . Constants::APPLICATION_ID]
        );

        //
        // merchantDetail is not fetched as a relation below because
        // a filter has to be added for merchantDetail.activation_status in the query
        //
        $query = $this->newQuery()
            ->with(['owners'])
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

    /**
     * @param array $applicationIds
     * @param array $params
     *
     * @param array $relations
     *
     * @return PublicCollection
     */
    public function fetchSubmerchantsByAppIds(array $applicationIds, array $params = [], array $relations = []): Base\PublicCollection
    {
        $merchantDetailsRepo = $this->repo->merchant_detail;

        if (empty($applicationIds) === true)
        {
            return new Base\PublicCollection;
        }

        $submerchantIds = $params[Entity::MERCHANT_ID] ?? [];

        unset($params[Entity::MERCHANT_ID]);

        $query = $this->buildQueryToFetchSubmerchantsByAppIds($applicationIds, $submerchantIds, $relations);

        // add contact no filter
        if (empty($params[Detail\Entity::CONTACT_MOBILE]) === false ) {
            $query->where($merchantDetailsRepo->dbColumn(Detail\Entity::CONTACT_MOBILE),$params[Detail\Entity::CONTACT_MOBILE]);
            unset($params[Detail\Entity::CONTACT_MOBILE]);
        }

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

    public function fetchMerchantsByParams(array $filters)
    {
        $merchantDetailsRepo = $this->repo->merchant_detail;

        //Please connect with terminals team before modifying this or adding any new fetch attribute
        // as this is used for IIR dashboard.
        $attributes = [
            $this->dbColumn(Entity::ID),
            $merchantDetailsRepo->dbColumn(Detail\Entity::BUSINESS_CATEGORY),
            $merchantDetailsRepo->dbColumn(Detail\Entity::BUSINESS_SUBCATEGORY),
            $merchantDetailsRepo->dbColumn(Detail\Entity::BUSINESS_TYPE),
            $this->dbColumn(Entity::WEBSITE),
            $this->dbColumn(Entity::CATEGORY2),
            $this->dbColumn(Entity::ORG_ID),
        ];

        $merchantsMerchantId = $this->dbColumn(Entity::ID);
        $merchantDetailsMerchantId = $merchantDetailsRepo->dbColumn(Detail\Entity::MERCHANT_ID);
        $activated     = $this->dbColumn(Entity::ACTIVATED);
        $activatedAt   = $this->dbColumn(Entity::ACTIVATED_AT);

        $startTime = millitime();

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->select($attributes)
            ->join(Table::MERCHANT_DETAIL, $merchantsMerchantId, $merchantDetailsMerchantId);
        //Removing merchant activation check from filter for unblocking banking ops to take action on IIRs of non-activated merchants, should be reverted in the future.
        // Ref. thread: https://razorpay.slack.com/archives/CNV2GTFEG/p1639122072426800
        //    ->whereNotNull($activatedAt)
        //    ->where($activated, 1);

        foreach ($filters as $attributeKey => $attributeValue)
        {
            switch ($attributeKey)
            {
                case Entity::ORG_ID:
                    $orgId = Org\Entity::silentlyStripSign($filters[Entity::ORG_ID]);
                    $query->where(Entity::ORG_ID, $orgId);
                    break;

                case 'merchant_ids':
                    if((isset($filters['merchant_ids']) === true) and (empty($filters['merchant_ids']) === false))
                    {
                        $query->whereIn(Entity::ID, $filters['merchant_ids']);
                    }
                    break;

                default:
                    $query->where($attributeKey, $attributeValue);
            }
        }

        $this->trace->info(
            TraceCode::FETCH_MERCHANTS_BY_PARAMS_TIME_TAKEN,
            [
                'filters'            => $filters,
                'time_taken'         => millitime() - $startTime,
            ]);

        return $query->orderBy(Entity::ACTIVATED_AT, 'desc')->get();
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

    /**
     * @param   string          $appId
     * @param   string          $partnerId
     * @param   string|null     $mode connection mode
     *
     * @return  Base\PublicCollection
     */
    public function getSubMerchantsForPartnerAndApplication(string $appId, string $partnerId, string $mode = null)
    {
        $accessMapRepo = $this->repo->merchant_access_map;

        $accessMapMerchantId = $accessMapRepo->dbColumn(AccessMap\Entity::MERCHANT_ID);
        $accessMapOwnerId    = $accessMapRepo->dbColumn(AccessMap\Entity::ENTITY_OWNER_ID);
        $accessMapEntityId   = $accessMapRepo->dbColumn(AccessMap\Entity::ENTITY_ID);

        $merchantsId         = $this->dbColumn(Entity::ID);

        $query = ($mode === null) ? $this->newQuery() : $this->newQueryWithConnection($mode);

        return $query->select($this->getTableName() . '.*')
                     ->join(Table::MERCHANT_ACCESS_MAP, $accessMapMerchantId, $merchantsId)
                     ->where($accessMapOwnerId, $partnerId)
                     ->where($accessMapEntityId, $appId)
                     ->get();
    }

    /**
     * Fetch merchants in sync for given appId and partner's MID.
     * It fails if data is not in sync in test and live DB.
     *
     * @param   string  $appId
     * @param   string  $partnerId
     * @return  Base\PublicCollection
     * @throws  LogicException
     */
    public function getSubMerchantsForPartnerAndAppInSyncOrFail(string $appId, string $partnerId) : Base\PublicCollection
    {
        $liveEntities = $this->getSubMerchantsForPartnerAndApplication($appId, $partnerId, 'live');
        $testEntities = $this->getSubMerchantsForPartnerAndApplication($appId, $partnerId, 'test');
        $isSynced = $this->areEntitiesSyncOnLiveAndTest($liveEntities, $testEntities);
        if ($isSynced === true)
        {
            return $liveEntities;
        }
        else
        {
            $this->trace->critical(
                TraceCode::DATA_MISMATCH_ON_LIVE_AND_TEST,
                [
                    'on_live' => $liveEntities,
                    'on_test' => $testEntities
                ]
            );
            throw new LogicException("Data is not synced on Live and Test DB");
        }
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

    private function joinMerchantUsers(BuilderEx & $query, string $product = Product::PRIMARY)
    {
        $merchantUsersRepo = $this->repo->merchant_user;

        $merchantUsersMerchantId = $merchantUsersRepo->dbColumn(MerchantUser\Entity::MERCHANT_ID);

        $merchantUsersProduct = $merchantUsersRepo->dbColumn(MerchantUser\Entity::PRODUCT);

        $merchantsMerchantId = $this->dbColumn(Entity::ID);

        $query->join(Table::MERCHANT_USERS, $merchantsMerchantId, '=', $merchantUsersMerchantId)
              ->where($merchantUsersProduct, $product)
              ->distinct();
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

    public function fetchAllMerchantIDsFromSlaveDB($input)
    {
        $query = $this->newQueryWithConnection($this->getAccountServiceReplicaConnection())->select([Entity::ID])->orderBy(Entity::ID);

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

    public function fetchUnsuspendedLinkedAccountMids($merchantId, $offset = 0)
    {
        $childMerchantIds = $this->newQueryWithConnection($this->getSlaveConnection())
                                 ->select(Entity::ID)
                                 ->where(Entity::PARENT_ID, $merchantId)
                                 ->whereNull(Entity::SUSPENDED_AT)
                                 ->offset($offset)
                                 ->limit(1000)
                                 ->get()
                                 ->pluck(Entity::ID)
                                 ->toArray();

        return $childMerchantIds;
    }

    public function fetchLinkedAccountMidsSuspendedDueToParentMerchantSuspension($merchantId, $offset = 0)
    {
        $childMerchantIds = $this->newQueryWithConnection($this->getSlaveConnection())
                                 ->select(Entity::ID)
                                 ->where(Entity::PARENT_ID, $merchantId)
                                 ->whereNotNull(Entity::SUSPENDED_AT)
                                 ->where(Entity::HOLD_FUNDS_REASON, Constants::ACCOUNT_SUSPENDED_DUE_TO_PARENT_MERCHANT_SUSPENSION)
                                 ->offset($offset)
                                 ->limit(1000)
                                 ->get()
                                 ->pluck(Entity::ID)
                                 ->toArray();

        return $childMerchantIds;
    }

    public function fetchActivatedLinkedAccountIdsForParentMerchant(string $parentMerchantId)
    {
        return $this->newQuery()
                    ->select(Entity::ID)
                    ->where(Entity::PARENT_ID, $parentMerchantId)
                    ->where(Entity::ACTIVATED, 1)
                    ->pluck(Entity::ID)
                    ->toArray();
    }

    public function fetchLinkedAccountsCount($merchantId)
    {
        $childMerchantIds = $this->newQuery()
                                 ->select(Entity::ID)
                                 ->where('parent_id', $merchantId)
                                 ->count();

        return $childMerchantIds;
    }

    public function fetchAllActiveLinkedAccounts(int $createdAt=null)
    {
        $query = $this->newQuery()
            ->whereNotNull(Entity::PARENT_ID)
            ->select(Entity::ID)
            ->where(Entity::ACTIVATED, 1);

        if (empty($createdAt) === false)
        {
            $query->where($this->dbColumn(Entity::CREATED_AT), '>=', $createdAt);
        }

        return $query->get()
            ->pluck(Entity::ID)
            ->toArray();
    }

    public function fetchAllMids($offsetID,$limit)
    {
        $query=$this->newQuery()
                    ->select(Entity::ID)
                    ->orderBy(Entity::CREATED_AT,'asc');

        if ($offsetID!=null)
        {
            $query=$query->where(Entity::ID, '>', $offsetID);
        }

        return $query->limit($limit)
                     ->get()
                     ->pluck(Entity::ID)
                     ->toArray();
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

    public function fetchAggregatorPartners($limit = null, $afterId = null)
    {
        $query = $this->newQuery()
                      ->select(Entity::ID)
                      ->where(Entity::PARTNER_TYPE, Constants::AGGREGATOR)
                      ->orderBy(Entity::ID);

        if (empty($limit) === false)
        {
            $query->take($limit);
        }

        if (empty($afterId) === false)
        {
            $query->where(Entity::ID, '>', $afterId);
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

    public function getMerchantsFromMerchantIdList(array $merchantIds)
    {
        if (empty($merchantIds) === true) {
            return [];
        }

        return $this->newQuery()
            ->whereIn(Entity::ID, $merchantIds)
            ->get();
    }

    public function filterMerchantIdsWithMinActivatedTime(array $mids, int $minActivatedTime,array $orgIdList = [Org\Entity::RAZORPAY_ORG_ID]): array
    {
        $timestamp                 = Carbon::now(Timezone::IST)->getTimestamp();
        $merchantActivatedAtColumn = $this->repo->merchant->dbColumn(Entity::ACTIVATED_AT);
        $merchantOrgIdColumn       = $this->repo->merchant->dbColumn(Entity::ORG_ID);

        return $this->newQuery()
                    ->select(Entity::ID)
                    ->whereIn(Entity::ID, $mids)
                    ->whereIn($merchantOrgIdColumn, $orgIdList)
                    ->where($merchantActivatedAtColumn, "<=", $timestamp - $minActivatedTime)
                    ->get()
                    ->pluck(Entity::ID)
                    ->toArray();
    }

    public function getActivatedSubMInPastDays(string $partnerMerchantId, int $pastDays, int $limit){
        $pastDaysTimestamp        = Carbon::now()->subDays($pastDays)->getTimestamp();

        $merchantId               = $this->dbColumn(Entity::ID);
        $activatedAt              = $this->dbColumn(Entity::ACTIVATED_AT);
        $merchantDetailRepo       = $this->repo->merchant_detail;
        $activationStatus         = $merchantDetailRepo->dbColumn(Detail\Entity::ACTIVATION_STATUS);
        $merchantDetailMerchantId = $merchantDetailRepo->dbColumn(Detail\Entity::MERCHANT_ID);

        $accessMapRepo           = $this->repo->merchant_access_map;
        $accessMapsMerchantId    = $accessMapRepo->dbColumn(AccessMap\Entity::MERCHANT_ID);
        $accessMapsEntityOwnerId = $accessMapRepo->dbColumn(AccessMap\Entity::ENTITY_OWNER_ID);

        return $this->newQueryWithConnection($this->getSlaveConnection())
                             ->join(Table::MERCHANT_ACCESS_MAP, $merchantId, $accessMapsMerchantId)
                             ->leftJoin(Table::MERCHANT_DETAIL, $merchantId, $merchantDetailMerchantId)
                             ->select($merchantId)
                             ->where($accessMapsEntityOwnerId, $partnerMerchantId)
                             ->where($activatedAt, '>', $pastDaysTimestamp)
                             ->whereIn($activationStatus, [
                                 Detail\Status::ACTIVATED,
                                 Detail\Status::ACTIVATED_KYC_PENDING,
                                 Detail\Status::ACTIVATED_MCC_PENDING
                             ])
                             ->take($limit)
                             ->get()
                             ->pluck(Entity::ID)
                             ->toArray();
    }

    public function getRejectedSubMInPastDays(string $partnerMerchantId, int $pastDays, int $limit){
        $pastDaysTimestamp        = Carbon::now()->subDays($pastDays)->getTimestamp();

        $actionStateRepo      = $this->repo->action_state;
        $actionStateEntityId  = $actionStateRepo->dbColumn(ActionState::ENTITY_ID);
        $actionStateName      = $actionStateRepo->dbColumn(ActionState::NAME);
        $actionStateCreatedAt = $actionStateRepo->dbColumn(ActionState::CREATED_AT);

        $accessMapRepo           = $this->repo->merchant_access_map;
        $accessMapsMerchantId    = $accessMapRepo->dbColumn(AccessMap\Entity::MERCHANT_ID);
        $accessMapsEntityOwnerId = $accessMapRepo->dbColumn(AccessMap\Entity::ENTITY_OWNER_ID);

        return $actionStateRepo->newQueryWithConnection($this->getSlaveConnection())
                                       ->join(Table::MERCHANT_ACCESS_MAP, $actionStateEntityId, $accessMapsMerchantId)
                                       ->select($actionStateEntityId)
                                       ->where($accessMapsEntityOwnerId, $partnerMerchantId)
                                       ->where($actionStateName, Detail\Status::REJECTED)
                                       ->where($actionStateCreatedAt, '>', $pastDaysTimestamp)
                                       ->take($limit)
                                       ->get()
                                       ->pluck(ActionState::ENTITY_ID)
                                       ->toArray();
    }

    public function getSubmerchantIdsInTerminalStateInPastDays(string $partnerMerchantId, int $pastDays, int $limit)
    {
        $activatedIds = $this->getActivatedSubMInPastDays($partnerMerchantId, $pastDays, $limit);

        $rejectedIds = $this->getRejectedSubMInPastDays($partnerMerchantId, $pastDays, $limit);

        return array_merge($activatedIds, $rejectedIds);
    }

    public function getMerchantListEligibleForRTB($blacklistedOrWhitelistedMIDs): Collection
    {
        $merchantId = $this->dbColumn(Entity::ID);
        $orgId = $this->dbColumn(Entity::ORG_ID);
        $activatedAt = $this->dbColumn(Entity::ACTIVATED_AT);
        $mccCode = $this->dbColumn(Entity::CATEGORY);
        $category2 = $this->dbColumn(Entity::CATEGORY2);

        $merchantDetailRepo = $this->repo->merchant_detail;
        $activationStatus = $merchantDetailRepo->dbColumn(Detail\Entity::ACTIVATION_STATUS);
        $businessType = $merchantDetailRepo->dbColumn(Detail\Entity::BUSINESS_TYPE);

        $excludedBusinessTypeList =  Detail\BusinessType::getIndexForUnregisteredBusiness();
        $threeMonthsAgoTimestamp = Carbon::today()->subDays(90)->getTimestamp();

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->join(Table::MERCHANT_DETAIL, Entity::ID, Detail\Entity::MERCHANT_ID)
            ->select($merchantId)
            ->where($orgId, '=', Org\Entity::RAZORPAY_ORG_ID)
            ->where($activatedAt, '<', $threeMonthsAgoTimestamp)
            ->where($activationStatus, '=', Detail\Status::ACTIVATED)
            ->where(static function($query) use ($category2, $mccCode)
            {
                $query->whereNotIn($category2, TrustedBadgeConstants::EXCLUDED_CATEGORY_LIST)
                    ->orWhere(static function ($query) use ($category2, $mccCode)
                    {
                        $query->whereNull($category2)
                              ->whereNotIn($mccCode, TrustedBadgeConstants::EXCLUDED_MCC_CODE_LIST);
                    });
            })
            ->whereNotIn($businessType, $excludedBusinessTypeList)
            ->whereNotIn($merchantId, $blacklistedOrWhitelistedMIDs);

        return $query->pluck($merchantId);
    }

    /**
     * this method takes in list of merchant ids and returns corresponding category2 for them
     * sample output : ['mid1'=>'ecommerce', 'mid2'=>'healthcare']
     * @param array $merchantIds
     * @return mixed
     */
    public function getMerchantsCategories(array $merchantIds): array
    {
        if (empty($merchantIds) === true)
        {
            return [];
        }

        $merchantId = $this->dbColumn(Entity::ID);
        $category = $this->dbColumn(Entity::CATEGORY2);

        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->select($category, $merchantId)
            ->whereIn($merchantId, $merchantIds)
            ->pluck($category, $merchantId)
            ->toArray();
    }

    public function getMerchantsForSettlementsEventsCron($updatedAtFrom, $updateAtTo)
    {
        $query = $this->newQueryWithConnection($this->getReportingReplicaConnection())
                      ->where(Entity::UPDATED_AT, '>=', $updatedAtFrom)
                      ->where(Entity::UPDATED_AT, '<=', $updateAtTo)
                      ->orderBy(Entity::UPDATED_AT, 'asc');

        return $query->get();
    }

    public function fetchMerchantsCreatedBetweenOfOrg($from, $to, $org = Org\Entity::RAZORPAY_ORG_ID)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->whereBetween(Entity::CREATED_AT, [$from, $to])
            ->where(Entity::ORG_ID, '=' , $org)
            ->where(Entity::BUSINESS_BANKING, '=', false)
            ->get()
            ->pluck(Entity::ID)
            ->toArray();

    }

    public function findManyOnReadReplica(array $merchantIds)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->findMany($merchantIds);
    }

    public function fetchMerchantsWithNotOnboardedOnNetworks($product, array $networks, $limit)
    {
        return $this->repo->useSlave(function () use ($product,$networks,$limit){
            $merchantAttributeTable = $this->repo->merchant_attribute->getTableName();
            $prodColumn  = $this->repo->merchant_attribute->dbColumn(Attribute\Entity::PRODUCT);
            $idColumn = $this->dbColumn(Entity::ID);

            return $this->newQuery()
                ->select($idColumn)
                ->leftJoin(
                    $merchantAttributeTable,
                    function(JoinClause $join) use($product,$networks) {
                        $midColumn   = $this->repo->merchant_attribute->dbColumn(Attribute\Entity::MERCHANT_ID);
                        $prodColumn  = $this->repo->merchant_attribute->dbColumn(Attribute\Entity::PRODUCT);
                        $groupColumn = $this->repo->merchant_attribute->dbColumn(Attribute\Entity::GROUP);

                        $idColumn = $this->dbColumn(Entity::ID);

                        $join->on($idColumn,$midColumn);
                        $join->where($prodColumn,$product);
                        $join->whereIn($groupColumn,$networks);
                    }
                )
                ->whereNull($prodColumn)
                ->where(Entity::ACTIVATED,"=",1)
                ->where(Entity::INTERNATIONAL,"=",1)
                ->limit($limit)
                ->distinct()
                ->get()
                ->pluck(Entity::ID)
                ->toArray();
        });
    }

    /**
     * __saveOrFail -  Keeping the method name not same with base repository method, this to be renamed  and used in merchant core while ramp-up
     *Once stakeholder saveOrFail is migrated to Account service only this method should be used while saving the merchant entity any save on merchant entity has to be called at any new place
     * @param MerchantEntity $entity
     * @param bool $testAndLive - If true saveEntity on both test and live db else only live db
     * @throws \Throwable
     */
    public function __saveOrFail(MerchantEntity $entity, bool $testAndLive)
    {
        $this->repo->transactionOnLiveAndTest(function () use ($testAndLive, $entity) {
            if ($testAndLive === true) {
                $this->saveOrFail($entity);
            } else {
                $this->repo->saveOrFail($entity);
            }
            (new MerchantWrapper())->SaveOrFail($entity);
        });
    }

    public function fetchMerchantsUnifiedDashboard(array $params,
                               string $merchantId = null,
                               string $connectionType = null): array
    {
        // Process params (sanitization, validation, modification, etc.)

        $this->processFetchParams($params);

        $this->attachRoleBasedQueryParams($params);

        $this->setEsRepoIfExist();

        $startTimeMs = round(microtime(true) * 1000);

        list($mysqlParams, $esParams) = $this->getMysqlAndEsParams($params);

        $esSearchResult = $this->runEsFetchUnifiedDashboard($esParams);

        $endTimeMs = round(microtime(true) * 1000);

        $queryDuration = $endTimeMs - $startTimeMs;

        if($queryDuration > 100) {
            $this->trace->info(TraceCode::ES_SEARCH_RESPONSE_DURATION, [
                'duration_ms' => $queryDuration,
            ]);
        }

        return $esSearchResult;
    }

    protected function runEsFetchUnifiedDashboard(
        array $params): array
    {
        $startTimeMs = round(microtime(true) * 1000);

        $count = $params['count']?? 10;
        $skip = $params['skip']?? 0;

        $params['count'] = 0;
        $params['skip'] = 0;

        $response = $this->esRepo->buildQueryAndSearch($params);

        $total_merchants_onboarded = $response[ES::HITS]['total'];

        $params['count'] = $count;
        $params['skip'] = $skip;

        $response = $this->esRepo->buildQueryAndSearch($params);

        $endTimeMs = round(microtime(true) * 1000);

        $queryDuration = $endTimeMs - $startTimeMs;

        if($queryDuration > 100) {
            $this->trace->info(TraceCode::ES_SEARCH_DURATION, [
                'duration_ms' => $queryDuration,
                'function'    => 'runESSearch',
            ]);
        }

        // Extract results from ES response. If hit has _source get that else just the document id.
        $result = array_map(
            function ($res)
            {
                return $res[ES::_SOURCE] ?? [Common::ID => $res[ES::_ID]];
            },
            $response[ES::HITS][ES::HITS]);

        if (count($result) === 0)
        {
            $newEntities = (new PublicCollection)->toArrayAdmin();
            $newEntities['total_merchants_onboarded'] = 0;
            return $newEntities;
        }

        $entities = $this->hydrate($result)->toArrayAdmin();

        $entities['total_merchants_onboarded'] = $total_merchants_onboarded;

        return $entities;
    }

    public function updateLinkedAccountsAsSuspendedOrUnsuspendedInBulk(array $linkedAccountMids, bool $shouldSuspend)
    {
        $countOfLinkedAccounts = count($linkedAccountMids);

        if ($countOfLinkedAccounts === 0)
        {
            return 0;
        }

        if ($shouldSuspend === true)
        {
            $updateColumnValues = [
                Entity::SUSPENDED_AT      => time(),
                Entity::LIVE              => false,
                Entity::HOLD_FUNDS        => true,
                Entity::HOLD_FUNDS_REASON => Constants::ACCOUNT_SUSPENDED_DUE_TO_PARENT_MERCHANT_SUSPENSION
            ];
        }
        else
        {
            $updateColumnValues = [
                Entity::SUSPENDED_AT       => null,
                Entity::LIVE               => true,
                Entity::HOLD_FUNDS         => false,
                Entity::HOLD_FUNDS_REASON  => null,
            ];
        }

        foreach ([Mode::LIVE, Mode::TEST] as $mode)
        {
            $updatedCount = $this->newQueryWithConnection($mode)
                ->whereIn(Entity::ID, $linkedAccountMids)
                ->update($updateColumnValues);

            if ($updatedCount !== $countOfLinkedAccounts)
            {
                throw new Exception\LogicException(
                    'Failed to update status for expected number of linked accounts',
                    null,
                    [
                        'suspend'  => $shouldSuspend,
                        'expected' => $countOfLinkedAccounts,
                        'updated'  => $updatedCount,
                    ]);
            }
        }
    }

    public function filterNonBusinessBankingMerchants(array $merchantIds)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->whereIn(Entity::ID, $merchantIds)
                    ->where(Entity::BUSINESS_BANKING, '=', false)
                    ->get()
                    ->pluck(Entity::ID)
                    ->toArray();
    }
}

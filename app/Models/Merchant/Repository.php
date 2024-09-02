<?php

namespace RZP\Models\Merchant;

use Database\Connection;
use DB;
use Closure;
use Throwable;
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
use RZP\Exception\BaseException;
use Rzp\Wda_php\WDAQueryBuilder;
use RZP\Models\Terminal\Category;
use RZP\Models\Base\EsRepository;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\TrustedBadge\Constants as TrustedBadgeConstants;
use RZP\Models\Partner\Activation;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\BusinessDetail;
use RZP\Models\State\Entity as ActionState;
use RZP\Models\Base\QueryCache\CacheQueries;
use RZP\Models\Partner\Config as PartnerConfig;
use RZP\Models\Merchant\Acs\Traits\AsvEntityConnection;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Fraud\HealthChecker as HealthChecker;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Modules\Acs\Wrapper\Merchant as MerchantWrapper;
use RZP\Models\Merchant\Acs\Traits\AsvFindWithCache;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Merchant as AsvSdkMerchantQuery;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLiveAndAsv;
    use AsvEntityConnection;
    use AsvFindWithCache;

    function __construct()
    {
        parent::__construct();

        $this->asvRouter = new AsvRouter();
    }

    //
    // Possible values for sub_accounts(other than merchant id) query:
    // - '1': Include only sub accounts in searched results
    // - '0': Exclude sub accounts from searched results
    //
    const SUB_ACCOUNTS_ONLY_VALUE     = '1';
    const SUB_ACCOUNTS_EXCLUDED_VALUE = '0';

    const ACTIVATED_SUBM_LAST_N_DAYS_DL_QUERY   = "SELECT m.id FROM hive.realtime_hudi_api.merchants AS m INNER JOIN hive.realtime_hudi_api.merchant_access_map AS mam ON m.id = mam.merchant_id LEFT JOIN hive.realtime_hudi_api.merchant_details AS md ON m.id = md.merchant_id WHERE mam.entity_owner_id = '%s' AND m.activated_at > %d AND md.activation_status IN ( '%s') LIMIT %d";
    const REJECTED_SUBM_LAST_N_DAYS_DL_QUERY    = "select a.entity_id from hive.realtime_hudi_api.action_state as a inner join hive.realtime_hudi_api.merchant_access_map as mam on a.entity_id = mam.merchant_id where mam.entity_owner_id = '%s' and a.name = '%s' and a.created_at > %d limit %d";
    const AGGREGATOR_PARTNERS_DL_QUERY          = "SELECT id FROM hive.realtime_hudi_api.merchants WHERE partner_type = 'aggregator'";

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

        return $this->repo->transactionOnLiveAndTestAndAsv(function () use ($id) {
            $merchantFromApi = $this->findOrFail($id);
            return (new MerchantWrapper())->FindOrFail($id, $merchantFromApi);
        });
    }

    public function __findOrFailPublic($id) {

        return $this->repo->transactionOnLiveAndTestAndAsv(function () use ($id) {
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

    /**
     * @param string $legalEntityId
     *
     * @return EloquentCollection|PublicCollection
     * @throws Exception\BadRequestException
     * @throws Exception\BaseException
     */
    public function fetchMerchantsByLegalEntityId(string $legalEntityId): EloquentCollection|PublicCollection
    {
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->isTransactionActive())
            {
                $query = $this->newQueryWithConnection(
                    $this->getConnectionFromType(Connection::ASV_WRITER)
                );
            }
            else
            {
                $results = (new Acs\AsvSdkIntegration\Merchant())->fetchMerchantsByLegalEntityId($legalEntityId);

                $this->resetConnectionOnModels($results);

                return $results;
            }
        }
        else
        {
            $query = $this->newQuery();
        }

        $results = $query->where(Entity::LEGAL_ENTITY_ID, $legalEntityId)->whereNotNull(Entity::LEGAL_ENTITY_ID)->get();

        $this->resetConnectionOnModels($results);

        return $results;
    }

    /**
     * @throws BaseException
     * @throws BadRequestException
     */
    public function fetchActivatedMerchantsBeforeTimestamp(
      int $limit,
      int $skip,
      int $end,
      array $merchantIds = [],
      array $merchantIdsExcluded = []): array
    {
        if ($this->asvRouter->shouldRouteBeMigratedToTiDB(__FUNCTION__))
        {
            if ($this->isTransactionActive())
            {
                $query = $this->newQueryWithConnection(
                    $this->getConnectionFromType(Connection::ASV_WRITER));
            }
            else
            {
                $query = $this->newQueryWithConnection(
                    $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT)
                );
            }
        }
        else
        {
            $query = $this->newQueryWithConnection(
                $this->getSlaveConnection()
            );
        }

        $query = $query->select(Entity::ID)
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

    /*
     * Since PG onboarding has been blocked, X has been using a new flag to mark merchants as activated on X
     * Read https://docs.google.com/document/d/1iVUFQu2ZoBBD5CNZoIwX6syw_armtk5TQRQgu9-1D_c/edit
     *
     * select `merchants`.`id` from `merchants` inner join `merchant_attributes` on `merchants`.`id` = `merchant_attributes`.`merchant_id`
     * where `activated` = ? and `merchant_attributes`.`product` = ? and `merchant_attributes`.`group` = ? and `merchant_attributes`.`type` = ?
     * and `merchant_attributes`.`value` = ? and `merchant_attributes`.`created_at` between ? and ? and (`parent_id` not in (?) or `parent_id` is null)
     * limit 10000 offset 0
     * DBA thread - https://razorpay.slack.com/archives/C3BPZHG8P/p1686285370722479
     */
    public function fetchMerchantsActivatedViaNewBankingActivationFlagBetweenTimestamps(
        int $limit,
        int $skip,
        int $fromTimestamp,
        int $toTimestamp,
        array $merchantIds = [],
        array $merchantIdsExcluded = []): array
    {
        $merchantIdCol = $this->dbColumn(Entity::ID);

        $merchantAttributeMerchantId = $this->repo->merchant_attribute->dbColumn(Attribute\Entity::MERCHANT_ID);
        $merchantAttributeProduct = $this->repo->merchant_attribute->dbColumn(Attribute\Entity::PRODUCT);
        $merchantAttributeGroup = $this->repo->merchant_attribute->dbColumn(Attribute\Entity::GROUP);
        $merchantAttributeType = $this->repo->merchant_attribute->dbColumn(Attribute\Entity::TYPE);
        $merchantAttributeValue = $this->repo->merchant_attribute->dbColumn(Attribute\Entity::VALUE);
        $merchantAttributeCreatedAt = $this->repo->merchant_attribute->dbColumn(Attribute\Entity::CREATED_AT);

        $query = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT))
            ->join(Table::MERCHANT_ATTRIBUTE, $merchantIdCol, '=', $merchantAttributeMerchantId)
            ->select($merchantIdCol)
            ->where(Entity::ACTIVATED, '=', 0)
            ->where($merchantAttributeProduct, '=', Product::BANKING)
            ->where($merchantAttributeGroup, '=', Attribute\Group::PRODUCTS_ENABLED)
            ->where($merchantAttributeType, '=', Attribute\Type::X)
            ->where($merchantAttributeValue, '=', 'true')
            ->whereBetween($merchantAttributeCreatedAt, [$fromTimestamp, $toTimestamp])
            ->where(function ($query)
            {
                $query->whereNotIn(Entity::PARENT_ID, Preferences::NO_MERCHANT_INVOICE_PARENT_MIDS)
                    ->orWhereNull(Entity::PARENT_ID);
            })
            ->take($limit)
            ->skip($skip);

        if (empty($merchantIds) === false)
        {
            $query = $query->whereIn($merchantIdCol, $merchantIds);
        }

        if (empty($merchantIdsExcluded) === false)
        {
            $query = $query->whereNotIn($merchantIdCol, $merchantIdsExcluded);
        }

        return $query->get()
            ->pluck(Entity::ID)
            ->toArray();
    }

    // DBA thread: https://razorpay.slack.com/archives/C3BPZHG8P/p1691660404405689
    public function fetchMerchantsDeactivatedBetweenTimestamps(
        int $limit,
        int $skip,
        int $fromTimestamp,
        int $toTimestamp,
        array $merchantIds = [],
        array $merchantIdsExcluded = []) : array
    {
        $merchantIdCol = $this->dbColumn(Entity::ID);

        $merchantAttributeMerchantId = $this->repo->merchant_attribute->dbColumn(Attribute\Entity::MERCHANT_ID);
        $merchantAttributeProduct = $this->repo->merchant_attribute->dbColumn(Attribute\Entity::PRODUCT);
        $merchantAttributeGroup = $this->repo->merchant_attribute->dbColumn(Attribute\Entity::GROUP);
        $merchantAttributeType = $this->repo->merchant_attribute->dbColumn(Attribute\Entity::TYPE);
        $merchantAttributeUpdatedAt = $this->repo->merchant_attribute->dbColumn(Attribute\Entity::UPDATED_AT);

        $query = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT))
            ->join(Table::MERCHANT_ATTRIBUTE, $merchantIdCol, '=', $merchantAttributeMerchantId)
            ->select($merchantIdCol)
            ->where(Entity::ACTIVATED, '=', 0)
            ->where($merchantAttributeProduct, '=', Product::PRIMARY)
            ->where($merchantAttributeGroup, '=', Attribute\Group::ACTIVATION)
            ->where($merchantAttributeType, '=', Attribute\Type::DEACTIVATED_AT)
            ->whereBetween($merchantAttributeUpdatedAt, [$fromTimestamp, $toTimestamp])
            ->where(function ($query)
            {
                $query->whereNotIn(Entity::PARENT_ID, Preferences::NO_MERCHANT_INVOICE_PARENT_MIDS)
                    ->orWhereNull(Entity::PARENT_ID);
            })
            ->take($limit)
            ->skip($skip);

        if (empty($merchantIds) === false)
        {
            $query = $query->whereIn($merchantIdCol, $merchantIds);
        }

        if (empty($merchantIdsExcluded) === false)
        {
            $query = $query->whereNotIn($merchantIdCol, $merchantIdsExcluded);
        }

        return $query->get()
            ->pluck(Entity::ID)
            ->toArray();
    }

    public function getSharedAccount(): Entity
    {
        if ($this->sharedMerchant === null)
        {
            $this->sharedMerchant = $this->findOrFail(Account::SHARED_ACCOUNT);
        }

        return $this->sharedMerchant;
    }

    public function getMerchantOrg(string $merchantId)
    {
        $merchant = $this->getMerchant($merchantId);

        return $merchant?->org_id;
    }

    public function getMerchant(string $merchantId)
    {
        return $this->findOrFail($merchantId);
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
        if ($this->asvRouter->shouldRouteBeMigratedToTiDB(__FUNCTION__))
        {
            if ($this->isTransactionActive())
            {
                // reference for this change is here:
                // https://razorpay.slack.com/archives/C01DL027FH8/p1713435506888309
                $this->trace->info(TraceCode::FETCH_FEE_BEARER_FOR_PLAN_ID_IN_TXN, [
                    "route_or_worker" => $this->asvRouter->getRouteOrJobName(),
                    "plan_id" => $planId,
                ]);
            }

            $query = $this->newQueryWithConnection(
                $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT)
            );
        }
        else
        {
            $query = $this->newQueryWithConnection(
                $this->getMasterReplicaConnection()
            );
        }

        return $query->where(Entity::PRICING_PLAN_ID, '=', $planId)
                    ->select(Entity::FEE_BEARER)
                    ->distinct()
                    ->get()
                    ->pluck(Entity::FEE_BEARER)
                    ->toArray();
    }

    /**
     * @param $planId
     *
     * @return bool
     * @throws BadRequestException
     * @throws BaseException
     */
    public function checkMerchantsCountWithPricingPlanIdNotEqualOne($planId): bool
    {
        $count = $this->fetchMerchantsCountWithPricingPlanId($planId);

        return $count !== 1;
    }

    /**
     * NOTE: when fetching from ASV, we fetch merchants for a pricing plan ID with LIMIT 2 and
     * then do a count on it.
     * This suffices for the use case of checkMerchantsCountWithPricingPlanIdNotEqualOne
     *
     * For fetching the total count of merchants for a pricing plan ID, define another method and use TiDB
     *
     * @param $planId
     *
     * @return int
     * @throws BadRequestException
     * @throws BaseException
     */
    private function fetchMerchantsCountWithPricingPlanId($planId): int
    {
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->isTransactionActive())
            {
                $query = $this->newQueryWithConnection(
                    $this->getConnectionFromType(Connection::ASV_WRITER)
                );
            }
            else
            {
                $merchants = (new AsvSdkMerchantQuery())->fetchMerchantsWithPricingPlanIdLimit2($planId);

                return $merchants->count();
            }
        }
        else
        {
            $query = $this->newQueryWithConnection($this->getMasterReplicaConnection());
        }

        return $query->where(Entity::PRICING_PLAN_ID, '=', $planId)
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

    /**
     * @param int $from
     * @param int $to
     *
     * @return array
     * @throws BadRequestException
     * @throws BaseException
     */
    public function fetchMerchantsCreatedBetween(int $from, int $to): array
    {
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->isTransactionActive())
            {
                $query = $this->newQueryWithConnection($this->getConnectionFromType(Connection::ASV_WRITER));
            }
            else
            {
                $results        = new PublicCollection();
                $lastMerchantId = '';

                do
                {
                    $subset = (new AsvSdkMerchantQuery())->fetchMerchantsCreatedBetween($from, $to, $lastMerchantId);

                    $lastMerchantId = $subset->pluck(Entity::ID)->last();

                    $results->push(...$subset);

                } while(sizeof($subset) == Acs\AsvSdkIntegration\Base::FETCH_SERVICE_FILTER_LIMIT);

                return $results->pluck('id')->toArray();
            }
        }
        else
        {
            $query = $this->newQueryWithConnection($this->getSlaveConnection());
        }

        return $query->whereBetween(Entity::CREATED_AT, [$from, $to])
            ->get()
            ->pluck(Entity::ID)
            ->toArray();
    }

    public function fetchMerchantsCreatedBetweenForOrg($from, $to, $orgId)
    {
        //NOTE: This change is being done for ASV Decomposition (#platform_account_service)
        //Changing the connection to TiDB as a fallback as no usage was found for this in the past 90 days.
        $query = $this->newQueryWithConnection(
            $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT)
        );

        return $query
            ->where(Entity::ORG_ID, $orgId)
            ->whereBetween(Entity::CREATED_AT, [$from, $to])
            ->get();
    }

    public function fetchMerchantsActivatedBetweenForOrg($from, $to, $orgId)
    {
        //NOTE: This change is being done for ASV Decomposition (#platform_account_service)
        //Changing the connection to TiDB as a fallback as no usage was found for this in the past 90 days.
        $query = $this->newQueryWithConnection(
            $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT)
        );

        return $query
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

    /**
     * @param string $externalId
     *
     * @return array
     * @throws BadRequestException
     * @throws BaseException
     */
    public function findMerchantIdsByExternalIds(string $externalId): array
    {
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->isTransactionActive())
            {
                $query = $this->newQueryWithConnection(
                    $this->getConnectionFromType(Connection::ASV_WRITER)
                );
            }
            else
            {
                $results = (new Acs\AsvSdkIntegration\Merchant())->findMerchantIdsByExternalIds($externalId);
                return $results->pluck(Entity::ID)->toArray();
            }
        }
        else
        {
            $query = $this->newQuery();
        }

        return $query->where(Entity::EXTERNAL_ID, $externalId)
                     ->get()
                     ->pluck(Entity::ID)
                     ->toArray();
    }

    /**
     * @param $skip
     * @param $limit
     *
     * @return array
     */
    public function fetchMerchantIdsInChunk($skip, $limit): array
    {
        if ($this->asvRouter->shouldRouteBeMigratedToTiDB(__FUNCTION__))
        {
            $query = $this->newQueryWithConnection(
                $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT)
            );
        }
        else
        {
            $query = $this->newQuery();
        }

        return $query->where(Entity::LIVE, '=', 1)
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

    public function findOrFailPublicWithRelations(
        string $id,
        array  $relations = [],
        array  $columns = array('*'))
    {
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__)) {
            try {
                $entity = $this->findOrFailPublic($id, $columns);

                $entity->load($relations);

                return $entity;
            } catch (\Throwable $e) {
                $this->trace->traceException($e, Trace::CRITICAL, TraceCode::ACCOUNT_SERVICE_FILTER_QUERY_EXCEPTION, [
                    "identifier" => __FUNCTION__
                ]);
            }
        }

        return parent::findOrFailPublicWithRelations($id, $relations, $columns);
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

    public function fetchMerchantsByOrgIdFromTidb($orgId, $skip, $limit)
    {
        $connection = $this->getDataWarehouseConnection(ConnectionType::DATA_WAREHOUSE_MERCHANT);

        return $this->newQueryWithConnection($connection)
            ->select(Entity::ID)
            ->where(Entity::ORG_ID, $orgId)
            ->skip($skip)
            ->take($limit)
            ->orderBy(Entity::ID)
            ->get()
            ->pluck(Entity::ID)
            ->toArray();
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

    /**
     * Get a linked account by it's id & parent_id
     *
     * @param string $accountId
     * @param Entity $parent
     *
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function findByAccountIdAndParent(string $accountId, Entity $parent): Entity
    {
        Account\Entity::verifyIdAndStripSign($accountId);

        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->repo->isTransactionActive())
            {
                $query = $this->newQueryWithConnection(
                    $this->getConnectionFromType(Connection::ASV_WRITER)
                );
            }
            else
            {
                /**
                 * @var $account Entity
                 */
                $account = $this->findOrFailPublicAsv($accountId);

                $this->resetConnectionOnModels($account);

                if ($account !== null)
                {
                    if ($account->getParentId() == $parent->getId())
                    {
                        $account->parent()->associate($parent);
                        return $account;
                    }
                }

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_ID,
                    null,
                    [
                        'model' => $this->getEntityClass(),
                        'attributes' => $accountId,
                        'operation' => 'find',
                    ],
                );

            }
        }
        else
        {
            $query = $this->newQuery();
        }

        $query   = $query->where(Entity::PARENT_ID, $parent->getId());
        $account = $query->findOrFailPublic($accountId);

        $this->resetConnectionOnModels($account);

        $account?->parent()->associate($parent);

        return $account;
    }

    /**
     * Used for Marketplace, dashboard:
     * Fetch entities for a CSV report of all linked accounts under a marketplace merchant
     *
     * @param       $merchantId
     * @param       $from
     * @param       $to
     * @param       $count
     * @param       $skip
     * @param array $relations
     *
     * @return mixed
     * @throws BadRequestException
     * @throws BaseException
     * @todo: Move this to Merchant/Account/Repository when account onboarding is merged.
     */
    public function fetchEntitiesForReport($merchantId, $from, $to, $count, $skip, $relations = []): Base\PublicCollection
    {
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->repo->isTransactionActive())
            {
                $query = $this->newQueryWithConnection(
                    $this->getConnectionFromType(Connection::ASV_WRITER)
                );
            }
            else
            {
                $merchants          = new PublicCollection();
                $lastMerchantId     = '';

                while ($count > 0)
                {
                    $limit = min($count, Acs\AsvSdkIntegration\Base::FETCH_SERVICE_FILTER_LIMIT);

                    $count = $count - $limit;

                    $subset = (new AsvSdkMerchantQuery())->fetchLinkedAccountsFromParentIdWithLimitOffset(
                        $merchantId, $lastMerchantId, $limit, $skip
                    );

                    $merchants->push(...$subset);

                    if ($subset->count() < $limit)
                    {
                        break;
                    }

                    $lastMerchantId = $subset->pluck(Entity::ID)->last();
                }

                $this->resetConnectionOnModels($merchants);

                if (count($relations) > 0)
                {
                    $merchants->load(...$relations);
                }

                return $merchants;
            }
        }
        else
        {
            $query = $this->newQuery();
        }

        $merchants = $query
            ->where(Entity::PARENT_ID, $merchantId)
            ->take($count)
            ->skip($skip);

        if (count($relations) > 0)
        {
            $merchants->with(...$relations);
        }

        $results = $merchants->get();

        $this->resetConnectionOnModels($results);

        return $results;
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

    public function serializeForIndexingForAsv(Base\PublicEntity $entity): array
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
        $serialized[Entity::MERCHANT_DETAIL] = $entity->merchantDetail ? $entity->merchantDetail->getEsAttributes() : [];
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



        if($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__)) {
            $merchant = $this->find($merchantId);
        } else {
            $query = $useWritePdo === true ?  $this->newQueryWithConnection($mode)->useWritePdo() : $this->newQuery();
            $merchant = $query->find($merchantId);
        }

        $query = $merchant->users()
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
        if( in_array($params[Detail\Entity::ACTIVATION_STATUS], Constants::ACTIVATION_STATUS_FILTERS) )
        {
            $query->whereIn(Detail\Entity::ACTIVATION_STATUS, Constants::ACTIVATION_STATUS_FILTER_MAPPING[$params[Detail\Entity::ACTIVATION_STATUS]]);
        }
        else
        {
            $query->where(Detail\Entity::ACTIVATION_STATUS, $params[Detail\Entity::ACTIVATION_STATUS]);
        }

        return $query;
    }

    public function FetchRecordFromAllDBs(string $id)
    {
        try
        {
            $testEntity = $this->newQueryWithConnection(Mode::TEST)->find($id);
            $liveEntity = $this->newQueryWithConnection(Mode::LIVE)->find($id);
            $asvEntity = $this->newQueryWithConnection(Connection::ASV_WRITER)->find($id);
            return array($testEntity, $liveEntity, $asvEntity);
        }
        catch(\Throwable $ex)
        {
            $this->trace->error(TraceCode::AMC_LINKED_ACCOUNT_CREATION_DEBUG_ERROR_LOGS, [
                'wda_migration_error' => $ex->getMessage(),
            ]);
        }
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

        $query = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT))
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

        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            try
            {
                $accessMaps = $accessMapRepo->fetchAllMappingsByEntityIdAndEntityOwnerId([$appId], $partnerId, $mode);
                $merchantIds = $accessMaps->pluck(Base\PublicEntity::MERCHANT_ID)->toArray();

                if ($this->isTransactionActive())
                {
                    return $this->repo->merchant->findMerchantsByIds($merchantIds);
                }

                $results = new PublicCollection();

                foreach (array_chunk($merchantIds, Acs\AsvSdkIntegration\Base::FETCH_SERVICE_FILTER_LIMIT) as $chunk) {
                    $results->push(...(new Acs\AsvSdkIntegration\Merchant())->fetchMerchantsByIds($chunk));
                }

                $this->resetConnectionOnModels($results);

                return $results;
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e, Trace::CRITICAL, TraceCode::ACCOUNT_SERVICE_FILTER_QUERY_EXCEPTION, [
                    "identifier" => __FUNCTION__
                ]);
            }
        }

        $accessMapMerchantId = $accessMapRepo->dbColumn(Base\PublicEntity::MERCHANT_ID);
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

    /**
     * @param $merchantId
     *
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\BaseException
     */
    public function fetchLinkedAccountMids($merchantId): array
    {
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->repo->isTransactionActive())
            {
                $query = $this->newQueryWithConnection(
                    $this->getConnectionFromType(Connection::ASV_WRITER)
                );
            }
            else
            {
                $results        = new PublicCollection();
                $lastMerchantId = '';

                do
                {
                    $subset = (new AsvSdkMerchantQuery())->fetchLinkedAccountsFromParentId($merchantId, $lastMerchantId);

                    $lastMerchantId = $subset->pluck(Entity::ID)->last();

                    $results->push(...$subset);
                } while(sizeof($subset) == Acs\AsvSdkIntegration\Base::FETCH_SERVICE_FILTER_LIMIT);

                return $results->pluck(Entity::ID)->toArray();
            }
        }
        else
        {
            $query = $this->newQuery();
        }

        return $query
            ->select(Entity::ID)
            ->where(Entity::PARENT_ID, $merchantId)
            ->get()
            ->pluck(Entity::ID)
            ->toArray();
    }

    public function fetchUnsuspendedLinkedAccountMids($merchantId, $offset = 0)
    {
        $limit = 1000;

        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if (!$this->repo->isTransactionActive())
            {
                $results = (new Acs\AsvSdkIntegration\Merchant())->fetchUnsuspendedLinkedAccountMids(
                    $merchantId, $limit, $offset,
                );

                return $results->pluck('id')->toArray();
            }
            else
            {
                $query = $this->newQueryWithConnection(
                    $this->getConnectionFromType(Connection::ASV_WRITER),
                );
            }
        }
        else
        {
            $query = $this->newQueryWithConnection($this->getSlaveConnection());
        }

        return $query->select(Entity::ID)
                     ->where(Entity::PARENT_ID, $merchantId)
                     ->whereNull(Entity::SUSPENDED_AT)
                     ->offset($offset)
                     ->limit($limit)
                     ->get()
                     ->pluck(Entity::ID)
                     ->toArray();
    }

    public function fetchLinkedAccountMidsSuspendedDueToParentMerchantSuspension($merchantId, $offset = 0)
    {
        $limit  = 1000;
        $reason = Constants::ACCOUNT_SUSPENDED_DUE_TO_PARENT_MERCHANT_SUSPENSION;

        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if (!$this->repo->isTransactionActive())
            {
                $results = (new Acs\AsvSdkIntegration\Merchant())->fetchLinkedAccountMidsSuspendedDueToParentMerchantSuspension(
                    $merchantId, $reason, $limit, $offset,
                );

                return $results->pluck('id')->toArray();
            }
            else
            {
                $query = $this->newQueryWithConnection(
                    $this->getConnectionFromType(Connection::ASV_WRITER),
                );
            }
        }
        else
        {
            $query = $this->newQueryWithConnection($this->getSlaveConnection());
        }

        return $query->select(Entity::ID)
                     ->where(Entity::PARENT_ID, $merchantId)
                     ->whereNotNull(Entity::SUSPENDED_AT)
                     ->where(Entity::HOLD_FUNDS_REASON, $reason)
                     ->offset($offset)
                     ->limit($limit)
                     ->get()
                     ->pluck(Entity::ID)
                     ->toArray();
    }

    /**
     * @param string $parentMerchantId
     * @param bool   $checkForActivated
     *
     * @return array
     * @throws BadRequestException
     * @throws BaseException
     */
    public function fetchLinkedAccountIdsForParentMerchant(
        string $parentMerchantId, bool $checkForActivated = false
    ): array
    {
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->repo->isTransactionActive())
            {
                $query = $this->newQueryWithConnection(
                    $this->getConnectionFromType(Connection::ASV_WRITER)
                );
            }
            else
            {
                $results        = new PublicCollection();
                $lastMerchantId = '';

                do
                {
                    $subset = (new AsvSdkMerchantQuery())->fetchLinkedAccountIdsFromParentIdWithActivated(
                        $parentMerchantId, $checkForActivated, $lastMerchantId
                    );

                    $lastMerchantId = $subset->pluck(Entity::ID)->last();

                    $results->push(...$subset);

                } while(sizeof($subset) == Acs\AsvSdkIntegration\Base::FETCH_SERVICE_FILTER_LIMIT);

                return $results->pluck(MerchantEntity::ID)->toArray();
            }
        }
        else
        {
            $query = $this->newQueryWithConnection($this->getSlaveConnection());
        }

        $query = $query
            ->select(Entity::ID)
            ->where(Entity::PARENT_ID, $parentMerchantId);

        if ($checkForActivated === true)
        {
            $query->where(Entity::ACTIVATED, 1);
        }

        return $query->pluck(Entity::ID)->toArray();
    }

    /**
     * @param array $parentMerchantIds
     *
     * @return array
     * @throws BadRequestException
     * @throws BaseException
     */
    public function fetchLinkedAccountIdsForParentMerchantIds(array $parentMerchantIds): array
    {
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            $results        = new PublicCollection();
            $lastMerchantId = '';

            do
            {
                $subset = (new Acs\AsvSdkIntegration\Merchant())->fetchLinkedAccountsFromMultipleParentIds($parentMerchantIds, $lastMerchantId);

                $lastMerchantId = $subset->pluck(Entity::ID)->last();

                $results->push(...$subset);

            } while(sizeof($subset) == Acs\AsvSdkIntegration\Base::FETCH_SERVICE_FILTER_LIMIT);

            return $results->pluck('id')->toArray();
        }

        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->select(Entity::ID)
            ->whereIn(Entity::PARENT_ID, $parentMerchantIds)
            ->pluck(Entity::ID)
            ->toArray();
    }

    /**
     * @param $merchantId
     *
     * @return int
     * @throws BadRequestException
     * @throws BaseException
     */
    public function fetchLinkedAccountsCount($merchantId): int
    {
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->isTransactionActive())
            {
                $query = $this->newQueryWithConnection(
                    $this->getConnectionFromType(Connection::ASV_WRITER)
                );
            }
            else
            {
                return (new Acs\AsvSdkIntegration\Merchant())->fetchLinkedAccountsCount($merchantId);
            }
        }
        else
        {
            $query = $this->newQuery();
        }
        return $query->select(Entity::ID)->where('parent_id', $merchantId)->count();
    }

    public function fetchLiveEnabledLinkedAccountMids($merchantId, $offset = 0)
    {
        $limit = 1000;

        $query = $this->newQueryWithConnection($this->getSlaveConnection());

        return $query->select(Entity::ID)
            ->where(Entity::PARENT_ID, $merchantId)
            ->where(Entity::LIVE, true)
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->pluck(Entity::ID)
            ->toArray();
    }

    public function fetchLinkedAccountMidsLiveDisabledToParentMerchantLiveDisabled($merchantId, $offset = 0)
    {
        $limit  = 1000;
        $reason = Constants::LIVE_DISABLED_AS_PARENT_MERCHANT_LIVE_DISABLED;

        $query = $this->newQueryWithConnection($this->getSlaveConnection());

        return $query->select(Entity::ID)
            ->where(Entity::PARENT_ID, $merchantId)
            ->where(Entity::LIVE, false)
            ->where(Entity::LIVE_DISABLE_REASON, $reason)
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->pluck(Entity::ID)
            ->toArray();
    }


    /**
     * @throws \Exception
     */
    public function  fetchMerchantIdsWithSameEmailFromWDA(string $email): array
    {
        $this->trace->info(TraceCode::WDA_SERVICE_REQUEST, [
            'method_name'  => __FUNCTION__,
            'email'        => $email
        ]);

        $startTimeMs = round(microtime(true) * 1000);

        $wdaClient = $this->app['wda-client']->wdaClient;

        $wdaQueryBuilder = new WDAQueryBuilder();

        $wdaQueryBuilder->addQuery($this->getTableName(), Entity::ID);

        $wdaQueryBuilder->resources($this->getTableName());

        $wdaQueryBuilder->filters($this->getTableName(), Entity::EMAIL, [$email], Symbol::EQ);

        $wdaQueryBuilder->namespace($this->getEntityObject()->getConnection()->getDatabaseName());

        $wdaQueryBuilder->cluster(WDAService::ADMIN_CLUSTER);

        $this->trace->info(TraceCode::WDA_SERVICE_QUERY, [
            'wda_query_builder' => $wdaQueryBuilder->build()->serializeToJsonString(),
            'route_name'        => $this->app['api.route']->getCurrentRouteName(),
        ]);

        $response = $wdaClient->fetchMultipleWithExpand($wdaQueryBuilder->build(), $this->newQuery()->getModel(), []);

        $merchantIdsWithSameEmail = $this->convertWdaResponseToArray($response, Entity::ID);

        $endTimeMs = round(microtime(true) * 1000);

        $queryDuration = $endTimeMs - $startTimeMs;

        $this->trace->info(TraceCode::WDA_SERVICE_RESPONSE, [
            'route_name'       => $this->app['api.route']->getCurrentRouteName(),
            'method_name'      => __FUNCTION__,
            'merchants_count'  => count($merchantIdsWithSameEmail),
            'duration_ms'      => $queryDuration,
        ]);

        return $merchantIdsWithSameEmail;
    }

    public function fetchMerchantIdsWithSameEmail(string $email): array
    {
        try
        {
            if($this->isExperimentEnabled(Constants::FETCH_MERCHANT_ID_SAME_EMAIL_FROM_WDA) === true)
            {
                return $this->fetchMerchantIdsWithSameEmailFromWDA($email);
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->error(TraceCode::WDA_MIGRATION_ERROR, [
                'wda_migration_error' => $ex->getMessage(),
                'route_name'          => $this->app['api.route']->getCurrentRouteName(),
            ]);
        }

        $connection = $this->getDataWarehouseConnection(ConnectionType::DATA_WAREHOUSE_MERCHANT);

        return $this->newQueryWithConnection($connection)
                     ->select(Entity::ID)
                     ->where(Entity::EMAIL, $email)
                     ->pluck(Entity::ID)
                     ->toArray();
    }

    public function fetchAllActiveLinkedAccounts(int $createdAt=null)
    {
        $query = $this->newQueryWithConnection(
            $this->getDataWarehouseConnection(ConnectionType::DATA_WAREHOUSE_MERCHANT)
        );

        $query = $query->whereNotNull(Entity::PARENT_ID)
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

    public function fetchMidsCountWithSecondFactorAuth( array $mids)
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(Connection::ASV_WRITER))
                    ->whereIn(Entity::ID, $mids)
                    ->where(Entity::SECOND_FACTOR_AUTH, 1)
                    ->limit(1)
                    ->count();

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

    /**
     * Returns the number of merchants associated with the passed
     * parent_id and account_code
     *
     * @param string $accountCode
     * @param string $parentId
     *
     * @return int
     * @throws BadRequestException
     * @throws BaseException
     */
    public function countAccountCodeForMerchant(string $accountCode, string $parentId): int
    {
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->repo->isTransactionActive())
            {
                $query = $this->newQueryWithConnection(
                    $this->getConnectionFromType(Connection::ASV_WRITER)
                );
            }
            else
            {
                $merchants = (new AsvSdkMerchantQuery())->fetchMerchantsByParentIdAndAccountCode(
                    $parentId, $accountCode
                );

                return count($merchants);
            }
        }
        else
        {
            $query = $this->newQueryWithConnection($this->getSlaveConnection());
        }

        return $query
            ->where(Entity::PARENT_ID, $parentId)
            ->where(Entity::ACCOUNT_CODE, $accountCode)
            ->limit(1)
            ->count(Entity::ACCOUNT_CODE);
    }

    /**
     * @param string $accountCode
     * @param string $parentId
     *
     * @return Collection|string|null
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getIdByAccountCodeAndParent(string $accountCode, string $parentId)
    {
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->repo->isTransactionActive())
            {
                $query = $this->newQueryWithConnection(
                    $this->getConnectionFromType(Connection::ASV_WRITER)
                );
            }
            else
            {
                return (new AsvSdkMerchantQuery())
                    ->fetchMerchantsByParentIdAndAccountCode(
                        $parentId, $accountCode
                    )
                    ->pluck(Entity::ID)
                    ->pop();
            }
        }
        else
        {
            $query = $this->newQueryWithConnection(
                $this->getSlaveConnection()
            );
        }

        return $query
            ->select($this->dbColumn(Entity::ID))
            ->where(Entity::PARENT_ID, $parentId)
            ->where(Entity::ACCOUNT_CODE, $accountCode)
            ->limit(1)
            ->get()
            ->pluck(Entity::ID)
            ->pop();
    }

    /**
     * @param string $id
     *
     * @return string|null
     * @throws BadRequestException
     * @throws BaseException
     * @throws \Exception
     */
    public function getAccountCodeById(string $id): ?string
    {
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->repo->isTransactionActive())
            {
                $query = $this->newQueryWithConnection(
                    $this->getConnectionFromType(Connection::ASV_WRITER)
                );
            }
            else
            {
                return (new AsvSdkMerchantQuery())->getByIdForFindOrFail($id)?->getAccountCode();
            }
        }
        else
        {
            $query = $this->newQueryWithConnection(
                $this->getSlaveConnection()
            );
        }

        return $query
            ->select($this->dbColumn(Entity::ACCOUNT_CODE))
            ->where(Entity::ID, $id)
            ->limit(1)
            ->get()
            ->pluck(Entity::ACCOUNT_CODE)
            ->pop();
    }

    public function fetchPartnerIdsInBatches($merchantIds = null, $limit = null, $afterId = null)
    {
        //NOTE: This change is being done for ASV Decomposition (#platform_account_service)
        //Changing the connection to TiDB as a fallback as no usage was found for this in the past 90 days.
        $query = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT))
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
        try
        {
            return $this->fetchAggregatorPartnersFromDataLake($limit, $afterId);
        }
        catch (Throwable $e)
        {
            $this->trace->traceException($e);
        }

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

    private function fetchAggregatorPartnersFromDataLake($limit = null, $afterId = null): PublicCollection
    {
        $dataLakeQuery = self::AGGREGATOR_PARTNERS_DL_QUERY;

        if (empty($afterId) === false)
        {
            $dataLakeQuery .= sprintf(" AND id > '%s'", $afterId);
        }

        $dataLakeQuery .= " ORDER BY id ASC";

        if (empty($limit) === false)
        {
            $dataLakeQuery .= sprintf(" LIMIT %d", $limit);
        }

        $results = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);

        return (new PublicCollection(
            array_map(
                function ($row) {return (new Entity())->fill($row);}, $results
            )
        ));
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

    public function getActivatedSubMInPastDays(string $partnerMerchantId, int $pastDays, int $limit, string $variant = null): array
    {
        $pastDaysTimestamp = Carbon::now()->subDays($pastDays)->getTimestamp();
        $activatedStatuses = [
            Detail\Status::ACTIVATED, Detail\Status::ACTIVATED_KYC_PENDING, Detail\Status::ACTIVATED_MCC_PENDING,
        ];

        if ($variant != null)
        {
            try
            {
                $dataLakeQuery      = $this->getActivatedSubMInPastDaysFromDataLakeQuery(
                    $partnerMerchantId, $pastDaysTimestamp, $activatedStatuses, $limit,
                );
                $results            = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);
                $dataLakeResults    = collect($results)->pluck(Entity::ID)->toArray();

                switch ($variant)
                {
                    case 'enable':
                        return $dataLakeResults;

                    case 'shadow':

                        $dbQuery    = $this->getActivatedSubMInPastDaysFromDBQuery(
                            $partnerMerchantId, $pastDaysTimestamp, $activatedStatuses, $limit,
                        );
                        $dbResults  = $dbQuery->get()->pluck(Entity::ID)->toArray();

                        $this->trace->info(
                            TraceCode::DATALAKE_DB_RESULT_COMPARISON,
                            [
                                "datalake_query"            => $dataLakeQuery,
                                "datalake_results"          => $dataLakeResults,
                                "db_query"                  => $dbQuery->toSql(),
                                "db_bindings"               => $dbQuery->getBindings(),
                                "db_results"                => $dbResults,
                                "db_datalake_result_match"  => array_sort($dbResults) === array_sort($dataLakeResults)
                            ]
                        );

                        return $dbResults;

                    default:
                        break;
                }
            }
            catch (Throwable $e)
            {
                $this->trace->traceException($e);
            }
        }

        $dbQuery = $this->getActivatedSubMInPastDaysFromDBQuery(
            $partnerMerchantId, $pastDaysTimestamp, $activatedStatuses, $limit,
        );

        return $dbQuery->get()->pluck(Entity::ID)->toArray();
    }

    /**
     * @param string $partnerMerchantId
     * @param int    $pastDaysTimestamp
     * @param array  $activatedStatuses
     * @param int    $limit
     *
     * @return mixed
     */
    private function getActivatedSubMInPastDaysFromDBQuery(
        string $partnerMerchantId, int $pastDaysTimestamp, array $activatedStatuses, int $limit,
    ): mixed
    {
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
                    ->whereIn($activationStatus, $activatedStatuses)
                    ->take($limit);
    }

    public function getMerchantsWithActivatedButNotLive()
    {
        $merchantIdColumn = $this->repo->merchant->dbColumn(MerchantEntity::ID);
        $createdAtColumn = $this->repo->merchant->dbColumn(Detail\Entity::CREATED_AT);
        $merchantDetailsActivationStatusColumn = $this->repo->merchant_detail->dbColumn(Detail\Entity::ACTIVATION_STATUS);
        $merchantDetailsId = $this->repo->merchant_detail->dbColumn(Detail\Entity::MERCHANT_ID);
        $merchantActivatedColumn = $this->repo->merchant->dbColumn(MerchantEntity::ACTIVATED);
        $merchantLiveColumn = $this->repo->merchant->dbColumn(MerchantEntity::LIVE);
        $merchantActivatedAtColumn = $this->repo->merchant->dbColumn(MerchantEntity::ACTIVATED_AT);

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT))
                    ->select(
                        $merchantIdColumn
                    )
                    ->join(Table::MERCHANT_DETAIL, $merchantIdColumn, '=', $merchantDetailsId)
                    ->where($merchantDetailsActivationStatusColumn, '=', MerchantEntity::ACTIVATED)
                    ->where($merchantActivatedColumn, '=', 0)
                    ->where($merchantLiveColumn, '=', 0)
                    ->whereNull($merchantActivatedAtColumn)
                    ->orderBy($createdAtColumn, 'desc')
                    ->get();
    }

    /**
     * @param string $partnerMerchantId
     * @param int    $pastDaysTimestamp
     * @param array  $activatedStatuses
     * @param int    $limit
     *
     * @return string
     */
    private function getActivatedSubMInPastDaysFromDataLakeQuery(
        string $partnerMerchantId,
        int $pastDaysTimestamp,
        array $activatedStatuses,
        int $limit,
    ): string
    {
        return sprintf(
            self::ACTIVATED_SUBM_LAST_N_DAYS_DL_QUERY,
            $partnerMerchantId,
            $pastDaysTimestamp,
            implode("', '", $activatedStatuses),
            $limit
        );
    }

    /**
     * @param string      $partnerMerchantId
     * @param int         $pastDays
     * @param int         $limit
     * @param string|null $variant
     *
     * @return array
     */
    public function getRejectedSubMInPastDays(string $partnerMerchantId, int $pastDays, int $limit, string|null $variant): array
    {
        $pastDaysTimestamp = Carbon::now()->subDays($pastDays)->getTimestamp();

        if ($variant != null)
        {
            try
            {
                $dataLakeQuery      = $this->getRejectedSubMInPastDaysFromDataLakeQuery(
                    $partnerMerchantId, $pastDaysTimestamp, $limit,
                );
                $results            = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);
                $dataLakeResults    = collect($results)->pluck(ActionState::ENTITY_ID)->toArray();

                switch ($variant)
                {
                    case 'enable':
                        return $dataLakeResults;

                    case 'shadow':
                        $dbQuery    = $this->getRejectedSubMInPastDaysFromDBQuery(
                            $partnerMerchantId, $pastDaysTimestamp, $limit,
                        );
                        $dbResults  = $dbQuery->get()->pluck(ActionState::ENTITY_ID)->toArray();

                        $this->trace->info(
                            TraceCode::DATALAKE_DB_RESULT_COMPARISON,
                            [
                                "datalake_query"            => $dataLakeQuery,
                                "datalake_results"          => $dataLakeResults,
                                "db_query"                  => $dbQuery->toSql(),
                                "db_bindings"               => $dbQuery->getBindings(),
                                "db_results"                => $dbResults,
                                "db_datalake_result_match"  => array_sort($dbResults) === array_sort($dataLakeResults)
                            ]
                        );

                        return $dbResults;

                    default:
                        break;
                }
            }
            catch (Throwable $e)
            {
                $this->trace->traceException($e);
            }
        }

        $dbQuery = $this->getRejectedSubMInPastDaysFromDBQuery(
            $partnerMerchantId, $pastDaysTimestamp, $limit,
        );

        return $dbQuery->get()->pluck(ActionState::ENTITY_ID)->toArray();
    }

    private function getRejectedSubMInPastDaysFromDBQuery(
        string $partnerMerchantId, int $pastDaysTimestamp, int $limit,
    ): mixed
    {
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
                               ->take($limit);
    }

    /**
     * @param string $partnerMerchantId
     * @param int    $pastDaysTimestamp
     * @param int    $limit
     *
     * @return string
     */
    private function getRejectedSubMInPastDaysFromDataLakeQuery(
        string $partnerMerchantId, int $pastDaysTimestamp, int $limit,
    ): string
    {
        return sprintf(
            self::REJECTED_SUBM_LAST_N_DAYS_DL_QUERY,
            $partnerMerchantId,
            Detail\Status::REJECTED,
            $pastDaysTimestamp,
            $limit
        );
    }

    public function getSubmerchantIdsInTerminalStateInPastDays(
        string $partnerMerchantId, int $pastDays, int $limit, string $variant = null,
    )
    {
        $activatedIds = $this->getActivatedSubMInPastDays($partnerMerchantId, $pastDays, $limit, $variant);

        $rejectedIds = $this->getRejectedSubMInPastDays($partnerMerchantId, $pastDays, $limit, $variant);

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

    /**
     * @param int $updatedAtFrom
     * @param int $updateAtTo
     *
     * @return Collection|PublicCollection
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getMerchantsForSettlementsEventsCron(
        int $updatedAtFrom, int $updateAtTo
    ): Collection|PublicCollection
    {
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->isTransactionActive())
            {
                $query = $this->newQueryWithConnection(
                    $this->getConnectionFromType(Connection::ASV_WRITER)
                );
            }
            else
            {
                $results        = new PublicCollection();
                $lastMerchantId = '';

                do
                {
                    $subset = (new AsvSdkMerchantQuery())->getMerchantsForSettlementsEventsCron(
                        $updatedAtFrom, $updateAtTo, $lastMerchantId
                    );

                    $lastMerchantId = $subset->pluck(Entity::ID)->last();

                    $results->push(...$subset);

                } while(sizeof($subset) == Acs\AsvSdkIntegration\Base::FETCH_SERVICE_FILTER_LIMIT);

                $this->resetConnectionOnModels($results, $this->getReportingReplicaConnection());

                return $results;
            }
        }
        else
        {
            $query = $this->newQueryWithConnection($this->getReportingReplicaConnection());
        }

        $query = $query->where(Entity::UPDATED_AT, '>=', $updatedAtFrom)
                      ->where(Entity::UPDATED_AT, '<=', $updateAtTo)
                      ->orderBy(Entity::UPDATED_AT, 'asc');

        $results = $query->get();

        $this->resetConnectionOnModels($results, $this->getReportingReplicaConnection());

        return $results;
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
     * __saveOrFail -  Keeping the method name not same with base repository method, this to be renamed  and used in
     * merchant core while ramp-up
     *Once stakeholder saveOrFail is migrated to Account service only this method should be used while saving the merchant entity any save on merchant entity has to be called at any new place
     * @param MerchantEntity $entity
     * @param bool $testAndLive - If true saveEntity on both test and live db else only live db
     * @throws \Throwable
     */
    public function __saveOrFail(MerchantEntity $entity, bool $testAndLive)
    {
        $this->repo->transactionOnLiveAndTestAndAsv(function () use ($testAndLive, $entity) {
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

        $connectionArray = [Mode::LIVE, Mode::TEST, Connection::ASV_WRITER];

        foreach ($connectionArray as $mode)
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

    public function updateLinkedAccountsAsLiveEnabledOrLiveDisabledInBulk(array $linkedAccountMids, bool $shouldDisable)
    {
        $countOfLinkedAccounts = count($linkedAccountMids);

        if ($countOfLinkedAccounts === 0)
        {
            return 0;
        }

        if ($shouldDisable === true)
        {
            $updateColumnValues = [
                Entity::LIVE                => false,
                Entity::LIVE_DISABLE_REASON => Constants::LIVE_DISABLED_AS_PARENT_MERCHANT_LIVE_DISABLED
            ];
        }
        else
        {
            $updateColumnValues = [
                Entity::LIVE                 => true,
                Entity::LIVE_DISABLE_REASON  => null,
            ];
        }

        $connectionArray = [Mode::LIVE, Mode::TEST, Connection::ASV_WRITER];

        foreach ($connectionArray as $mode)
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
                        'live_disable'  => $shouldDisable,
                        'expected'      => $countOfLinkedAccounts,
                        'updated'       => $updatedCount,
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

    /**
     * @param array $ids
     *
     * @return EloquentCollection|PublicCollection
     */
    public function findMerchantsByIds(array $ids): EloquentCollection|PublicCollection
    {
        if (sizeof($ids) > 0)
        {
            if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
            {
                if ($this->repo->isTransactionActive())
                {
                    $results = $this->newQueryWithConnection(
                        $this->getConnectionFromType(Connection::ASV_WRITER)
                    )->findMany($ids, array('*'));

                    $this->resetConnectionOnModels($results);

                    return $results;
                }
                else
                {
                    try {
                        $this->trace->info(TraceCode::ACCOUNT_SERVICE_FILTER_REQUEST, [
                            "identifier" => __FUNCTION__
                        ]);

                        $results = new Base\PublicCollection();

                        foreach (array_chunk($ids, Acs\AsvSdkIntegration\Base::FETCH_SERVICE_FILTER_LIMIT) as $chunk) {
                            $results->push(...(new Acs\AsvSdkIntegration\Merchant())->fetchMerchantsByIds($chunk));
                        }

                        $this->resetConnectionOnModels($results);

                        return $results;
                    }
                    catch (\Exception $e) {
                        $this->trace->traceException($e, Trace::CRITICAL, TraceCode::ACCOUNT_SERVICE_FILTER_QUERY_EXCEPTION, [
                            "identifier" => __FUNCTION__
                        ]);
                    }
                    return $this->findMany($ids);
                }
            }
            else
            {
                return $this->findMany($ids);
            }
        }
        return new PublicCollection();
    }

    public function getNonSuspendedMerchantsFromIds(array $ids): EloquentCollection|PublicCollection
    {
        if (sizeof($ids) > 0 )
        {
            if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
            {
                if ($this->repo->isTransactionActive())
                {
                    $results = $this->newQueryWithConnection(
                        $this->getConnectionFromType(Connection::ASV_WRITER)
                    )->findMany($ids)
                     ->where(Entity::SUSPENDED_AT, null);

                    $this->resetConnectionOnModels($results);

                    return $results;
                }
                else
                {
                    try {
                        $this->trace->info(TraceCode::ACCOUNT_SERVICE_FILTER_REQUEST, [
                            "identifier" => __FUNCTION__
                        ]);

                        $results = (new Acs\AsvSdkIntegration\Merchant())->getNonSuspendedMerchantsFromIds($ids);

                        $this->resetConnectionOnModels($results);

                        return $results;
                    }
                    catch (\Exception $e) {
                        $this->trace->traceException($e, Trace::CRITICAL, TraceCode::ACCOUNT_SERVICE_FILTER_QUERY_EXCEPTION, [
                            "identifier" => __FUNCTION__
                        ]);
                    }
                    return $this->newQuery()->findMany($ids)->where(Entity::SUSPENDED_AT, null);
                }
            }
            else
            {
                return $this->newQuery()->findMany($ids)->where(Entity::SUSPENDED_AT, null);
            }

        }
        return (new PublicCollection());
    }
}

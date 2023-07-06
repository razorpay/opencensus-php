<?php

namespace RZP\Models\Merchant\Detail;

use Carbon\Carbon;
use Database\Connection;
use DB;

use Exception;
use RZP\Base\ConnectionType;
use RZP\Models\Base;
use Rzp\Wda_php\Symbol;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Models\Merchant\AccessMap;
use RZP\Constants\Table;
use RZP\Models\Admin\Org;
use Rzp\Wda_php\Operator;
use RZP\Services\WDAService;
use RZP\Models\Merchant\Detail;
use Rzp\Wda_php\WDAQueryBuilder;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Models\Merchant\Stakeholder;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Modules\Acs\Wrapper\MerchantDetail as MerchantDetailWrapper;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Acs\traits\AsvFind;

class Repository extends Base\Repository
{
    use AsvFind;
    use Base\RepositoryUpdateTestAndLive
    {
        saveOrFail as saveOrFailTestAndLive;
        validateEntitiesMatch as parentValidateEntitiesMatch;
    }

    protected $entity = 'merchant_detail';

    function __construct()
    {
        parent::__construct();

        $this->asvRouter = new AsvRouter();
    }

    public function fetchAllMerchantIDsFromSlaveDB($input)
    {
        $query = $this->newQueryWithConnection($this->getAccountServiceReplicaConnection())
            ->select([Entity::MERCHANT_ID])
            ->distinct()
            ->orderBy(Entity::MERCHANT_ID);

        if (isset($input['after_merchant_id']) === true) {
            $query->where(Entity::MERCHANT_ID, '>', $input['after_merchant_id']);
        }

        if (isset($input['count']) === true) {
            $query->take($input['count']);
        }

        return $query->get();
    }

    /**
     * @override
     *
     * Once merchant details is saved, we need to trigger es sync for corresponding
     * merchant. This handling is required as merchant detail relation is part
     * of merchant index content.
     *
     * We doesn't do cascade save of related entities when the main entity is saved.
     * Since merchantDetails entity is passed around multiple functions, we trigger the stakeholder save
     * whenever the merchantDetails get saved.
     *
     * @param Entity $merchantDetail
     * @param array  $options
     */
    public function saveOrFail($merchantDetail, array $options = [])
    {
        $this->saveOrFailTestAndLive($merchantDetail, $options);

        $stakeholder = $merchantDetail->relationLoaded(Entity::STAKEHOLDER) ? $merchantDetail->getRelation(Entity::STAKEHOLDER) : null;

        if ((empty($stakeholder) === false) and ($stakeholder->exists === true) and ($stakeholder->isDirty() === true))
        {
            $this->repo->stakeholder->saveOrFail($stakeholder);
        }

        $merchant = $merchantDetail->merchant;

        $this->repo->merchant->syncToEsLiveAndTest($merchant, Merchant\EsRepository::UPDATE);
    }

    /**
     * __saveOrFail - Saves MerchantDetail Entity in API DB and ASV
     * @param Entity $merchantDetail
     * $param boll $testAndLive
     * @param bool $testAndLive - If true saveEntity on both test and live db else only live db
     * @throws \Throwable
     */
    public function __saveOrFail(Entity $merchantDetail, $testAndLive, array $options = []) {
        $this->repo->transactionOnLiveAndTest(function () use ($merchantDetail, $testAndLive, $options) {
            if ($testAndLive === true) {
                $this->saveOrFail($merchantDetail, $options);
            } else {
                $this->repo->saveOrFail($merchantDetail, $options);
            }
            (new MerchantDetailWrapper())->SaveOrFail($merchantDetail);
        });
    }

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::MERCHANT_ID, 'desc');
    }

    public function getByMerchantId($merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->first();
    }

    public function __findOrFailPublic($id)
    {
        $merchantDetailFromApi = $this->findOrFailPublic($id);
        $id = Entity::stripDefaultSign($id);
        return (new MerchantDetailWrapper())->getByMerchantId($id, $merchantDetailFromApi);
    }

    public function __getByMerchantId($merchantId)
    {
        $merchantDetailsFromApi = $this->getByMerchantId($merchantId);
        if($merchantDetailsFromApi === null) {
            return $merchantDetailsFromApi;
        }
        return (new MerchantDetailWrapper())->getByMerchantId($merchantId, $merchantDetailsFromApi);
    }

    public function __findOrFail($id) {
        $merchantDetailsFromApi = $this->findOrFail($id);
        return (new MerchantDetailWrapper())->getByMerchantId($id, $merchantDetailsFromApi);
    }

    protected function validateEntitiesMatch($liveEntity, $testEntity)
    {
        $liveEntityClone = clone $liveEntity;
        $testEntityClone = clone $testEntity;

        $liveEntityClone->setFundAdditionVAIds(NULL);
        $testEntityClone->setFundAdditionVAIds(NULL);

        $this->parentValidateEntitiesMatch($liveEntityClone, $testEntityClone);
    }

    public function fetchForMerchant(Merchant\Entity $merchant)
    {
        if ($merchant->hasRelation('merchantDetail'))
        {
            return $merchant->merchantDetail;
        }

        $merchantDetail = $this->getByMerchantId($merchant->getId());

        $merchant->setRelation('merchantDetail', $merchantDetail);

        return $merchantDetail;
    }

    public function findMerchantsWithoutStakeholders($limit, $afterId = null)
    {
        $detailsMerchantIdCol     = $this->dbColumn(Entity::MERCHANT_ID);
        $stakeholderMerchantIdCol = $this->repo->stakeholder->dbColumn(Stakeholder\Entity::MERCHANT_ID);
        $stakeholderDeletedAtCol  = $this->repo->stakeholder->dbColumn(Stakeholder\Entity::DELETED_AT);

        $query = $this->newQuery()->select($detailsMerchantIdCol)
                      ->leftJoin(Table::STAKEHOLDER, $detailsMerchantIdCol, '=', $stakeholderMerchantIdCol)
                      ->whereNull($stakeholderMerchantIdCol)
                      ->whereNull($stakeholderDeletedAtCol);

        if (empty($limit) === false)
        {
            $query->limit($limit);
        }

        if (empty($afterId) === false)
        {
            $query->where($detailsMerchantIdCol, '>', $afterId);
        }

        return $query->orderBy($detailsMerchantIdCol, 'asc')->get()->pluck(Entity::MERCHANT_ID)->toArray();
    }

    public function getMerchantDetailsToBeMigrated($count = 1000, $skip = 0)
    {
        return $this->newQuery()
                    ->select(
                        Entity::MERCHANT_ID,
                        Entity::BUSINESS_PROOF_URL,
                        Entity::BUSINESS_OPERATION_PROOF_URL,
                        Entity::BUSINESS_PAN_URL,
                        Entity::ADDRESS_PROOF_URL,
                        Entity::PROMOTER_PROOF_URL,
                        Entity::PROMOTER_PAN_URL,
                        Entity::PROMOTER_ADDRESS_URL)
                    ->whereRaw(
                        'length(business_proof_url) > 19 or
                        length(business_operation_proof_url) > 19 or
                        length(business_pan_url) > 19 or
                        length(address_proof_url) > 19 or
                        length(promoter_proof_url) > 19 or
                        length(promoter_pan_url) > 19 or
                        length(promoter_address_url) > 19')
                    ->orderBy(Entity::MERCHANT_ID)
                    ->skip($skip)
                    ->take($count)
                    ->get();
    }

    /**
     * @param string $status
     *
     * @return array
     * @deprecated by getFeatureOnboardingRequests()
     *
     */
    public function getFeatureOnboardingRequestsByStatus(string $status): array
    {
        return $this->newQueryWithConnection(Mode::LIVE)
                    ->select(
                        Entity::MERCHANT_ID,
                        Entity::CONTACT_NAME,
                        Entity::MARKETPLACE_ACTIVATION_STATUS,
                        Entity::VIRTUAL_ACCOUNTS_ACTIVATION_STATUS,
                        Entity::SUBSCRIPTIONS_ACTIVATION_STATUS)
                    ->where(Entity::MARKETPLACE_ACTIVATION_STATUS, $status)
                    ->orWhere(Entity::VIRTUAL_ACCOUNTS_ACTIVATION_STATUS, $status)
                    ->orWhere(Entity::SUBSCRIPTIONS_ACTIVATION_STATUS, $status)
                    ->get()
                    ->toArray();
    }

    /**
     * Returns the list of feature onboarding requests based on the params passed
     *
     * @param array $params
     *
     * @return array
     */
    public function getFeatureOnboardingRequests(array $params): array
    {
        //
        // [Sample]
        //
        // Union query that will run when these params are passed -
        // status=rejected, count=2, skip=0
        //
        //    (SELECT `merchant_id`,
        //            'marketplace'                 AS product,
        //            marketplace_activation_status AS 'status'
        //     FROM   `merchant_details`
        //     WHERE `marketplace_activation_status` = 'rejected')
        //    UNION
        //    (SELECT `merchant_id`,
        //            'subscriptions'                 AS product,
        //            subscriptions_activation_status AS 'status'
        //     FROM   `merchant_details`
        //     WHERE `subscriptions_activation_status` = 'rejected')
        //    UNION
        //    (SELECT `merchant_id`,
        //            'virtual_accounts'                 AS product,
        //            virtual_accounts_activation_status AS 'status'
        //     FROM   `merchant_details`
        //     WHERE `virtual_accounts_activation_status` = 'rejected')
        //    LIMIT 2
        //    OFFSET 0
        //

        //
        // Unset input keys that are not required ahead and are only used below
        // in the query building. They interfere with buildQueryWithParams() call.
        //
        $statusFilter  = array_pull($params, FeatureConstants::STATUS);
        $productFilter = array_pull($params, FeatureConstants::PRODUCT);

        $products = FeatureConstants::PRODUCT_FEATURES;

        $unionQueryElements = [];

        foreach ($products as $product)
        {
            //
            // Add productFeature results,
            // - If the product filter is not present, or,
            // - If the product filter is set to productFeature
            //
            if (($productFilter === null) or ($productFilter === $product))
            {
                // For eg: virtual_accounts_activation_status
                $productActivationStatus = $product . '_activation_status';

                //
                // Dynamically generate the queries that have to be run for each product
                // All such queries will then be UNIONed to run just one single query.
                //
                $unionQueryElement = $this->newQueryWithConnection(Mode::LIVE)
                                          ->select(
                                              Entity::MERCHANT_ID,
                                              DB::raw("'" . $product . "' as product"),
                                              DB::raw($productActivationStatus . " as 'status'"));

                // Filter with status
                if ($statusFilter === null)
                {
                    //
                    // If the merchant hasn't submitted on-boarding responses
                    // *_activation_status attribute is null.
                    //
                    $unionQueryElement->whereNotNull($productActivationStatus);
                }
                else
                {
                    $unionQueryElement->where($productActivationStatus, $statusFilter);
                }

                $unionQueryElements[] = $unionQueryElement;
            }
        }

        $records = [];

        if (count($unionQueryElements) > 0)
        {
            $query = null;

            // Generate a union query
            foreach ($unionQueryElements as $unionQueryElement)
            {
                $query = $query ? $query->union($unionQueryElement) : $unionQueryElement;
            }

            // Handles query params like skip, count, from and to
            $this->buildQueryWithParams($query, $params);

            $records = $query->get()->toArray();
        }

        return $records;
    }

    public function updateFeatureActivationStatus(
        Merchant\Entity $merchant,
        string $featureName,
        string $status)
    {
        $merchantDetail = $merchant->merchantDetail;

        $setFeatureActivationStatus = camel_case('set_' . $featureName . '_activation_status');

        $merchantDetail->$setFeatureActivationStatus($status);

        $this->saveOrFailTestAndLive($merchantDetail);
    }

    public function getFeatureActivationStatus(
        Merchant\Entity $merchant,
        string $featureName)
    {
        $merchantDetail = $merchant->merchantDetail;

        $getFeatureActivationStatus = camel_case('get_' . $featureName . '_activation_status');

        $status = $merchantDetail->$getFeatureActivationStatus();

        return $status;
    }


    /**
     * @param string $status
     * @param int    $pennyTestingUpdatedAt
     *
     * @return mixed
     */
    public function fetchMerchantDetailsForPennyTestingRetry(string $status, int $pennyTestingUpdatedAt)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->where(Entity::BANK_DETAILS_VERIFICATION_STATUS, '=', $status)
                    ->where(Entity::PENNY_TESTING_UPDATED_AT, '<', $pennyTestingUpdatedAt)
                    ->get();
    }

    public function fetchMerchantIdsByActivationStatus(array $activationStatusList, array $orgIdList = [Org\Entity::RAZORPAY_ORG_ID], int $createdAt = null): array
    {
        $detailMerchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $merchantIdColumn       = $this->repo->merchant->dbColumn(Merchant\Entity::ID);
        $merchantOrgIdColumn    = $this->repo->merchant->dbColumn(Merchant\Entity::ORG_ID);
        $merchantParentIdColumn = $this->repo->merchant->dbColumn(Merchant\Entity::PARENT_ID);

        $query = $this->newQuery()
                      ->join(Table::MERCHANT, $merchantIdColumn, '=', $detailMerchantIdColumn)
                      ->whereIn($merchantOrgIdColumn, $orgIdList)
                      ->where($merchantParentIdColumn, '=', null)
                      ->select(Entity::MERCHANT_ID)
                      ->whereIn(Entity::ACTIVATION_STATUS, $activationStatusList);

        if (empty($createdAt) === false)
        {
            $query->where($this->dbColumn(Entity::CREATED_AT), '>=', $createdAt);
        }

        return $query->get()
                     ->pluck(Entity::MERCHANT_ID)
                     ->toArray();
    }

    public function filterMerchantIdsByActivationStatus(array $mids, array $activationStatusList): array
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
                    ->select(Entity::MERCHANT_ID)
                    ->whereIn(Entity::MERCHANT_ID, $mids)
                    ->whereIn(Entity::ACTIVATION_STATUS, $activationStatusList)
                    ->get()
                    ->pluck(Entity::MERCHANT_ID)
                    ->toArray();
    }

    public function filterMerchantIdsByOrg(array $mids, array $orgIdList): array
    {
        $detailMerchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $merchantIdColumn       = $this->repo->merchant->dbColumn(Merchant\Entity::ID);
        $merchantOrgIdColumn    = $this->repo->merchant->dbColumn(Merchant\Entity::ORG_ID);

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->join(Table::MERCHANT, $merchantIdColumn, '=', $detailMerchantIdColumn)
            ->select(Entity::MERCHANT_ID)
            ->whereIn(Entity::MERCHANT_ID, $mids)
            ->whereIn($merchantOrgIdColumn, $orgIdList)
            ->get()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }

    public function filterL1NotSubmittedMerchantIds(int $from, int $to): array
    {
        $experimentResult = (new Detail\Core)->getSplitzResponse(UniqueIdEntity::generateUniqueId(),
                                                                 Merchant\Constants::WDA_MIGRATION_ACQUISITION_SPLITZ_EXP_ID);

        $isWDAExperimentEnabled = ($experimentResult === 'live') ? true : false;

        try
        {
            if (($this->app['api.route']->isWDAServiceRoute() === true) and ($isWDAExperimentEnabled === true))
            {
                return $this->filterL1NotSubmittedMerchantIdsFromWda($from, $to);
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->error(TraceCode::WDA_MIGRATION_ERROR, [
                'wda_migration_error' => $ex->getMessage(),
                'route_name'          => $this->app['api.route']->getCurrentRouteName(),
            ]);
        }

        $detailMerchantIdColumn          = $this->dbColumn(Entity::MERCHANT_ID);
        $merchantCreatedAtColumn         = $this->repo->merchant->dbColumn(Merchant\Entity::CREATED_AT);
        $merchantIdColumn                = $this->repo->merchant->dbColumn(Merchant\Entity::ID);
        $merchantOrgIdColumn             = $this->repo->merchant->dbColumn(Merchant\Entity::ORG_ID);
        $merchantBusinessBankingIdColumn = $this->repo->merchant->dbColumn(Merchant\Entity::BUSINESS_BANKING);

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
                    ->join(Table::MERCHANT, $merchantIdColumn, '=', $detailMerchantIdColumn)
                    ->select(Entity::MERCHANT_ID)
                    ->whereBetween($merchantCreatedAtColumn, [$from, $to])
                    ->WhereNull(Entity::ACTIVATION_FORM_MILESTONE)
                    ->where($merchantOrgIdColumn, '=', Org\Entity::RAZORPAY_ORG_ID)
                    ->where($merchantBusinessBankingIdColumn, '=', false)
                    ->get()
                    ->pluck(Entity::MERCHANT_ID)
                    ->toArray();
    }

    /**
     * Filter L1 not submitted merchantIdList based on the provided time interval.
     *
     * @param int $from
     * @param int $to
     *
     * @return array
     *
     * @throws Exception
     */
    public function filterL1NotSubmittedMerchantIdsFromWda(int $from, int $to): array
    {
        $detailMerchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $merchantIdColumn       = $this->repo->merchant->dbColumn(Merchant\Entity::ID);

        $this->trace->info(TraceCode::WDA_SERVICE_REQUEST, [
            'method_name' => __FUNCTION__,
            'from'        => $from,
            'to'          => $to,
        ]);

        $startTimeMs = round(microtime(true) * 1000);

        $wdaClient = $this->app['wda-client']->wdaClient;

        $wdaQueryBuilder = new WDAQueryBuilder();

        $wdaQueryBuilder->addQuery($this->getTableName(), Entity::MERCHANT_ID);

        $wdaQueryBuilder->resources($this->getTableName());

        $wdaQueryBuilder->addResource(Table::MERCHANT, 'inner', "$merchantIdColumn = $detailMerchantIdColumn");

        $wdaQueryBuilder->filters(Table::MERCHANT, Merchant\Entity::CREATED_AT, [$from, $to], Symbol::BETWEEN)
                        ->filters($this->getTableName(), Entity::ACTIVATION_FORM_MILESTONE, [], Symbol::NULL)
                        ->filters(Table::MERCHANT, Merchant\Entity::ORG_ID, [Org\Entity::RAZORPAY_ORG_ID], Symbol::EQ)
                        ->filters(Table::MERCHANT, Merchant\Entity::BUSINESS_BANKING, [false], Symbol::EQ);

        $wdaQueryBuilder->namespace($this->getEntityObject()->getConnection()->getDatabaseName());

        $wdaQueryBuilder->cluster(WDAService::ADMIN_CLUSTER);

        $this->trace->info(TraceCode::WDA_SERVICE_QUERY, [
            'wda_query_builder' => $wdaQueryBuilder->build()->serializeToJsonString(),
            'route_name'        => $this->app['api.route']->getCurrentRouteName(),
        ]);

        $response = $wdaClient->fetchMultipleWithExpand($wdaQueryBuilder->build(), $this->newQuery()->getModel(), []);

        $l1NotSubmittedMerchantIds = (new Merchant\Repository)->convertWdaResponseToArray($response, Entity::MERCHANT_ID);

        $endTimeMs = round(microtime(true) * 1000);

        $queryDuration = $endTimeMs - $startTimeMs;

        $this->trace->info(TraceCode::WDA_SERVICE_RESPONSE, [
            'route_name'      => $this->app['api.route']->getCurrentRouteName(),
            'method_name'     => __FUNCTION__,
            'merchants_count' => count($l1NotSubmittedMerchantIds),
            'duration_ms'     => $queryDuration,
        ]);

        return $l1NotSubmittedMerchantIds;
    }

    public function filterL1NotSubmittedMerchantIdsWithEmailId(int $from, int $to): array
    {
        $detailMerchantIdColumn             = $this->dbColumn(Entity::MERCHANT_ID);
        $merchantCreatedAtColumn            = $this->repo->merchant->dbColumn(Merchant\Entity::CREATED_AT);
        $merchantIdColumn                   = $this->repo->merchant->dbColumn(Merchant\Entity::ID);
        $merchantOrgIdColumn                = $this->repo->merchant->dbColumn(Merchant\Entity::ORG_ID);
        $merchantBusinessBankingIdColumn    = $this->repo->merchant->dbColumn(Merchant\Entity::BUSINESS_BANKING);

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
                    ->join(Table::MERCHANT, $merchantIdColumn, '=', $detailMerchantIdColumn)
                    ->select(Entity::MERCHANT_ID)
                    ->whereBetween($merchantCreatedAtColumn, [$from, $to])
                    ->WhereNull(Entity::ACTIVATION_FORM_MILESTONE)
                    ->where($merchantOrgIdColumn,  '=' ,Org\Entity::RAZORPAY_ORG_ID)
                    ->whereNotNull(Entity::CONTACT_EMAIL)
                    ->where($merchantBusinessBankingIdColumn, '=', false)
                    ->get()
                    ->pluck(Entity::MERCHANT_ID)
                    ->toArray();
    }

    public function filterL2BankDetailsNotSubmittedMerchantIds(int $from, int $to, $org = Org\Entity::RAZORPAY_ORG_ID): array
    {
        $experimentResult = (new Detail\Core)->getSplitzResponse(UniqueIdEntity::generateUniqueId(),
            Merchant\Constants::WDA_MIGRATION_ACQUISITION_SPLITZ_EXP_ID);

        $isWDAExperimentEnabled = ( $experimentResult === 'live' ) ? true : false;

        try
        {
            if (($this->app['api.route']->isWDAServiceRoute() === true) and ($isWDAExperimentEnabled === true))
            {
                return $this->filterL2BankDetailsNotSubmittedMerchantIdsFromWda($from, $to, $org);
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->error(TraceCode::WDA_MIGRATION_ERROR, [
                'wda_migration_error' => $ex->getMessage(),
                'route_name'          => $this->app['api.route']->getCurrentRouteName(),
            ]);
        }

        $detailMerchantIdColumn             = $this->dbColumn(Entity::MERCHANT_ID);
        $merchantIdColumn                   = $this->repo->merchant->dbColumn(Merchant\Entity::ID);
        $merchantCreatedAtColumn            = $this->repo->merchant->dbColumn(Merchant\Entity::CREATED_AT);
        $merchantOrgIdColumn                = $this->repo->merchant->dbColumn(Merchant\Entity::ORG_ID);
        $merchantBusinessBankingIdColumn    = $this->repo->merchant->dbColumn(Merchant\Entity::BUSINESS_BANKING);

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->join(Table::MERCHANT, $merchantIdColumn, '=', $detailMerchantIdColumn)
            ->select(Entity::MERCHANT_ID)
            ->whereBetween($merchantCreatedAtColumn, [$from, $to])
            ->Where(Entity::ACTIVATION_FORM_MILESTONE, '=', 'L1')
            ->where($merchantOrgIdColumn, '=' , $org)
            ->where($merchantBusinessBankingIdColumn, '=', false)
            ->where(function($query)
            {
                $query->whereNull(Entity::BANK_ACCOUNT_NUMBER)
                    ->orWhereNull(Entity::BANK_BRANCH_IFSC);
            })
            ->get()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }

    /**
     * Filters all L2 bank details not submitted merchantIds from query through Wda service layer
     *
     * @param int $from
     * @param int $to
     * @param string $org
     *
     * @return array
     *
     * @throws Exception
     */
    public function filterL2BankDetailsNotSubmittedMerchantIdsFromWda(int $from, int $to, string $org = Org\Entity::RAZORPAY_ORG_ID): array
    {
        $this->trace->info(TraceCode::WDA_SERVICE_REQUEST, [
            'method_name'  => __FUNCTION__,
            'from'         => $from,
            'to'           => $to,
            'org'          => $org,
        ]);

        $startTimeMs = round(microtime(true) * 1000);

        $detailMerchantIdColumn             = $this->dbColumn(Entity::MERCHANT_ID);
        $merchantIdColumn                   = $this->repo->merchant->dbColumn(Merchant\Entity::ID);

        $wdaClient = $this->app['wda-client']->wdaClient;

        $wdaQueryBuilder = new WDAQueryBuilder();

        $wdaQueryBuilder->addQuery($this->getTableName(), Entity::MERCHANT_ID);

        $wdaQueryBuilder->resources($this->getTableName());

        $wdaQueryBuilder->addResource(Table::MERCHANT, 'inner', "$merchantIdColumn = $detailMerchantIdColumn");

        $isBankAccountNumberNull = $wdaQueryBuilder->filter($this->getTableName(), Entity::BANK_ACCOUNT_NUMBER, [], Symbol::NULL);
        $isBankBranchIfscNull = $wdaQueryBuilder->filter($this->getTableName(), Entity::BANK_BRANCH_IFSC, [], Symbol::NULL, Operator::OR);

        $wdaQueryBuilder->filters(Table::MERCHANT, Merchant\Entity::CREATED_AT, [$from, $to], Symbol::BETWEEN)
                        ->filters($this->getTableName(), Entity::ACTIVATION_FORM_MILESTONE, ['L1'], Symbol::EQ)
                        ->filters(Table::MERCHANT, Merchant\Entity::ORG_ID, [$org], Symbol::EQ)
                        ->filters(Table::MERCHANT, Merchant\Entity::BUSINESS_BANKING, [false], Symbol::EQ)
                        ->withSubFilter(Operator::AND, $isBankAccountNumberNull, $isBankBranchIfscNull);

        $wdaQueryBuilder->namespace($this->getEntityObject()->getConnection()->getDatabaseName());

        $wdaQueryBuilder->cluster(WDAService::ADMIN_CLUSTER);

        $this->trace->info(TraceCode::WDA_SERVICE_QUERY, [
            'wda_query_builder' => $wdaQueryBuilder->build()->serializeToJsonString(),
            'route_name'        => $this->app['api.route']->getCurrentRouteName(),
        ]);

        $response = $wdaClient->fetchMultipleWithExpand($wdaQueryBuilder->build(), $this->newQuery()->getModel(), []);

        $l2BankDetailsNotSubmittedMerchantIds = (new Merchant\Repository)->convertWdaResponseToArray($response, Entity::MERCHANT_ID);;

        $endTimeMs = round(microtime(true) * 1000);

        $queryDuration = $endTimeMs - $startTimeMs;

        $this->trace->info(TraceCode::WDA_SERVICE_RESPONSE, [
            'route_name'       => $this->app['api.route']->getCurrentRouteName(),
            'method_name'      => __FUNCTION__,
            'merchants_count'  => count($l2BankDetailsNotSubmittedMerchantIds),
            'duration_ms'      => $queryDuration,
        ]);

        return $l2BankDetailsNotSubmittedMerchantIds;
    }

    public function filterL1MilestoneSubmittedMerchantsOfOrg(int $from, int $to, array $orgList =[Org\Entity::RAZORPAY_ORG_ID]): array
    {
        $detailMerchantIdColumn             = $this->dbColumn(Entity::MERCHANT_ID);
        $merchantIdColumn                   = $this->repo->merchant->dbColumn(Merchant\Entity::ID);
        $merchantOrgIdColumn                = $this->repo->merchant->dbColumn(Merchant\Entity::ORG_ID);
        $merchantBusinessBankingIdColumn    = $this->repo->merchant->dbColumn(Merchant\Entity::BUSINESS_BANKING);
        $merchantCreatedAtColumn            = $this->repo->merchant->dbColumn(Merchant\Entity::CREATED_AT);

        return $this->newQuery()
            ->join(Table::MERCHANT, $merchantIdColumn, '=', $detailMerchantIdColumn)
            ->select(Entity::MERCHANT_ID)
            ->whereBetween($merchantCreatedAtColumn, [$from, $to])
            ->Where(Entity::ACTIVATION_FORM_MILESTONE, '=', 'L1')
            ->whereIn($merchantOrgIdColumn,$orgList)
            ->where($merchantBusinessBankingIdColumn, '=', false)
            ->get()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }

    public function filterNullAndInitiatedFieldStatusMerchants(string $entityName,array $merchantIds): array
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select(Entity::MERCHANT_ID)
                    ->whereIn(Entity::MERCHANT_ID, $merchantIds)
                    ->where(function($query) use ($entityName) {
                        $query->whereNull($entityName)
                              ->orWhere($entityName, "=", 'initiated');
                    })->get()
                    ->pluck(Entity::MERCHANT_ID)
                    ->toArray();
    }

    /*
     * $limit is optional here. Used for
    */
    public function getSubmerchantIdsByActivationStatus(string $partnerMerchantId, array $activationStatusList, int $limit = null): array
    {
        $merchantDetailMerchantId       = $this->dbColumn(Entity::MERCHANT_ID);
        $merchantDetailActivationStatus = $this->dbColumn(Entity::ACTIVATION_STATUS);

        $accessMapRepo           = $this->repo->merchant_access_map;
        $accessMapsMerchantId    = $accessMapRepo->dbColumn(AccessMap\Entity::MERCHANT_ID);
        $accessMapsEntityOwnerId = $accessMapRepo->dbColumn(AccessMap\Entity::ENTITY_OWNER_ID);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->join(Table::MERCHANT_ACCESS_MAP, $merchantDetailMerchantId, $accessMapsMerchantId)
                      ->select($merchantDetailMerchantId)
                      ->where($accessMapsEntityOwnerId, $partnerMerchantId)
                      ->whereIn($merchantDetailActivationStatus, $activationStatusList);

        if (empty($limit) === false)
        {
            $query = $query->take($limit);
        }

        return $query->get()
                     ->pluck(Entity::MERCHANT_ID)
                     ->toArray();
    }

    public function getSubmerchantIdsWithKYCSubmittedUnderReviewInPastDays(string $partnerMerchantId, int $pastDays, int $limit): array
    {
        $fromEpoch                      = Carbon::now()->subDays($pastDays)->getTimestamp();
        $merchantDetailMerchantId       = $this->dbColumn(Entity::MERCHANT_ID);
        $merchantDetailActivationStatus = $this->dbColumn(Entity::ACTIVATION_STATUS);

        $accessMapRepo           = $this->repo->merchant_access_map;
        $accessMapsMerchantId    = $accessMapRepo->dbColumn(AccessMap\Entity::MERCHANT_ID);
        $accessMapsEntityOwnerId = $accessMapRepo->dbColumn(AccessMap\Entity::ENTITY_OWNER_ID);

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->join(Table::MERCHANT_ACCESS_MAP, $merchantDetailMerchantId, $accessMapsMerchantId)
                    ->select($merchantDetailMerchantId)
                    ->where($accessMapsEntityOwnerId, $partnerMerchantId)
                    ->where($this->dbColumn(Entity::SUBMITTED_AT), '>=', $fromEpoch)
                    ->where($this->dbColumn(Entity::SUBMITTED), 1)
                    ->where($merchantDetailActivationStatus, Status::UNDER_REVIEW)
                    ->take($limit)
                    ->get()
                    ->pluck(Entity::MERCHANT_ID)
                    ->toArray();
    }

    public function countSubmerchantsWithKYCNotInitiatedInPastDays(string $partnerMerchantId, int $pastDays): int
    {
        $fromEpoch                = Carbon::now()->subDays($pastDays)->getTimestamp();
        $merchantDetailMerchantId = $this->dbColumn(Entity::MERCHANT_ID);
        $merchantDetailCreatedAt  = $this->dbColumn(Entity::CREATED_AT);

        $accessMapRepo           = $this->repo->merchant_access_map;
        $accessMapsMerchantId    = $accessMapRepo->dbColumn(AccessMap\Entity::MERCHANT_ID);
        $accessMapsEntityOwnerId = $accessMapRepo->dbColumn(AccessMap\Entity::ENTITY_OWNER_ID);

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->join(Table::MERCHANT_ACCESS_MAP, $merchantDetailMerchantId, $accessMapsMerchantId)
                    ->select($merchantDetailMerchantId)
                    ->where($accessMapsEntityOwnerId, $partnerMerchantId)
                    ->where($merchantDetailCreatedAt, '>=', $fromEpoch)
                    ->whereNull(Entity::ACTIVATION_STATUS)
                    ->count();
    }

    public function findMerchantWithContactNumbersExcludingMerchant(string $merchantIdToBeExcluded, array $numbers)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->whereIn(Entity::CONTACT_MOBILE, $numbers)
                    ->where(Entity::MERCHANT_ID, '!=', $merchantIdToBeExcluded)
                    ->first();
    }

    public function findMerchantBankDetailsWithIds(array $merchantIds): array
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select(Entity::MERCHANT_ID,
                             Entity::BANK_ACCOUNT_NAME,
                             Entity::BANK_ACCOUNT_NUMBER,
                             Entity::BANK_BRANCH_IFSC,
                             Entity::BANK_BENEFICIARY_ADDRESS1,
                             Entity::BANK_BENEFICIARY_ADDRESS2,
                             Entity::BANK_BENEFICIARY_ADDRESS3
                    )
                    ->whereIn(Entity::MERCHANT_ID,$merchantIds)
                    ->get()
                    ->toArray();
    }

    /**
     * This method returns list of mids who have any risk tags associated
     * @return array
     */
    public function getMerchantsWithRiskTags(): array
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->select(Entity::MERCHANT_ID)
            ->whereNotNull(Entity::FRAUD_TYPE)
            ->whereNot(Entity::FRAUD_TYPE, '')
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }

    public function findMerchantByActivationStatusAndActivationFormMileStone(array $activationStatusList,
                                                                             array $orgIdList = [Org\Entity::RAZORPAY_ORG_ID],
                                                                             int $updatedAt = null,
                                                                             string $activationMilestone = "L2"): array
    {
        $detailMerchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $merchantIdColumn       = $this->repo->merchant->dbColumn(Merchant\Entity::ID);
        $merchantOrgIdColumn    = $this->repo->merchant->dbColumn(Merchant\Entity::ORG_ID);
        $merchantParentIdColumn = $this->repo->merchant->dbColumn(Merchant\Entity::PARENT_ID);

        $query = $this->newQuery()
            ->join(Table::MERCHANT, $merchantIdColumn, '=', $detailMerchantIdColumn)
            ->whereIn($merchantOrgIdColumn, $orgIdList)
            ->where($merchantParentIdColumn, '=', null)
            ->select(Entity::MERCHANT_ID)
            ->whereIn(Entity::ACTIVATION_STATUS, $activationStatusList)
            ->Where(Entity::ACTIVATION_FORM_MILESTONE, '=', $activationMilestone)
            ->Where($this->dbColumn(Entity::UPDATED_AT), '>=', $updatedAt);


        return $query->get()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }
    // Merchants in needs clarification state in the merchant details table
    public function  filterMerchantsInNeedsClarification() : array
    {
        return $this->newQuery()
                    ->where(Entity::ACTIVATION_STATUS, Status::NEEDS_CLARIFICATION)
                    ->get()
                    ->pluck(Entity::MERCHANT_ID)
                    ->toArray();
    }

    public function fetchRzpOrgMerchantIdsWithPoiAndBankVerified(array $merchantIds) : array
    {
        // Merchant Details Table Column
        $detailMerchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);

        $poiVerificationColumn = $this->dbColumn(Entity::POI_VERIFICATION_STATUS);

        $bankDetailsVerificationColumn = $this->dbColumn(Entity::BANK_DETAILS_VERIFICATION_STATUS);

        // Merchant Table Column

        $merchantIdColumn = $this->repo->merchant->dbColumn(Merchant\Entity::ID);

        $merchantOrgIdColumn = $this->repo->merchant->dbColumn(Merchant\Entity::ORG_ID);

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
                    ->join(Table::MERCHANT, $merchantIdColumn, '=', $detailMerchantIdColumn)
                    ->whereIn($detailMerchantIdColumn, $merchantIds)
                    ->where($merchantOrgIdColumn, '=', Org\Entity::RAZORPAY_ORG_ID)
                    ->where($poiVerificationColumn, '=', 'verified')
                    ->where($bankDetailsVerificationColumn, '=', 'verified')
                    ->pluck($detailMerchantIdColumn)
                    ->toArray();
    }

    public function  filterMerchantIdsWithPoaStatusVerified(array $merchantIds) : array
    {
        // Merchant Detail Table column
        $detailMerchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);

        $poaVerificationColumn = $this->dbColumn(Entity::POA_VERIFICATION_STATUS);

        // Stakeholder Repo Column
        $stakeholderMerchantIdCol = $this->repo->stakeholder->dbColumn(Stakeholder\Entity::MERCHANT_ID);

        $aadhaarEsignStatusColumn = $this->repo->stakeholder->dbColumn(Stakeholder\Entity::AADHAAR_ESIGN_STATUS);

        $aadhaarEsignStatusWithPanColumn = $this->repo->stakeholder->dbColumn(Stakeholder\Entity::AADHAAR_VERIFICATION_WITH_PAN_STATUS);

        $query = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
                      ->leftJoin(Table::STAKEHOLDER, $detailMerchantIdColumn, '=', $stakeholderMerchantIdCol)
                      ->whereIn($detailMerchantIdColumn, $merchantIds)
                      ->where(function($query) use ($aadhaarEsignStatusColumn, $aadhaarEsignStatusWithPanColumn, $poaVerificationColumn) {
                          $query->where($poaVerificationColumn, '=', 'verified')
                                ->orWhere(function($query) use ($aadhaarEsignStatusWithPanColumn, $aadhaarEsignStatusColumn) {
                                    $query->where($aadhaarEsignStatusColumn, '=', 'verified')
                                          ->where($aadhaarEsignStatusWithPanColumn, '=', 'verified');
                                });
                      });

        return $query->pluck($detailMerchantIdColumn)
                     ->toArray();
    }

}

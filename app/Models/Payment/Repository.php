<?php

namespace RZP\Models\Payment;

use DB;
use App;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use RZP\Constants\Es;
use RZP\Base\Common;
use Database\Connection;

use RZP\Base\ConnectionType;
use RZP\Constants\Environment;
use RZP\Exception\LogicException;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Offer;
use RZP\Models\Order;
use RZP\Gateway\Enach;
use RZP\Constants\Mode;
use RZP\Base\BuilderEx;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Emi;
use RZP\Services\WDAService;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Constants\Table;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Constants\Timezone;
use RZP\Models\BankAccount;
use RZP\Models\Transaction;
use RZP\Models\PaymentLink;
use RZP\Models\BankTransfer;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Verify;
use RZP\Models\VirtualAccount;
use RZP\Models\Offer\EntityOffer;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Pricing\Calculator;

use RZP\Models\Bank\IFSC;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Base\Traits\ExternalCore;
use RZP\Models\Base\Traits\ArchivedCore;
use RZP\Constants\Entity as EntityName;
use RZP\Models\Base\Traits\ExternalRepo;
use RZP\Models\Gateway\Downtime\DowntimeDetection;
use RZP\Models\Merchant\Invoice\Type as InvoiceType;
use RZP\Models\QrCode\NonVirtualAccountQrCode as QrV2;
use RZP\Models\Merchant\Detail as MerchantDetail;
use Rzp\Wda_php\SortOrder;
use Rzp\Wda_php\Symbol;
use Rzp\Wda_php\WDAQueryBuilder;

class Repository extends Base\Repository
{
    use ExternalRepo, ExternalCore, ArchivedCore;

    const SECONDS_IN_A_YEAR = 31536000;

    protected $entity = 'payment';

    protected $cardQueryKeys = [
        Card\Entity::IIN,
        Card\Entity::LAST4,
    ];

    public const SUCCESSFUL_PAYMENTS_COUNT_SQL = <<<'EOT'
SELECT
  merchant_id,
  COUNT(*) AS payments_count
FROM
  hive.realtime_hudi_api.payments
WHERE
  authorized_at IS NOT NULL
  AND merchant_id IN (%s)
  AND created_date > '%s'
GROUP BY
  merchant_id
LIMIT
  %d
EOT;


    protected function serializeForIndexing(PublicEntity $entity): array
    {
        $serialized = parent::serializeForIndexing($entity);

        if ($entity->getReceiverType() === null) {
            return $serialized;
        }

        $vaTransactionIdExists = (($entity->bankTransfer !== null) or
                                  ($entity->upiTransfer !== null) or
                                  ($entity->bharatQr !== null));

        if ($vaTransactionIdExists !== true)
        {
            return $serialized;
        }

        $vaTransactionId = null;

        try
        {
            switch ($entity->getReceiverType())
            {
                case VirtualAccount\Receiver::BANK_ACCOUNT:
                    $vaTransactionId = $entity->bankTransfer->getUtr();
                    break;

                case VirtualAccount\Receiver::QR_CODE:
                    $vaTransactionId = $entity->bharatQr->getProviderReferenceId();
                    break;

                case VirtualAccount\Receiver::VPA:
                    $vaTransactionId = $entity->upiTransfer->getRRN();
                    break;

                default:
                    $this->trace->info(
                        TraceCode::INVALID_VA_RECEIVER_TYPE,
                        [
                            'receiver_type' => $entity->getReceiverType(),
                        ]
                    );
                    break;
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::ES_SYNC_SERIALIZATION_EXCEPTION);
        }

        if ($vaTransactionId !== null)
        {
            $serialized[Entity::VA_TRANSACTION_ID] = strtolower($vaTransactionId);
        }

        return $serialized;
    }

    protected function newQueryWithConnection($connection)
    {
        // DATA_WAREHOUSE_SOURCE_API_CONNECTIONS contains dummy connections. Mapping to right connection here.
        if (in_array($connection, array_keys(Connection::DATA_WAREHOUSE_SOURCE_API_CONNECTIONS)) === true)
        {
            $query = parent::newQueryWithConnection(Connection::DATA_WAREHOUSE_SOURCE_API_CONNECTIONS[$connection]);

            $query = $query->where($this->dbColumn(Base\Entity::RECORD_SOURCE), '=', Base\Constants::RECORD_SOURCE_API);
        }
        else
        {
            $query = parent::newQueryWithConnection($connection);
        }

        return $query;
    }

    protected function validateCustomerId($attribute, $value)
    {
        $merchant = $this->merchant;

        if ((empty($merchant) === false) and
            (Merchant\Entity::hascustomerTransactionHistoryEnabled($merchant->getId()) === false))
        {
            throw new Exception\ExtraFieldsException($attribute);
        }
    }

    /**
     * This validates the expand route to allow transfer and settlement expand only for linked account merchants
     * @param $attribute
     * @param $value
     *
     * @throws \RZP\Exception\ExtraFieldsException
     */
    protected function validateExpand($attribute, $value)
    {
        $merchant = $this->auth->getMerchant();

        if (((empty($merchant) === true) or ($merchant->isLinkedAccount() === false)) and
            (($value === 'transfer') or ($value === 'transfer.settlement')))
        {
            throw new Exception\ExtraFieldsException('expand=transfer');
        }
    }

    public function getRecentMerchantPaymentsForCheckoutId($checkoutId)
    {
        $timestamp = time() - Entity::PAYMENT_WINDOW;
        $currentTimestamp = time();

        $pid = $this->dbColumn(Payment\Entity::ID);
        $paPaymentId = $this->repo
                            ->payment_analytics
                            ->dbColumn(Analytics\Entity::PAYMENT_ID);

        $paymentColumns = $this->dbColumn('*');

        $paTable = $this->repo->payment_analytics->getTableName();
        $checkoutIdAttr = $this->repo
                               ->payment_analytics
                               ->dbColumn(Analytics\Entity::CHECKOUT_ID);

        $paymentAnalyticsCreatedAtAttr = $this->repo
                                              ->payment_analytics
                                              ->dbColumn(Analytics\Entity::CREATED_AT);

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select($paymentColumns)
                    ->join($paTable, $pid, '=', $paPaymentId)
                    ->where($checkoutIdAttr, '=', $checkoutId)
                    ->where($paymentAnalyticsCreatedAtAttr, '<', $currentTimestamp)
                    ->where($paymentAnalyticsCreatedAtAttr, '>', $timestamp)
                    ->createdAtGreaterThan($timestamp)
                    ->latest()
                    ->get();
    }

    public function fetchCapturedForGatewayBetweenTimestamp($from, $to, $gateway)
    {
        return $this->newQuery()
                    ->whereBetween(Payment\Entity::CAPTURED_AT, array($from, $to))
                    ->status(Payment\Status::CAPTURED)
                    ->where(Payment\Entity::GATEWAY, '=', $gateway)
                    ->get();
    }

    public function fetchAggregatedPaymentsForMethodBetweenTimePeriodForMerchantIds($midList,$from,$to,$method){

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->selectRaw(
                Payment\Entity::MERCHANT_ID.
                ', SUM(' . Payment\Entity::AMOUNT . ') as total_amount ,'.
                'SUM(' . Payment\Entity::FEE . ') as total_fee ,'.
                'SUM(' . Payment\Entity::MDR . ') as total_mdr'
            )
            ->whereBetween(Payment\Entity::CAPTURED_AT, array($from, $to))
            ->where(Payment\Entity::SETTLED_BY, '!=', 'Razorpay')
            ->whereIn(Payment\Entity::METHOD, $method)
            ->whereIn(Payment\Entity::MERCHANT_ID, $midList)
            ->groupBy(Payment\Entity::MERCHANT_ID)
            ->get();
    }

    public function fetchLastPaymentCaptureTimestampByMethodAndPeriodForMerchants($midList,$from,$to,$method){

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->selectRaw(
                'MAX(' . Payment\Entity::CAPTURED_AT . ') as last_capture_timestamp',
            )
            ->whereBetween(Payment\Entity::CAPTURED_AT, array($from, $to))
            ->where(Payment\Entity::SETTLED_BY, '!=', 'Razorpay')
            ->whereIn(Payment\Entity::METHOD, $method)
            ->whereIn(Payment\Entity::MERCHANT_ID, $midList)
            ->get();
    }

    public function fetchPendingCapturePaymentsBetweenTimestamps($from, $to, $limit = 100)
    {
        $query = $this->newQuery();

        return $query->whereBetween(Payment\Entity::CAPTURED_AT, array($from, $to))
                     ->whereNull(Payment\Entity::GATEWAY_CAPTURED)
                     ->limit($limit)
                     ->get();
    }

    public function fetchPaymentsStatusCountBetweenTimestamps(array $params,
                          string $merchantId = null,
                          bool $useSlave = false)
    {
        $query = $this->newQuery();

        if ($useSlave === true)
        {
            $connectionType = $this->getPaymentFetchReplicaConnection();

            $query = $this->newQueryWithConnection($connectionType);
        }
        else
        {
            $query = $this->newQueryWithConnection($this->getDataWarehouseConnection(ConnectionType::DATA_WAREHOUSE_MERCHANT));
        }

        $this->addCommonQueryParamMerchantId($query, $merchantId);

        // Splits the params into mysqlParams and esParams. Check methods doc on
        // how that happens.
        list($mysqlParams, $esParams) = $this->getMysqlAndEsParams($params);

        $query = $query->groupBy(Payment\Entity::STATUS)
                       ->selectRaw(
                       Payment\Entity::STATUS . ', ' .
                       'COUNT(*) AS count');

        if((empty($mysqlParams['from']) === false) and
           (empty($mysqlParams['to']) === false))
        {
            $query = $query->where(Payment\Entity::CREATED_AT, '>=', $mysqlParams['from'])
                           ->where(Payment\Entity::CREATED_AT, '<=', $mysqlParams['to']);
        }

        return  $query->get();

    }

    public function fetchPaymentsWithStatus($from, $to, $gateway, $status)
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
                    ->from(\DB::raw('`payments` FORCE INDEX (payments_authorized_at_index)'))
                    ->whereBetween(Payment\Entity::AUTHORIZED_AT, array($from, $to))
                    ->whereIn('status', $status)
                    ->where(Payment\Entity::GATEWAY, '=', $gateway)
                    ->get();
    }

    public function fetchPaymentsFailureAnalysisData($from, $to, $merchantId)
    {
        try
        {
            if(($this->app['api.route']->isWDAServiceRoute() === true) and
                ($this->isExperimentEnabled($this->app['api.route']->getWdaRouteExperimentName()) === true))
            {
                $wdaFailureAnalysis = $this->fetchPaymentsFailureAnalysisDataFromWda($from, $to, $merchantId);

                if(sizeof($wdaFailureAnalysis) > 0)
                {
                    return $wdaFailureAnalysis;
                }
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->error(TraceCode::WDA_MIGRATION_ERROR, [
                'wda_migration_error' => $ex->getMessage(),
                'route_name'          => $this->app['api.route']->getCurrentRouteName(),
            ]);
        }

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT))
            ->selectRaw(Entity::STATUS . ', '. Entity::INTERNAL_ERROR_CODE . ', ' . Entity::METHOD . ', COUNT(*) as count')
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->whereBetween(Entity::CREATED_AT, [$from, $to])
            ->groupBy(Entity::STATUS, Entity::INTERNAL_ERROR_CODE, Entity::METHOD)
            ->get();
    }

    public function fetchPaymentsFailureAnalysisDataFromWda($from, $to, $merchantId)
    {
        $this->trace->info(TraceCode::WDA_SERVICE_REQUEST, [
            'function'          => __FUNCTION__,
            'merchant_id'       => $merchantId,
            'from'              => $from,
            'to'                => $to,
            'route_name'        => $this->app['api.route']->getCurrentRouteName(),
        ]);

        $wdaQueryBuilder = new WDAQueryBuilder();

        $wdaQueryBuilder->addQuery($this->getTableName(), Entity::STATUS)
            ->addQuery($this->getTableName(), Entity::INTERNAL_ERROR_CODE)
            ->addQuery($this->getTableName(), Entity::METHOD)
            ->addQuery($this->getTableName(), '*', 'COUNT', 'count');
        $wdaQueryBuilder->resources($this->getTableName());
        $wdaQueryBuilder->filters($this->getTableName(), Entity::MERCHANT_ID, [$merchantId], Symbol::EQ)
            ->filters($this->getTableName(), Entity::CREATED_AT, [$from, $to], Symbol::BETWEEN);
        $wdaQueryBuilder->group($this->getTableName(), Entity::STATUS, SortOrder::DESC)
            ->group($this->getTableName(), Entity::INTERNAL_ERROR_CODE, SortOrder::DESC)
            ->group($this->getTableName(), Entity::METHOD, SortOrder::DESC);
        $wdaQueryBuilder->namespace($this->getEntityObject()->getConnection()->getDatabaseName());

        if ($this->app['env'] === Environment::PRODUCTION)
        {
            $wdaQueryBuilder->cluster(WDAService::MERCHANT_CLUSTER);
        }
        else
        {
            $wdaQueryBuilder->cluster(WDAService::ADMIN_CLUSTER);
        }

        $this->trace->info(TraceCode::WDA_SERVICE_QUERY, [
            'wda_query_builder' => $wdaQueryBuilder->build()->serializeToJsonString(),
            'route_name'    => $this->app['api.route']->getCurrentRouteName(),
        ]);

        $wdaClient = $this->app['wda-client']->wdaClient;

        $response = $wdaClient->fetchMultipleWithExpand($wdaQueryBuilder->build(), $this->newQuery()->getModel(),[]);

        $collection = new PublicCollection();

        foreach ($response as $arr)
        {
            $collection->push($arr);
        }

        if(sizeof($collection) > 0)
        {
            $this->trace->info(TraceCode::WDA_SERVICE_RESPONSE, [
                'size'          => $collection->count(),
                'route_name'        => $this->app['api.route']->getCurrentRouteName(),
            ]);
        }

        return $collection;
    }

    public function fetchCreatedPaymentsWithStatus($from, $to, $gateway, $status)
    {
        return $this->newQuery()
                    ->whereBetween(Payment\Entity::CREATED_AT, array($from, $to))
                    ->whereIn('status', $status)
                    ->where(Payment\Entity::GATEWAY, '=', $gateway)
                    ->get();
    }

    /**
     * Returns the captured payments
     * between the given timestamps (using CAPTURED_AT)
     * @param  int $from    timestamp for start of interval
     * @param  int $to      timestamp for end of interval
     * @return Collection of Payment
     */
    public function fetchCapturedBetweenTimestamp($from, $to, $merchantId)
    {
        return $this->newQuery()
                    ->whereBetween(Entity::CAPTURED_AT, [$from, $to])
                    ->where(Entity::STATUS, '=', Status::CAPTURED)
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->get();
    }

    /**
     * Returns all captured payments between timestamps
     * with gateway=paysecure and acquirer=axis
     * @param  int $from    timestamp for start of interval
     * @param  int $to      timestamp for end of interval
     * @return Collection of Payment
     */
    public function getAxisPaysecureCapturedTransactionsBetween($from, $to)
    {
        $terminalRepo = $this->repo->terminal;

        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $terminaltableName = $terminalRepo->getTableName();

        $paymentTerminalId = $this->dbColumn(Entity::TERMINAL_ID);

        $terminalId = $terminalRepo->dbColumn(Terminal\Entity::ID);

        $gateway = $terminalRepo->dbColumn(Terminal\Entity::GATEWAY);

        $acquirer = $terminalRepo->dbColumn(Terminal\Entity::GATEWAY_ACQUIRER);

        return $this->newQueryWithConnection($this->getDataWarehouseConnection())
            ->select($this->dbColumn('*'))
            ->join($terminaltableName, $paymentTerminalId, '=', $terminalId)
            ->whereBetween(Entity::CAPTURED_AT, [$from, $to])
            ->where(Entity::METHOD, '=', Method::CARD)
            ->where($gateway, '=', Payment\Gateway::PAYSECURE)
            ->where($acquirer, '=', Payment\Gateway::ACQUIRER_AXIS)
            ->with('merchant', 'card')
            ->get();
    }

    public function fetchEmiPaymentsWithCardTerminalsBetween($from, $to, $bank)
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $tRepo = $this->repo->terminal;

        $tTableName = $tRepo->getTableName();

        $terminalEmi = $tRepo->dbColumn(Terminal\Entity::EMI);

        $paymentTerminalId = $this->dbColumn(Entity::TERMINAL_ID);

        $paymentData = $this->dbColumn('*');

        $terminalId = $tRepo->dbColumn(Terminal\Entity::ID);

        $paymentStatus = $this->dbColumn(Entity::STATUS);

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
                    ->join($tTableName, $paymentTerminalId, '=', $terminalId)
                    ->whereBetween(Entity::CAPTURED_AT, [$from, $to])
                    ->where($paymentStatus, '=', Status::CAPTURED)
                    ->where(Entity::BANK, '=', $bank)
                    ->where(Entity::METHOD, '=', Method::EMI)
                    ->where($terminalEmi, '=', false)
                    ->with('card.globalCard', 'emiPlan', 'merchant', 'terminal')
                    ->select($paymentData)
                    ->get();
    }

    /**
     * Returns all captured payments between timestamps
     * with gateway, acquirer and bank code
     * @param  int $from        timestamp for start of interval
     * @param  int $to          timestamp for end of interval
     * @param  string $bank     bank code
     * @param  string $gateway  terminal gateway
     * @param  string $acquirer terminal gateway acquirer
     * @return PublicCollection of Payment
     */
    public function fetchEmiPaymentsWithGatewayAndAcquirerBetween($from, $to, $bank, $gateway, $acquirer): PublicCollection
    {
        $tRepo = $this->repo->terminal;

        $tTableName = $tRepo->getTableName();

        $terminalGateway = $tRepo->dbColumn(Terminal\Entity::GATEWAY);

        $terminalGatewayAcquirer = $tRepo->dbColumn(Terminal\Entity::GATEWAY_ACQUIRER);

        $paymentTerminalId = $this->dbColumn(Entity::TERMINAL_ID);

        $paymentData = $this->dbColumn('*');

        $terminalId = $tRepo->dbColumn(Terminal\Entity::ID);

        $paymentStatus = $this->dbColumn(Entity::STATUS);

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->join($tTableName, $paymentTerminalId, '=', $terminalId)
            ->whereBetween(Entity::CAPTURED_AT, [$from, $to])
            ->where($paymentStatus, '=', Status::CAPTURED)
            ->where(Entity::BANK, '=', $bank)
            ->where(Entity::METHOD, '=', Method::EMI)
            ->where($terminalGateway, '=', $gateway)
            ->where($terminalGatewayAcquirer, '=', $acquirer)
            ->with('card.globalCard', 'emiPlan', 'merchant', 'terminal')
            ->select($paymentData)
            ->get();
    }

    public function fetchCardPaymentsForGatewayAndMerchantBetween($from, $to, $merchantIds)
    {
        $paymentData = $this->dbColumn('*');

        $gateway = $this->dbColumn(Entity::GATEWAY);

        $paymentMerchantId = $this->dbColumn(Entity::MERCHANT_ID);

        return $this->newQueryWithConnection($this->getDataWarehouseConnection())
            ->whereBetween(Entity::CAPTURED_AT, [$from, $to])
            ->where($gateway, '=', 'cybersource')
            ->whereIn($paymentMerchantId, $merchantIds)
            ->where(Entity::METHOD, '=', Method::CARD)
            ->with('card.globalCard')
            ->orderBy($this->dbColumn(Entity::CAPTURED_AT), 'desc')
            ->select($paymentData)
            ->get();
    }

    /**
     *  refer: https://razorpay.slack.com/archives/CQ932EVNH/p1624709316068200
     */
    public function fetchPaymentWithForceIndex(array $params, string $merchantId = null)
    {
        $startTimeMsForTrace = round(microtime(true) * 1000);

        // Process params (sanitization, validation, modification, etc.)
        $this->processFetchParams($params);

        $expands = $this->getExpandsForQueryFromInput($params);

        $connection = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN);

        try
        {
            if($this->app['api.route']->isWDAServiceRoute() === true)
            {
                $this->trace->info(TraceCode::WDA_FETCH_PAYMENT_WITH_FORCE_INDEX, [
                    'input_params'     => $params,
                    'expand_params'    => $expands,
                    'route_auth'       => $this->auth->getAuthType(),
                    'route_name'       => $this->app['api.route']->getCurrentRouteName(),
                ]);
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->error(TraceCode::WDA_SERVICE_LOGGING_ERROR, [
                'error_message'    => $ex->getMessage(),
                'route_name'       => $this->app['api.route']->getCurrentRouteName(),
            ]);
        }

        if (!is_null($merchantId) &&
            count(array_diff(array_keys($params), ["skip", "count", "from", "to"])) === 0)
        {
            $app = App::getFacadeRoot();

            // The variant is used for switching between tidb admin / merchant -> slave
            // and also for reverting back to ES and slave in case admin tibd is not able
            // to support queries
            if (($this->isExperimentEnabled(self::MERCHANT_TIDB_EXPERIMENT) === true) or
                (app()->isEnvironmentProduction() === false))
            {

                // TiDB Merchant here because part of bulk fetch will be accessible by merchant only
                $connection = $this->getDataWarehouseConnection(ConnectionType::DATA_WAREHOUSE_MERCHANT);

                $query = $this->newQueryWithConnection($connection);
            }
            else
            {
                try
                {
                    $paymentIds = (new EsRepository('payment'))->buildQueryAndSearch($params, $merchantId);

                    $paymentIdsFiltered = array_map(
                        function ($res) {
                            return $res[ES::_SOURCE] ?? [Common::ID => $res[ES::_ID]];
                        },
                        $paymentIds[ES::HITS][ES::HITS]);

                    if (count($paymentIdsFiltered) > 0) {
                        $connection = $this->getPaymentFetchReplicaConnection();

                        $result = $this->newQueryWithConnection($connection)
                            ->whereIn(Entity::ID, $paymentIdsFiltered)
                            ->where(Entity::MERCHANT_ID, $merchantId)
                            ->with($expands)
                            ->orderBy(Entity::CREATED_AT, 'desc')
                            ->get();

                        $this->traceBeforeReturnFromFetchPaymentWithForceIndex($startTimeMsForTrace, $connection, true);

                        return $result;
                    }

                    $this->traceBeforeReturnFromFetchPaymentWithForceIndex($startTimeMsForTrace, $connection, true);

                    return (new Base\PublicCollection());
                } catch (\Exception $e)
                {
                    $this->trace->error(TraceCode::PAYMENT_FETCH_MULTIPLE_ES_FAILURE, [
                        'error' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ]);

                    $connection = $this->getPaymentFetchReplicaConnection();

                    $query = $this->newQueryWithConnection($connection);
                }
            }
        }
        else
        {
            $query = $this->newQueryWithConnection($connection);
        }

        $this->setEsRepoIfExist();

        // Splits the params into mysqlParams and esParams. Check methods doc on
        // how that happens.
        list($mysqlParams, $esParams) = $this->getMysqlAndEsParams($params);

        // If we find that there are es params then we do es search.
        // Currently (as commented in getMysqlAndEsParams method) we raise bad
        // request error if we get mix of MySQL and es params. Later we might support
        // such thing.
        if (count($esParams) > 0)
        {
            return $this->runEsFetch($esParams, $merchantId, $expands,ConnectionType::DATA_WAREHOUSE_ADMIN);
        }

        // Found a bug where Merchant SDK intg private auth calls were
        // going to admin cluster, returning the same result for private
        // auth and paginated result for proxy auth.
        if (($this->auth->isPrivateAuth() === true) or
            ($this->auth->isProxyAuth() === true))
        {
            $connection = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT);

            // Routing all payment queries containing contact filter to harvester replica as contact index
            // is only present in harvester replica. With API decomp this query, needs to go to WDA service in the future.
            // Note: contact index should be added on payments table in WDA service.
            if (isset($params['contact']))
            {
                $connection = $this->getConnectionFromType($this->getPaymentFetchReplicaConnection());
            }

            $query = $this->newQueryWithConnection($connection);
        }

        $query = $query->with($expands);

        $this->addCommonQueryParamMerchantId($query, $merchantId);

        // If above doesn't happen we build query for mysql fetch and return the
        // result.
        $query = $this->buildFetchQuery($query, $mysqlParams);

        // Check if the connection is going to Tidb cluster and form the query object
        // for WDA.
        $isWda = false;

        try
        {
            if($this->checkWdaRouteForFetchPayment($expands, $this->getWdaConnectionType($connection)) === true)
            {
                $wdaQueryBuilder = $this->buildWdaQuery($query, $connection, $merchantId, $mysqlParams);

                $isWda = true;
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->error(TraceCode::WDA_MIGRATION_ERROR, [
                'wda_query_builder_error' => $ex->getMessage(),
                'route_name'    => $this->app['api.route']->getCurrentRouteName(),
            ]);
        }

        //
        // For now, we want to expose this only for proxy auth.
        // We would want to expose this to private auth as well
        // in the future, but need a little bit though around
        // how we want to expose it. Pagination has lot of standards
        // generally and we might want to follow those when
        // exposing on private auth. SDKs _might_ have to fixed too.
        //
        if ($this->auth->isProxyAuth() === true)
        {
            $result = $this->getPaginated($query, $params);

            //Adding pagination for call going to tidb via wda-service
            try
            {
                if($isWda === true)
                {
                    $wdaStartTimeMs = round(microtime(true) * 1000);

                    $wdaResult = $this->getPaginatedFromWDA($wdaQueryBuilder, $query, $params);

                    $difference = $this->compareAndLogEntitiesInShadowMode($wdaResult, $result, $wdaStartTimeMs);

                    if ($difference === false)
                    {
                        $this->traceBeforeReturnFromFetchPaymentWithForceIndex($startTimeMsForTrace, "WDA");

                        return $wdaResult;
                    }
                }
            }
            catch(\Throwable $ex)
            {
                $this->trace->error(TraceCode::WDA_MIGRATION_ERROR, [
                    'wda_migration_error_pagination' => $ex->getMessage(),
                    'route_name'    => $this->app['api.route']->getCurrentRouteName(),
                ]);
            }

            $this->traceBeforeReturnFromFetchPaymentWithForceIndex($startTimeMsForTrace, $connection);

            return $result;
        }

        try
        {
            $startTimeMs = round(microtime(true) * 1000);

            $entities = $query->get();

            $endTimeMs = round(microtime(true) * 1000);

            //When the auth type does not requires pagination
            try
            {
                if ($isWda === true)
                {
                    $wdaStartTimeMs = round(microtime(true) * 1000);

                    $wdaEntities = $this->getEntitiesFromWda($wdaQueryBuilder, $query);

                    $difference = $this->compareAndLogEntitiesInShadowMode($wdaEntities, $entities, $wdaStartTimeMs);

                    if ($difference === false)
                    {
                        $this->traceBeforeReturnFromFetchPaymentWithForceIndex($startTimeMsForTrace, "WDA");

                        return $wdaEntities;
                    }
                }
            }
            catch(\Throwable $ex)
            {
                $this->trace->error(TraceCode::WDA_MIGRATION_ERROR, [
                    'wda_migration_error' => $ex->getMessage(),
                    'route_name'    => $this->app['api.route']->getCurrentRouteName(),
                ]);
            }

            $queryDuration = $endTimeMs - $startTimeMs;

            $this->trace->info(TraceCode::DATA_WAREHOUSE_PAYMENT_FETCH_DURATION,
                [
                    'connection' => $connection,
                    'query_ctx' => is_null($merchantId) ? 'admin' : 'merchant',
                    'duration_ms' => $queryDuration,
                    'query' => $query->toSql(),
                    'sql_error_code' => ($queryDuration > 3000) ? 1 : 0,
                ]
            );

            $this->traceBeforeReturnFromFetchPaymentWithForceIndex($startTimeMsForTrace, $connection);

            return $entities;
        }
        catch (\Exception $e)
        {
            $this->trace->error(TraceCode::DATA_WAREHOUSE_PAYMENT_FETCH_ERROR, [
                'connection' => $connection,
                'query_ctx' => is_null($merchantId) ? 'admin' : 'merchant',
                'query' => $query->toSql(),
                'sql_error_code' => 2,
            ]);

            throw $e;
        }
    }

    protected function buildWdaQuery($query, $connection, $merchantId, $mysqlParams) : WDAQueryBuilder
    {
        $this->trace->info(TraceCode::WDA_SERVICE_REQUEST, [
            'method_name' => __FUNCTION__,
            'route_name'  =>  $this->app['api.route']->getCurrentRouteName(),
        ]);

        $wdaQueryBuilder = new WDAQueryBuilder();

        $wdaQueryBuilder->addQuery($this->getTableName(), '*')
            ->resources($this->getTableName());
        $wdaQueryBuilder->namespace($query->getConnection()->getDatabaseName());

        if ($this->app['env'] === Environment::PRODUCTION)
        {
            $connectionType = $this->getWdaConnectionType($connection);

            if($connectionType === ConnectionType::DATA_WAREHOUSE_MERCHANT)
            {
                $wdaQueryBuilder->cluster(WDAService::MERCHANT_CLUSTER);
            }
            else
            {
                $wdaQueryBuilder->cluster(WDAService::ADMIN_CLUSTER);
            }
        }
        else
        {
            $wdaQueryBuilder->cluster(WDAService::ADMIN_CLUSTER);
        }

        $this->addCommonWDAQueryParamMerchantId($wdaQueryBuilder, $merchantId);

        $this->buildWDAFetchQuery($wdaQueryBuilder, $mysqlParams);

        return $wdaQueryBuilder;
    }

    public function fetchEmiPaymentsWithRelationsBetween($from, $to, $bank, $relations)
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $tRepo = $this->repo->terminal;

        $tTableName = $tRepo->getTableName();

        $terminalEmi = $tRepo->dbColumn(Terminal\Entity::EMI);

        $paymentTerminalId = $this->dbColumn(Entity::TERMINAL_ID);

        $paymentData = $this->dbColumn('*');

        $terminalId = $tRepo->dbColumn(Terminal\Entity::ID);

        $paymentStatus = $this->dbColumn(Entity::STATUS);

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
                    ->join($tTableName, $paymentTerminalId, '=', $terminalId)
                    ->whereBetween(Entity::CAPTURED_AT, [$from, $to])
                    ->where($paymentStatus, '=', Status::CAPTURED)
                    ->where(Entity::BANK, '=', $bank)
                    ->where(Entity::METHOD, '=', Method::EMI)
                    ->where($terminalEmi, '=', false)
                    ->with($relations)
                    ->select($paymentData)
                    ->get();
    }

    public function fetchNoCostEmiPaymentsWithBankCode($from, $to, $bank)
    {
        $paymentData = $this->dbColumn('*');
        $paymentStatus = $this->dbColumn(Entity::STATUS);
        $paymentIdCol = $this->dbColumn(Entity::ID);

        $entityOfferTable = $this->repo->entity_offer->getTableName();
        $entityOfferEntityIdCol = $this->repo->entity_offer->dbColumn(EntityOffer\Entity::ENTITY_ID);
        $entityOfferEntityTypeCol = $this->repo->entity_offer->dbColumn(EntityOffer\Entity::ENTITY_TYPE);
        $entityOfferOfferIdCol = $this->repo->entity_offer->dbColumn(EntityOffer\Entity::OFFER_ID);
        $entityOfferOfferTypeCol = $this->repo->entity_offer->dbColumn(EntityOffer\Entity::ENTITY_OFFER_TYPE);

        $offerTable = $this->repo->offer->getTableName();
        $offerEntityIdCol = $this->repo->offer->dbColumn(UniqueIdEntity::ID);
        $emiSubventionCol = $this->repo->offer->dbColumn(Offer\Entity::EMI_SUBVENTION);

        $query =  $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->join($entityOfferTable, $entityOfferEntityIdCol, '=', $paymentIdCol)
            ->join($offerTable, $entityOfferOfferIdCol, '=', $offerEntityIdCol)
            ->where($entityOfferEntityTypeCol, '=', EntityName::PAYMENT)
            ->where($entityOfferOfferTypeCol, '=', EntityName::OFFER)
            ->where($emiSubventionCol, '=', true)
            ->whereBetween(Entity::CAPTURED_AT, [$from, $to])
            ->where($paymentStatus, '=', Status::CAPTURED)
            ->where(Entity::BANK, '=', $bank)
            ->where(Entity::METHOD, '=', Method::EMI)
            ->select($paymentData);
            //                    ->get();
//        s($query->toSql());
        return $query->get();
    }

    public function fetchEmiPaymentsOfCobrandingPartnerWithRelationsBetween($from, $to, $cobrandingPartner, $relations)
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $tRepo = $this->repo->terminal;

        $tTableName = $tRepo->getTableName();

        $cardTableName = $this->repo->card->getTableName();

        $terminalEmi = $tRepo->dbColumn(Terminal\Entity::EMI);

        $paymentTerminalId = $this->dbColumn(Entity::TERMINAL_ID);

        $paymentCardId = $this->dbColumn(Entity::CARD_ID);

        $paymentData = $this->dbColumn('*');

        $terminalId = $tRepo->dbColumn(Terminal\Entity::ID);

        $cardId = $this->repo->card->dbColumn(Card\Entity::ID);

        $paymentStatus = $this->dbColumn(Entity::STATUS);

        $network = $this->repo->card->dbColumn(Card\Entity::NETWORK);

        $issuer = $this->repo->card->dbColumn(Card\Entity::ISSUER);

        $paymentEmiPlanId = $this->dbColumn(Entity::EMI_PLAN_ID);

        $emiPlanRepo = $this->repo->emi_plan;

        $emiPlanTableName = $emiPlanRepo->getTableName();

        $emiCobrandingPartner = $emiPlanRepo->dbColumn(Emi\Entity::COBRANDING_PARTNER);

        $emiPlanId = $emiPlanRepo->dbColumn(Emi\Entity::ID);

        $query = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->join($tTableName, $paymentTerminalId, '=', $terminalId)
            ->join($cardTableName, $paymentCardId, '=', $cardId)
            ->join($emiPlanTableName, $paymentEmiPlanId, '=', $emiPlanId)
            ->whereBetween(Entity::CAPTURED_AT, [$from, $to])
            ->where($paymentStatus, '=', Status::CAPTURED)
            ->where($network, '=', Card\Network::$fullName[Card\Network::VISA])
            ->whereIn($issuer, Card\Issuer::getAllOnecardIssuers())
            ->where(Entity::METHOD, '=', Method::EMI)
            ->where($terminalEmi, '=', false)
            ->where($emiCobrandingPartner, '=', $cobrandingPartner)
            ->with($relations)
            ->select($paymentData);

        return $query->get();
    }

    public function fetchEmiPaymentsOfCobrandingPartnerAndBankWithRelationsBetween($from, $to, $cobrandingPartner, $bank, $relations)
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $tRepo = $this->repo->terminal;

        $tTableName = $tRepo->getTableName();

        $cardTableName = $this->repo->card->getTableName();

        $terminalEmi = $tRepo->dbColumn(Terminal\Entity::EMI);

        $paymentTerminalId = $this->dbColumn(Entity::TERMINAL_ID);

        $paymentCardId = $this->dbColumn(Entity::CARD_ID);

        $paymentData = $this->dbColumn('*');

        $terminalId = $tRepo->dbColumn(Terminal\Entity::ID);

        $cardId = $this->repo->card->dbColumn(Card\Entity::ID);

        $paymentStatus = $this->dbColumn(Entity::STATUS);

        $paymentBank = $this->dbColumn(Entity::BANK);

        $paymentEmiPlanId = $this->dbColumn(Entity::EMI_PLAN_ID);

        $emiPlanRepo = $this->repo->emi_plan;

        $emiPlanTableName = $emiPlanRepo->getTableName();

        $emiCobrandingPartner = $emiPlanRepo->dbColumn(Emi\Entity::COBRANDING_PARTNER);

        $emiPlanId = $emiPlanRepo->dbColumn(Emi\Entity::ID);

        $query = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->join($tTableName, $paymentTerminalId, '=', $terminalId)
            ->join($cardTableName, $paymentCardId, '=', $cardId)
            ->join($emiPlanTableName, $paymentEmiPlanId, '=', $emiPlanId)
            ->whereBetween(Entity::CAPTURED_AT, [$from, $to])
            ->where($paymentStatus, '=', Status::CAPTURED)
            ->where(Entity::METHOD, '=', Method::EMI)
            ->where($paymentBank, '=', $bank)
            ->where($terminalEmi, '=', false)
            ->where($emiCobrandingPartner, '=', $cobrandingPartner)
            ->with($relations)
            ->select($paymentData);

        return $query->get();
    }

    public function fetchCreatedPaymentsWithInternalError($timestamp)
    {
        return $this->newQuery()
                    ->status(Payment\Status::CREATED)
                    ->whereNotNull(Payment\Entity::INTERNAL_ERROR_CODE)
                    ->where(Payment\Entity::CREATED_AT, '<=', $timestamp)
                    ->get();
    }

    /**
     * Fetches entity with given id with a mysql lock for update
     *
     * @param string       $id
     * @param bool|boolean $withTrashed
     *
     * withTrashed: Method signature changed to make it compatible
     *              with Base/Repository's method.
     *
     * @return Entity
     */
    public function lockForUpdate(string $id, bool $withTrashed = false)
    {
        // Not fetching external payments. lockForUpdate not needed
        $payment = $this->findOrFailArchived($id);

        try
        {
            if ((method_exists($payment, 'isArchived') === true) and
                ($payment->isArchived() === true))
            {
                $this->saveOrFail($payment);
            }
        }
        catch (\Exception $ex) {}

        return $this->newQuery()
                    ->lockForUpdate()->findOrFail($id);
    }

    public function timeoutOldPayments($timestamp)
    {
        return $this->newQuery()
                    ->status(Payment\Status::CREATED)
                    ->whereNull(Payment\Entity::INTERNAL_ERROR_CODE)
                    ->where(Payment\Entity::CREATED_AT, '<=', $timestamp)
                    ->update(
                        array(
                            Payment\Entity::STATUS => Payment\Status::FAILED,
                            Payment\Entity::ERROR_CODE => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,
                            Payment\Entity::ERROR_DESCRIPTION => PublicErrorDescription::BAD_REQUEST_PAYMENT_TIMED_OUT)
                        );
    }

    /**
     * Fetches old payments which can be timed-out with respective
     * merchant relation.
     */
    public function fetchOldCreatedPaymentsForTimeout(int $timestamp, int $limit)
    {
        $query = $this->newQuery();

        return $query
                    ->status(Payment\Status::CREATED)
                    ->where(Payment\Entity::CREATED_AT, '<=', $timestamp)
                    ->with(['merchant', 'merchant.features'])
                    ->limit($limit)
                    ->get();
    }

    /**
     * Fetches old payments which can be timed-out at method level with respective
     * merchant relation.
     * @param int    $fromTimestamp
     * @param int    $toTimestamp
     * @param int    $limit
     * @param string $method
     * @param string $emandateRecurringType
     * @param array  $includeMerchantList List of merchant IDs to be fetched
     * @param array  $excludeMerchantList List of merchant IDs to be ignored
     * @return mixed
     */
    public function fetchOldCreatedPaymentsForMethodForTimeout(int $fromTimestamp, int $toTimestamp, int $limit, string $method, $emandateRecurringType, array $includeMerchantList, array $excludeMerchantList, $filterPaymentPushedToKafka)
    {
        return $this->repo->useSlave(function() use ($fromTimestamp, $toTimestamp, $limit, $method, $emandateRecurringType, $includeMerchantList, $excludeMerchantList, $filterPaymentPushedToKafka)
        {
            $query = $this->newQuery();

            // keep the experiment for some more time, not needed to move to TiDB
            if ($this->isExperimentEnabledForId(self::PAYMENT_QUERIES_TIDB_MIGRATION, 'fetchOldCreatedPaymentsForMethodForTimeout') === true)
            {
                $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);

                $query = $this->newQueryWithConnection($connectionType);
            }

            $query = $query
                        ->from(\DB::raw('`payments` FORCE INDEX (payments_status_index)'))
                        ->status(Payment\Status::CREATED)
                        ->where(Payment\Entity::CREATED_AT, '>=', $fromTimestamp)
                        ->where(Payment\Entity::CREATED_AT, '<=', $toTimestamp)
                        ->where(Payment\Entity::METHOD, '=', $method);

            if (count($includeMerchantList) > 0)
            {
                $query->whereIn(Payment\Entity::MERCHANT_ID, $includeMerchantList);
            }
            else if (count($excludeMerchantList) > 0)
            {
                $query->whereNotIn(Payment\Entity::MERCHANT_ID, $excludeMerchantList);
            }

            if ($emandateRecurringType !== null)
            {
                $query->where(Payment\Entity::RECURRING_TYPE, '=', $emandateRecurringType);
            }

            if ($filterPaymentPushedToKafka == true)
            {
                $query->where(function ($query)
                {
                    $query->where(Payment\Entity::IS_PUSHED_TO_KAFKA, '=', Payment\Processor\Constants::VERIFY_VIA_SCHEDULER)
                        ->orWhereNull(Payment\Entity::IS_PUSHED_TO_KAFKA);
                });
            }

            return $query->with(['merchant', 'merchant.features'])
                         ->limit($limit)
                         ->get();
        });
    }

    public function getMerchantFirstAuthorizedPaymentTimeStamp($merchantId)
    {
        return $this->newQuery()
                    ->whereNotNull(Payment\Entity::AUTHORIZED_AT)
                    ->where(Payment\Entity::MERCHANT_ID, '=', $merchantId)
                    ->min(Payment\Entity::CREATED_AT);
    }

    public function getMerchantTransactionCountBetweenTimestamps($merchantId, $from, $to)
    {
        $connectionType = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT);

        $query = $this->newQueryWithConnection($connectionType);

        return $query
            ->whereNotNull(Payment\Entity::AUTHORIZED_AT)
            ->where(Payment\Entity::MERCHANT_ID, '=', $merchantId)
            ->whereBetween(Payment\Entity::CREATED_AT, [$from, $to])
            ->count();
    }


    /**
     * Fetches old payments which can be timed-out at method level with respective
     * merchant relation.
     * @param int $fromTimestamp
     * @param int $toTimestamp
     * @param int $limit
     * @param string $method
     */
    public function fetchOldAuthenticatedPaymentsForMethodForTimeout(int $fromTimestamp, int $toTimestamp, int $limit, string $method)
    {
        return $this->repo->useSlave(function() use ($fromTimestamp, $toTimestamp, $limit, $method)
        {
            $query = $this->newQuery();

            // keep the experiment for some more time, not needed to move to TiDB
            if ($this->isExperimentEnabledForId(self::PAYMENT_QUERIES_TIDB_MIGRATION, 'fetchOldAuthenticatedPaymentsForMethodForTimeout') === true)
            {
                $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);

                $query = $this->newQueryWithConnection($connectionType);
            }

            return $query
                ->from(\DB::raw('`payments` FORCE INDEX (payments_status_index)'))
                ->status(Payment\Status::AUTHENTICATED)
                ->where(Payment\Entity::AUTHENTICATED_AT, '>=', $fromTimestamp)
                ->where(Payment\Entity::AUTHENTICATED_AT, '<=', $toTimestamp)
                ->where(Payment\Entity::METHOD, '=', $method)
                ->with(['merchant', 'merchant.features'])
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Fetches min created_at for particular method in created state.
     * @param string $method
     * @param string $emandateRecurringType
     * @param array  $includeMerchantList List of merchant IDs to be fetched
     * @param array  $excludeMerchantList List of merchant IDs to be ignored
     * @return int
     */
    public function fetchOldPaymentsMinCreatedForMethodForTimeout(string $method, $emandateRecurringType, array $includeMerchantList, array $excludeMerchantList, $filterPaymentPushedToKafka)
    {
        return $this->repo->useSlave(function() use ($method, $emandateRecurringType, $includeMerchantList, $excludeMerchantList, $filterPaymentPushedToKafka)
        {
            $query = $this->newQueryWithConnection($this->getReportingReplicaConnection())
                      ->from(\DB::raw('`payments` FORCE INDEX (payments_status_index)'))
                      ->status(Payment\Status::CREATED)
                      ->where(Payment\Entity::METHOD, '=', $method);

            if (count($includeMerchantList) > 0)
            {
                $query->whereIn(Payment\Entity::MERCHANT_ID, $includeMerchantList);
            }
            else if (count($excludeMerchantList) > 0)
            {
                $query->whereNotIn(Payment\Entity::MERCHANT_ID, $excludeMerchantList);
            }

            if ($emandateRecurringType !== null)
            {
                $query->where(Payment\Entity::RECURRING_TYPE, '=', $emandateRecurringType);
            }

            if ($filterPaymentPushedToKafka == true)
            {
                $query->where(function ($query)
                {
                    $query->where(Payment\Entity::IS_PUSHED_TO_KAFKA, '=', Payment\Processor\Constants::VERIFY_VIA_SCHEDULER)
                        ->orWhereNull(Payment\Entity::IS_PUSHED_TO_KAFKA);
                });
            }

            return $query->min(Entity::CREATED_AT);
        });
    }

    /**
     * Fetches min authenticated_at for particular method in authenticated state.
     * @param string $method
     * @return int
     */
    public function fetchOldPaymentsMinAuthenticatedForMethodForTimeout(string $method)
    {
        return $this->repo->useSlave(function() use ($method)
        {
            $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);

            return  $this->newQueryWithConnection($connectionType)
                      ->from(\DB::raw('`payments` FORCE INDEX (payments_status_index)'))
                      ->status(Payment\Status::AUTHENTICATED)
                      ->where(Payment\Entity::METHOD, '=', $method)
                      ->min(Entity::AUTHENTICATED_AT);
        });
    }

    /**
     * This function is used to fetch the authorized payments where
     * Merchant auto refund delay is null.
     *
     * @param int  $timestamp
     * @param bool $getDisputed Flag to check whether to get disputed payments
     *
     * @return Base\PublicCollection
     */
    public function getAuthorizedPaymentsBeforeTimestamp(
        int $timestamp,
        bool $getDisputed = true): Base\PublicCollection
    {
        $createdAt  = $this->dbColumn(Entity::CREATED_AT);
        $merchantId = $this->repo->merchant->dbColumn(Merchant\Entity::ID);

        $query = $this->newQuery()
                      ->select($this->dbColumn('*'))
                      ->join(Table::MERCHANT, Entity::MERCHANT_ID, '=', $merchantId)
                      ->whereNull(Merchant\Entity::AUTO_REFUND_DELAY)
                      ->status(Payment\Status::AUTHORIZED)
                      ->where($createdAt, '<=', $timestamp)
                      ->orderBy(Payment\Entity::MERCHANT_ID);

        // Check if we should pick disputed payments for refund
        if ($getDisputed === false)
        {
            $query = $query->where(Entity::DISPUTED, '=', 0);
        }

        return $query->get();
    }

    /**
     * This function is used to fetch the authorized payments
     * that are not disputed with merchant auto delay delay
     *
     * @return Base\PublicCollection
     */
    public function getAuthorizedPaymentsWithAutoRefundDelay()
    {
        $paymentCreatedAt = $this->dbColumn(Entity::CREATED_AT);
        $merchantId       = $this->repo->merchant->dbColumn(Merchant\Entity::ID);

        $minCreatedAt = Carbon::now()->subSeconds(Merchant\Entity::MIN_AUTO_REFUND_DELAY)->getTimestamp();

        $rawCondition = '(' . time() . ' - ' . $paymentCreatedAt . ') > ' . Merchant\Entity::AUTO_REFUND_DELAY;

        return $this->newQuery()
                    ->select($this->dbColumn('*'))
                    ->join(Table::MERCHANT, Entity::MERCHANT_ID, '=', $merchantId)
                    ->status(Payment\Status::AUTHORIZED)
                    ->whereRaw($rawCondition)
                    ->whereNotNull(Merchant\Entity::AUTO_REFUND_DELAY)
                    ->where($paymentCreatedAt, '<', $minCreatedAt)
                    ->where(Entity::DISPUTED, '=', 0)
                    ->get();
    }

    /**
     * This function will fetch all the payments using the refund_at
     * column and the timestamp provided.
     * @param int $timestamp*
     * @return Base\PublicCollection
     */
    public function getAuthorizedPaymentsToBeRefundedUsingRefundAt(
        int $timestamp, $limit
    ): Base\PublicCollection
    {
        $refundAt = $this->repo->payment->dbColumn(Payment\Entity::REFUND_AT);
        $status = $this->repo->payment->dbColumn(Payment\Entity::STATUS);

        $results = $this->repo->useSlave(function () use ($refundAt, $status, $timestamp, $limit)
        {
            $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);

            $query = $this->newQueryWithConnection($connectionType);

            return $query
                        ->from(\DB::raw('`payments` FORCE INDEX (payments_status_index)'))
                        ->select($this->dbColumn('*'))
                        ->where($refundAt, '<=', $timestamp)
                        ->where($status, Payment\Status::AUTHORIZED)
                        ->orderBy($refundAt, 'DESC')
                        ->limit($limit)
                        ->get();
        });

        return $results;
    }

    public function getAuthorizedPaymentsBetweenTimestamps($timeLowerLimit, $timeUpperLimit)
    {
        // Stop capture reminder emails being sent to B2B export merchants
        // for intl_bank_transfer payments.
        // Slack: https://razorpay.slack.com/archives/C024U3B04LD/p1685525446278749?thread_ts=1685432424.957589&cid=C024U3B04LD
        $excludeMethods = [
            Entity::INTL_BANK_TRANSFER,
        ];

        $query = $this->newQuery();

        return $query->status(Payment\Status::AUTHORIZED)
                     ->where(Payment\Entity::CREATED_AT, '<=', $timeUpperLimit)
                     ->where(Payment\Entity::CREATED_AT, '>', $timeLowerLimit)
                     ->whereNotIn(Entity::METHOD, $excludeMethods)
                     ->get();
    }


    /**
     * Fetch the payments that are authorized, refund_at time has not been crossed yet,
     * are within lower & upper time limits whose order's auto capture is set and
     * merchant wants to capture late_auth payments
     *
     * @todo : We have hardcoded goibibo mid for now. We need to fix this.
     *
     * @param $from     int     Lower limit to capture payments
     * @param $to       int     Upper limit to capture payments
     *
     * @return mixed
     */
    public function getAuthorizedAutoCapturePaymentsBetweenTimestamps(int $from, int $to)
    {
        $paymentRepo = $this->repo->payment;

        $merchantRepo = $this->repo->merchant;

        $orderRepo = $this->repo->order;

        $paymentMerchantId = $paymentRepo->dbColumn(Payment\Entity::MERCHANT_ID);

        $paymentOrderId = $paymentRepo->dbColumn(Payment\Entity::ORDER_ID);

        $merchantId = $merchantRepo->dbColumn(Merchant\Entity::ID);

        $orderId    = $orderRepo->dbColumn(Order\Entity::ID);

        $paymentAuthorizedAt = $paymentRepo->dbColumn(Payment\Entity::AUTHORIZED_AT);

        $paymentStatus = $paymentRepo->dbColumn(Payment\Entity::STATUS);

        $paymentRefundAt = $paymentRepo->dbColumn(Payment\Entity::REFUND_AT);

        $paymentMerchantId = $paymentRepo->dbColumn(Payment\Entity::MERCHANT_ID);

        $currentTime = Carbon::now()->getTimestamp();

        $merchantAutoCaptureLateAuth = $merchantRepo->dbColumn(Merchant\Entity::AUTO_CAPTURE_LATE_AUTH);

        $orderPaymentCaptureFlag = $orderRepo->dbColumn(Order\Entity::PAYMENT_CAPTURE);

        $query = $this->newQuery()
                        ->select($this->dbColumn('*'))
                        ->join(Table::MERCHANT, $paymentMerchantId, '=', $merchantId)
                        ->join(Table::ORDER, $paymentOrderId, '=', $orderId)
                        ->whereBetween($paymentAuthorizedAt, [$from, $to])
                        ->where($paymentStatus, '=', Payment\Status::AUTHORIZED)
                        ->where($paymentRefundAt, '>', $currentTime)
                        ->where($merchantAutoCaptureLateAuth, '=', true)
                        ->where($orderPaymentCaptureFlag, '=', true)
                        // '10000000000000', '1cXSLlUU8V9sXl' are test cases MID
                        ->whereIn($paymentMerchantId, ['6ZLE5BE57SExGF', '10000000000000', '1cXSLlUU8V9sXl'])
                        ->get();

        return $query;
    }

    public function getAutoCapturedPaymentsBetweenTimestamps($timeLowerLimit, $timeUpperLimit)
    {
        $query = $this->newQuery();

        return $query->status(Payment\Status::CAPTURED)
                     ->where(Payment\Entity::AUTO_CAPTURED, '=', true)
                     ->where(Payment\Entity::CAPTURED_AT, '<=', $timeUpperLimit)
                     ->where(Payment\Entity::CAPTURED_AT, '>', $timeLowerLimit)
                     ->orderBy(Payment\Entity::MERCHANT_ID, 'desc')
                     ->orderBy(Payment\Entity::ID, 'desc')
                     ->get();
    }

    public function getPaymentsToVerifyByGatewayAndTime(array $timestamps, $gateway, $count, $disabledGateways,
                                                        $bucket, array $filterStatus = [], $filterPaymentPushedToKafka = false)
    {
        $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);

        $query = $this->newQueryWithConnection($connectionType);

        $query = $query
                      ->whereBetween(Payment\Entity::VERIFY_AT, $timestamps);

        if ($gateway !== null)
        {
            $query->where(Payment\Entity::GATEWAY, '=', $gateway);
        }
        else
        {
            $query->whereNotIn(Payment\Entity::GATEWAY, $disabledGateways);
        }

        if ($bucket !== null)
        {
            $query->whereIn(Payment\Entity::VERIFY_BUCKET, $bucket);
        }

        if (isset($filterStatus) === true)
        {
            $query->whereIn(Payment\Entity::STATUS, $filterStatus);
        }

        if ($filterPaymentPushedToKafka == true)
        {
            $query->where(function ($query)
            {
                $query->where(Payment\Entity::IS_PUSHED_TO_KAFKA, '=', Payment\Processor\Constants::TIMEOUT_VIA_SCHEDULER)
                    ->orWhereNull(Payment\Entity::IS_PUSHED_TO_KAFKA);
            });
        }

        return $query->take($count)
                     ->orderBy(Payment\Entity::VERIFY_AT, 'desc')
                     ->get();
    }

    /**
     * Return Payments object(s) which should be verified
     *
     * @param array        $minMaxArray      Min/Max array
     * @param array|string $verifyBoundary   Array of [VERIFY_BUCKET and timestamp] values
     * @param string       $verifyStatus     Value for filter of VerifyStatus
     * @param string       $paymentStatus    Value for filter of paymentStatus
     * @param int          $rowsToFetch      Rows to fetch
     * @param array        $disabledGateways Gateways for which verify should be skipped
     * @param bool         $random           Db should take param in random value or not
     *
     * @return array
     */
    public function getPaymentsToVerify(
                        array $minMaxArray,
                        array $verifyBoundary,
                        $verifyStatus = null,
                        $paymentStatus = null,
                        int $rowsToFetch = 100,
                        string $gateway = null,
                        array $disabledGateways = [],
                        bool $random = true)
    {
        $query = $this->newQuery();

        if ($gateway === null)
        {
            $query->whereNotNull(Payment\Entity::GATEWAY)
                  ->whereNotIn(Payment\Entity::GATEWAY, $disabledGateways);
        }
        else
        {
            $query->where(Payment\Entity::GATEWAY, '=', $gateway);
        }

        if ($verifyStatus !== null)
        {
            $query->where(Payment\Entity::VERIFIED, '=', $verifyStatus);
        }

        if ($paymentStatus !== null)
        {
            $query->status($paymentStatus);
        }

        if ($random === true)
        {
            $query->inRandomOrder();
        }

        // For verify Error, we only look at the payment status.
        if ($verifyStatus !== Verify\Status::ERROR)
        {
            $this->addWhereConditionsUsingVerifyBoundary($minMaxArray, $verifyBoundary, $query);
        }
        else
        {
            $this->addWhereConditionsUsingMinimumTime($minMaxArray, $query);
        }

        // Sample Query
        // SELECT *
        // FROM   `payments`
        // WHERE  `gateway` NOT IN ( 'wallet_openwallet' )
        //        AND `status` = 'failed'
        //        AND ( ( `verify_bucket` = '0' AND `created_at` < '1478023148' )
        //              OR ( `verify_bucket` = '1' AND `created_at` < '1478022368' )
        //              OR ( `verify_bucket` = '2' AND `created_at` < '1478019668' )
        //              OR ( `verify_bucket` = '3' AND `created_at` < '1477936868' )
        //              OR ( `verify_bucket` = '4' AND `created_at` < '1477850468' )
        //              OR ( `verify_bucket` = '5' AND `created_at` < '1477764068' )
        //              OR ( `verify_bucket` = '6' AND `created_at` < '1477677668' )
        //              OR ( `verify_bucket` = '7' AND `created_at` < '1477591268' )
        //              OR ( `verify_bucket` = '8' AND `created_at` < '1477504868' )
        //              OR ( `verify_bucket` = '9' AND `created_at` < '1477418468' )
        //            )
        // ORDER  BY Rand()
        // LIMIT  100

        // We want total number of Payments which are awaiting verify, for logging
        $verifiableCount = $query->count();

        $payments = $query->take($rowsToFetch)
                          ->with('merchant')
                          ->get();

        return ['payments' => $payments, 'verifiable_count' => $verifiableCount];
    }

    public function getPaymentsToVerifyByIds(
        array $paymentIds,
        int $rowsToFetch = 100,
        array $disabledGateways = [])
    {
        $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);

        $query = $this->newQueryWithConnection($connectionType);

        $query = $query
                      ->whereIn(Payment\Entity::ID, $paymentIds)
                      ->whereNotIn(Payment\Entity::GATEWAY, $disabledGateways);

        $verifiableCount = $query->count();

        $payments = $query->take($rowsToFetch)
                          ->with('merchant')
                          ->get();

        return ['payments' => $payments, 'verifiable_count' => $verifiableCount];
    }


    public function getPaymentsForCreatingCustomerVpaTokens($limit, $startTime = null)
    {
        if (isset($startTime) === false)
        {
            $startTime = 1575384036;
        }

        $query = $this->newQuery()
              ->whereNotNull(Payment\Entity::GLOBAL_CUSTOMER_ID)
              ->where(Payment\Entity::CREATED_AT, '>=', $startTime)
              ->where(Payment\Entity::METHOD, Payment\Method::UPI)
              ->whereNotNull(Payment\Entity::AUTHORIZED_AT)
              ->whereNotNull(Payment\Entity::VPA)
              ->whereNull(Payment\Entity::GLOBAL_TOKEN_ID)
              ->orderBy(Payment\Entity::CREATED_AT)
              ->limit($limit);

        return $query->get();
    }

    /**
     * Add Where Condition for Created Payments, And Verify Failed Payments
     *
     * @param array     $minMaxArray Min Max array to filter payments created $ts sec before and $tx time after
     * @param BuilderEx $query       original query
     *
     * @return void
     */
    protected function addWhereConditionsUsingMinimumTime(array $minMaxArray, BuilderEx $query)
    {
        $currentTime = Carbon::now()->getTimestamp();

        $query->where(Payment\Entity::CREATED_AT, '<=', $currentTime - $minMaxArray['min']);
    }

    protected function addWhereClauseForMinAndMaxTime(array $minMaxArray, array & $whereConditions)
    {
        $currentTime = Carbon::now()->getTimestamp();

        if ($minMaxArray['max'] !== null)
        {
            $whereConditions[] = [
                [Payment\Entity::CREATED_AT, '<=', $currentTime - $minMaxArray['min']],
                [Payment\Entity::CREATED_AT, '>=', $currentTime - $minMaxArray['max']]
            ];
        }
    }

    /**
     * Process min_time and verify_boundary array and return where and orWhere Condition
     *
     * @param array     $minMaxArray      Min Max array to filter payments created $ts sec before and $tx time after
     * @param array     $verifyBoundaries array with Key as bucket and value as time for that bucket
     * @param BuilderEx $query            original query
     *
     * @return void
     */
    protected function addWhereConditionsUsingVerifyBoundary(
                                                    array $minMaxArray,
                                                    array $verifyBoundaries,
                                                    BuilderEx $query)
    {
        $currentTime = Carbon::now()->getTimestamp();

        $whereConditions = [];

        $this->addWhereClauseForMinAndMaxTime($minMaxArray, $whereConditions);

        // Each or condition will fetch payments which are
        // in next Verify Bucket and not processed by previous cron
        // This will not give all payments at once, but only payments which
        // crossed the boundary after prev cron ran (SLIDING WINDOW PROTOCOL)
        foreach ($verifyBoundaries as $bucket => $time)
        {
            // This gets all the payments in the last `boundary (15, 60, etc)` time.
            // $boundary has time in seconds, signifying payment should be X second old
            // For querying on db, need to change that to absolute value
            $paymentCreatedAfter = $currentTime - $time;

            $whereConditions[] = [
                [Payment\Entity::VERIFY_BUCKET, '=', ($bucket + 1)],
                [Payment\Entity::CREATED_AT, '<', $paymentCreatedAfter]
            ];
        }

        // Now add the conditions to the payment verify query.
        $query->where(
            function ($query) use ($whereConditions)
            {
                foreach($whereConditions as $condition)
                {
                    $query->orWhere($condition);
                }
            });
    }

    public function fetchEntitiesForReport($merchantId, $from, $to, $count, $skip, $relations = [])
    {
        return $this->fetchBetweenTimestampWithRelations(
                        $merchantId, $from, $to, $count, $skip, $relations);
    }


    /*
     * select `payments`.*
     * from `payments`, `transactions` USE INDEX (transactions_reconciled_at_index)
     * where `payments`.`id` = `transactions`.`entity_id`
     * and `gateway` = ?
     * and `transactions`.`type` = ?
     * and `transactions`.`reconciled_at` >= ?
     * and `transactions`.`reconciled_at` <= ?
     * and `status` in (?, ?, ?)
     */

    public function fetchReconciledPaymentsForGateway($from, $to, $gateway, $status)
    {
        $paymentAttrs = $this->dbColumn('*');

        $paymentId = $this->dbColumn(Entity::ID);

        $txnRepo = $this->repo->transaction;

        $transactionEntityType = $txnRepo->dbColumn(Transaction\Entity::TYPE);

        $transactionReconciledAt = $txnRepo->dbColumn(Transaction\Entity::RECONCILED_AT);

        // Queries go to admin tidb cluster due to rearch
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
                    ->select($paymentAttrs)
                    ->from(\DB::raw('`payments`, `transactions` USE INDEX (transactions_reconciled_at_index)'))
                    ->where($paymentId, '=', \DB::raw('`transactions`.`entity_id`'))
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->where($transactionEntityType, '=', 'payment')
                    ->where($transactionReconciledAt, '>=', $from)
                    ->where($transactionReconciledAt, '<=', $to)
                    ->whereIn(Entity::STATUS, $status)
                    ->get();
    }

    public function fetchReconciledPaymentsForGatewayWithBankCode($from, $to, $gateway, $bankCode, $status)
    {
        $paymentAttrs = $this->dbColumn('*');

        $paymentId = $this->dbColumn(Entity::ID);

        $txnRepo = $this->repo->transaction;

        $transactionEntityType = $txnRepo->dbColumn(Transaction\Entity::TYPE);

        $transactionReconciledAt = $txnRepo->dbColumn(Transaction\Entity::RECONCILED_AT);

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->select($paymentAttrs)
            ->from(\DB::raw('`payments`, `transactions` USE INDEX (transactions_reconciled_at_index)'))
            ->where($paymentId, '=', \DB::raw('`transactions`.`entity_id`'))
            ->where(Entity::GATEWAY, '=', $gateway)
            ->where(Entity::BANK, '=', $bankCode)
            ->where($transactionEntityType, '=', 'payment')
            ->where($transactionReconciledAt, '>=', $from)
            ->where($transactionReconciledAt, '<=', $to)
            ->whereIn(Entity::STATUS, $status)
            ->get();
    }

    public function fetchReconciledPaymentsForGatewayUsingReportingReplica($from, $to, $gateway, $statuses)
    {
        $paymentAttrs = $this->dbColumn('*');

        $paymentId = $this->dbColumn(Entity::ID);

        $txnRepo = $this->repo->transaction;

        $transactionEntityType = $txnRepo->dbColumn(Transaction\Entity::TYPE);

        $transactionReconciledAt = $txnRepo->dbColumn(Transaction\Entity::RECONCILED_AT);

        return $this->newQueryWithConnection($this->getReportingReplicaConnection())
            ->select($paymentAttrs)
            ->from(\DB::raw('`payments`, `transactions` USE INDEX (transactions_reconciled_at_index)'))
            ->where($paymentId, '=', \DB::raw('`transactions`.`entity_id`'))
            ->where(Entity::GATEWAY, '=', $gateway)
            ->where($transactionEntityType, '=', 'payment')
            ->where($transactionReconciledAt, '>=', $from)
            ->where($transactionReconciledAt, '<=', $to)
            ->whereIn(Entity::STATUS, $statuses)
            ->get();
    }

    public function fetchCorporatePaymentsWithStatusAndRelations(
        int $from,
        int $to,
        string $gateway,
        string $bankCode,
        $relations = [])
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $paymentAttrs = $this->dbColumn('*');

        $terminalRepo = $this->repo->terminal;

        $tTablename = $terminalRepo->getTableName();

        $pGateway = $this->dbColumn(Entity::GATEWAY);

        $pTerminalId = $this->dbColumn(Entity::TERMINAL_ID);

        $pBankCode = $this->dbColumn(Entity::BANK);

        $tId = $terminalRepo->dbColumn(Terminal\Entity::ID);

        $pAuthorizedAt = $this->dbColumn(Entity::AUTHORIZED_AT);

        //use TiDB merchant
        $connectionType = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN);

        $query = $this->newQueryWithConnection($connectionType);

        return $query
                ->select($paymentAttrs)
                ->join($tTablename, $pTerminalId, '=', $tId)
                ->where($pAuthorizedAt, '>=', $from)
                ->where($pAuthorizedAt, '<=', $to)
                ->where($pGateway, $gateway)
                ->whereNotNull($pAuthorizedAt)
                ->where($pBankCode, $bankCode)
                ->with($relations)
                ->get();
    }

    public function fetchReconciledPaymentsForTpv($from, $to, $gateway, $status, $tpvEnabled = false, $relations = [])
    {
        // SELECT `payments`.*
        // FROM `payments`
        // INNER JOIN `transactions` ON `payments`.`id` = `transactions`.`entity_id`
        // INNER JOIN `terminals` ON `payments`.`terminal_id` = `terminals`.`id`
        // WHERE `payments`.`gateway` = $gateway
        //   AND `transactions`.`type` = 'payment'
        //   AND `transactions`.`reconciled_at` BETWEEN $from AND $to
        //   AND `payments`.`status` IN ( $status ) // status is an array
        //   AND `terminals`.`tpv` = $tpvEnabled
        //   AND `terminals`.`corporate` = 0 // corporate payments have separate gateway file generation

        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $paymentAttrs = $this->dbColumn('*');

        $paymentId = $this->dbColumn(Entity::ID);
        $paymentTerminalId = $this->dbColumn(Entity::TERMINAL_ID);
        $paymentGateway = $this->dbColumn(Entity::GATEWAY);
        $paymentStatus = $this->dbColumn(Entity::STATUS);

        $txnRepo = $this->repo->transaction;

        $tRepo = $this->repo->terminal;

        $transactionPaymentId = $txnRepo->dbColumn(Transaction\Entity::ENTITY_ID);
        $transactionEntityType = $txnRepo->dbColumn(Transaction\Entity::TYPE);
        $transactionReconciledAt = $txnRepo->dbColumn(Transaction\Entity::RECONCILED_AT);

        $terminalId = $tRepo->dbColumn(Terminal\Entity::ID);
        $terminalTpv = $tRepo->dbColumn(Terminal\Entity::TPV);
        $terminalCorporate = $tRepo->dbColumn(Terminal\Entity::CORPORATE);

        // Queries go to admin tidb cluster due to rearch
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
                    ->select($paymentAttrs)
                    ->join($txnRepo->getTableName(), $paymentId, '=', $transactionPaymentId)
                    ->join($tRepo->getTableName(), $paymentTerminalId, '=', $terminalId)
                    ->where($paymentGateway, '=', $gateway)
                    ->where($transactionEntityType, '=', 'payment')
                    ->whereBetween($transactionReconciledAt, [$from, $to])
                    ->whereIn($paymentStatus, $status)
                    ->where($terminalCorporate, '=', 0)
                    ->where($terminalTpv, '=', $tpvEnabled)
                    ->with($relations)
                    ->get();
    }

    public function fetchPaymentsForOrderId($orderId)
    {
        $payments = $this->newQuery()
                         ->where(Payment\Entity::ORDER_ID, '=', $orderId)
                         ->get();

        if (strlen($orderId) === UniqueIdEntity::ID_LENGTH)
        {
            $idGeneratedTimestamp = UniqueIdEntity::uidToTimestamp($orderId);

            $currentTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

            // If orderId is created < 7 days from current time, just returning data from hot storage
            // As all payments created for the order will be present in the hot storage
            if ($currentTimestamp - $idGeneratedTimestamp < 604800)
            {
                return $payments;
            }
        }

        $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);

        $warmPayments = $this->newQueryWithConnection($connectionType)
                             ->where(Payment\Entity::ORDER_ID, '=', $orderId)
                             ->get();

        $allPayments = $this->mergeCollectionsBasedOnKey($payments, $warmPayments, Entity::ID);

        return $allPayments;
    }

    public function fetchPaymentsWithCardForOrderId($orderId)
    {
        $payments = $this->newQuery()
                         ->where(Payment\Entity::ORDER_ID, '=', $orderId)
                         ->with(["card"])
                         ->get();

        if (strlen($orderId) === UniqueIdEntity::ID_LENGTH)
        {
            $idGeneratedTimestamp = UniqueIdEntity::uidToTimestamp($orderId);

            $currentTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

            // If orderId is created < 7 days from current time, just returning data from hot storage
            // As all payments created for the order will be present in the hot storage
            if ($currentTimestamp - $idGeneratedTimestamp < 604800)
            {
                return $payments;
            }
        }

        $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);

        $warmPayments = $this->newQueryWithConnection($connectionType)
                             ->where(Payment\Entity::ORDER_ID, '=', $orderId)
                             ->with(["card"])
                             ->get();

        return $this->mergeCollectionsBasedOnKey($payments, $warmPayments, Entity::ID);
    }

    public function fetchCapturedRearchPaymentsTxnNull()
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->where(Payment\Entity::CPS_ROUTE, '=', 5)
            ->where(Payment\Entity::STATUS, '=', 'captured')
            ->whereNull(Payment\Entity::TRANSACTION_ID)
            ->limit(100)
            ->get();
    }

    public function fetchCapturedRearchPaymentsTxnNullForMerchant($merchantId)
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->where(Payment\Entity::CPS_ROUTE, '=', 5)
            ->where(Payment\Entity::STATUS, '=', 'captured')
            ->where(Payment\Entity::MERCHANT_ID, '=', $merchantId)
            ->whereNull(Payment\Entity::TRANSACTION_ID)
            ->limit(100)
            ->get();
    }

    public function fetchFirstAuthorizedPaymentsForOrderReceiptOfMerchants(string $receipt, array $merchantIds)
    {
        /**
         * SELECT `payments`.`*`
         * FROM `payments`
         * INNER JOIN `orders` ON `payments`.`order_id` = `orders`.`id`
         * WHERE `payments`.`authorized_at` IS NOT NULL
         * AND `orders`.`receipt` = ?
         * AND `orders`.`authorized` = ?
         * AND `orders`.`merchant_id` IN (?)
         */

        $ordersTable = $this->repo->order->getTableName();

        $paymentCols = $this->dbColumn('*');
        $paymentOrderIdCol = $this->dbColumn(Entity::ORDER_ID);
        $paymentAuthorizedAtCol = $this->dbColumn(Entity::AUTHORIZED_AT);

        $orderIdCol = $this->repo->order->dbColumn(Order\Entity::ID);
        $orderMerchantIdCol = $this->repo->order->dbColumn(Order\Entity::MERCHANT_ID);
        $orderAuthorizedCol = $this->repo->order->dbColumn(Order\Entity::AUTHORIZED);
        $orderReceiptCol = $this->repo->order->dbColumn(Order\Entity::RECEIPT);

        $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);

        $query = $this->newQueryWithConnection($connectionType);

        return $query
                    ->select($paymentCols)
                    ->join($ordersTable, $paymentOrderIdCol, '=', $orderIdCol)
                    ->whereNotNull($paymentAuthorizedAtCol)
                    ->where($orderReceiptCol, $receipt)
                    ->where($orderAuthorizedCol, true)
                    ->whereIn($orderMerchantIdCol, $merchantIds)
                    ->first();
    }

    /**
     * Fetches all payments for on hold flag update with on_hold_until
     * timestamp earlier than timestamp parameter.
     *
     * @param int $timestamp
     * @return Base\PublicCollection
     */
    public function getPaymentsOnHoldBeforeTimestamp(int $timestamp) : Base\PublicCollection
    {
        $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);

        $query = $this->newQueryWithConnection($connectionType);

        $data = $query
                     ->where(Payment\Entity::ON_HOLD, true)
                     ->where(Payment\Entity::ON_HOLD_UNTIL, '<', $timestamp)
                     ->with('transfer')
                     ->limit(500)
                     ->get();

        return $data;
    }

    protected function addQueryParamBank($query, $params)
    {
        if (Payment\Processor\Netbanking::isSupportedBank($params['bank']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_INVALID_BANK_CODE,
                Entity::BANK);
        }

        $query = $query->where(Entity::BANK, '=', $params[Entity::BANK]);
    }

    protected function addWDAQueryParamBank($wdaQueryBuilder, $params)
    {
        if (Payment\Processor\Netbanking::isSupportedBank($params['bank']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_INVALID_BANK_CODE,
                Entity::BANK);
        }

        $wdaQueryBuilder->filters($this->getTableName(), Entity::BANK, [$params[Entity::BANK]], Symbol::EQ);
    }

    protected function addQueryParamStatus($query, $params)
    {
        $status = $params[Entity::STATUS];

        $statusColumn = $this->dbColumn(Entity::STATUS);

        $status = explode(',', $status);

        Payment\Validator::validateStatusArray($status);

        $query->whereIn($statusColumn, $status);
    }

    protected function addWDAQueryParamStatus($wdaQueryBuilder, $params)
    {
        $status = $params[Entity::STATUS];

        $status = explode(',', $status);

        Payment\Validator::validateStatusArray($status);

        $wdaQueryBuilder->filters($this->getTableName(), Entity::STATUS, $status, Symbol::IN);
    }

    protected function addQueryParamAmount($query, $params)
    {
        $amount = $this->dbColumn(Entity::AMOUNT);

        $query->where($amount, '=', $params[Entity::AMOUNT]);
    }

    protected function addWDAQueryParamAmount($wdaQueryBuilder, $params)
    {
        $wdaQueryBuilder->filters($this->getTableName(), Entity::AMOUNT, [$params[Entity::AMOUNT]], Symbol::EQ);
    }

    protected function addQueryParamIin($query, $params)
    {
        //
        // This needs to be empty as we are doing a special join for
        // card attributes defined in buildCardJoinQuery
        //
        return;
    }

    protected function addWDAQueryParamIin($wdaQuery, $params)
    {
        //
        // This needs to be empty as we are doing a special join for
        // card attributes defined in buildWDACardJoinQuery
        //
        return;
    }

    protected function addQueryParamLast4($query, $params)
    {
        //
        // This needs to be empty as we are doing a special join for
        // card attributes defined in buildCardJoinQuery
        //
        return;
    }

    protected function addWDAQueryParamLast4($query, $params)
    {
        //
        // This needs to be empty as we are doing a special join for
        // card attributes defined in buildWDACardJoinQuery
        //
        return;
    }

    protected function addQueryParamRecurringStatus($query, $params)
    {
        $this->joinQueryToken($query);

        $query->where(Token\Entity::RECURRING_STATUS, '=', $params[Token\Entity::RECURRING_STATUS]);

        $query->select($this->getTableName() . '.*');
    }

    protected function addWDAQueryParamRecurringStatus($wdaQueryBuilder, $params)
    {
        $this->joinWDAQueryToken($wdaQueryBuilder);

        $tokenTable = Table::getTableNameForEntity(Constants\Entity::TOKEN);

        $value = $params[Token\Entity::RECURRING_STATUS];

        if(!is_array($value))
        {
            $value = [$value];
        }

        $wdaQueryBuilder->filters($tokenTable, Token\Entity::RECURRING_STATUS, $value, Symbol::EQ);
    }

    protected function addQueryParamGatewayTerminalId($query, $params)
    {
        $this->joinQueryTerminal($query);

        $query->where(Terminal\Entity::GATEWAY_TERMINAL_ID, $params[Terminal\Entity::GATEWAY_TERMINAL_ID]);

        $query->select($this->getTableName() . '.*');
    }

    protected function addWDAQueryParamGatewayTerminalId($wdaQueryBuilder, $params)
    {
        $this->joinWDAQueryTerminal($wdaQueryBuilder);

        $terminalTable = Table::getTableNameForEntity(Constants\Entity::TERMINAL);

        $wdaQueryBuilder->filters($terminalTable, Terminal\Entity::GATEWAY_TERMINAL_ID, [$params[Terminal\Entity::GATEWAY_TERMINAL_ID]], Symbol::EQ);
    }

    /**
     * Param to filter payments that have been transferred (amount_transferred > 0)
     *
     * @param $query
     * @param $params
     */
    protected function addQueryParamTransferred($query, $params)
    {
        if ($params[Entity::TRANSFERRED] !== '1')
        {
            return;
        }

        $amountTransferred = $this->dbColumn(Entity::AMOUNT_TRANSFERRED);

        $query->where($amountTransferred, '>', 0);
    }

    protected function addWDAQueryParamTransferred($wdaQueryBuilder, $params)
    {
        if ($params[Entity::TRANSFERRED] !== '1')
        {
            return;
        }

        $wdaQueryBuilder->filters($this->getTableName(), Entity::AMOUNT_TRANSFERRED, [0], Symbol::GT);
    }

    protected function addQueryParamCaptured($query, $params)
    {
        if (boolval($params[Entity::CAPTURED]) === false)
        {
            $query->whereNull(Entity::CAPTURED_AT);
        }
        else
        {
            $query->whereNotNull(Entity::CAPTURED_AT);
        }
    }

    protected function addWDAQueryParamCaptured($wdaQueryBuilder, $params)
    {
        if (boolval($params[Entity::CAPTURED]) === false)
        {
            $wdaQueryBuilder->filters($this->getTableName(), Entity::CAPTURED_AT);
        }
        else
        {
            $wdaQueryBuilder->filters($this->getTableName(), Entity::CAPTURED_AT, [], Symbol::NOT_NULL);
        }
    }

    protected function addQueryParamEmail($query, $params)
    {
        $merchant = $this->auth->getMerchant();

        if (($this->auth->isPrivateAuth() === true) and
            ($this->auth->isProxyAuth() === false) and
            ($merchant->isFeatureEnabled(Feature\Constants::PAYMENT_EMAIL_FETCH) === false))
        {
            throw new Exception\ExtraFieldsException('email');
        }

        return parent::addQueryParamEmail($query, $params);
    }

    protected function addWDAQueryParamEmail($wdaQueryBuilder, $params)
    {
        $merchant = $this->auth->getMerchant();

        if (($this->auth->isPrivateAuth() === true) and
            ($this->auth->isProxyAuth() === false) and
            ($merchant->isFeatureEnabled(Feature\Constants::PAYMENT_EMAIL_FETCH) === false))
        {
            throw new Exception\ExtraFieldsException('email');
        }

        return parent::addWDAQueryParamEmail($wdaQueryBuilder, $params);
    }

    protected function joinQueryToken($query)
    {
        $joins = $query->getQuery()->joins;

        $joins = $joins ?: [];

        $tokenTable = Table::getTableNameForEntity(Constants\Entity::TOKEN);

        foreach ($joins as $join)
        {
            if ($join->table === $tokenTable)
            {
                return;
            }
        }

        $paymentTokenId = $this->dbColumn(Payment\Entity::TOKEN_ID);
        $tokenId = $this->repo->token->dbColumn(Token\Entity::ID);

        $query->join($tokenTable, $paymentTokenId, '=', $tokenId);
    }

    protected function joinWDAQueryToken($wdaQueryBuilder)
    {
        $joins = $wdaQueryBuilder->getJoinTables();

        $tokenTable = Table::getTableNameForEntity(Constants\Entity::TOKEN);

        foreach ($joins as $join)
        {
            if ($join === $tokenTable)
            {
                return;
            }
        }

        $paymentTokenId = $this->dbColumn(Payment\Entity::TOKEN_ID);

        $tokenId = $this->repo->token->dbColumn(Token\Entity::ID);

        $joinOperation = $paymentTokenId.' = '.$tokenId ;

        $wdaQueryBuilder->addResource($tokenTable, "INNER", $joinOperation);

    }

    protected function buildFetchQueryAdditional($params, $query)
    {
        if ((isset($params[Card\Entity::IIN]) === true) or
            (isset($params[Card\Entity::LAST4]) === true))
        {
            $this->buildCardJoinQuery($params, $query);
        }

        $query->select($this->getTableName() . '.*');
    }

    protected function buildWDAFetchQueryAdditional($params, $wdaQueryBuilder)
    {
        if ((isset($params[Card\Entity::IIN]) === true) or
            (isset($params[Card\Entity::LAST4]) === true))
        {
            $this->buildWDACardJoinQuery($params, $wdaQueryBuilder);
        }
    }

    /**
     * When card related attributes are present, we want do inner join on cards.
     * The method generates a query like
     * SELECT * FROM `payments` INNER JOIN `cards` ON `payments`.`card_id` = `cards`.`id` WHERE `cards`.`iin` = ? AND `cards`.`last4` = ? ORDER BY `payments`.`created_at` DESC, `payments`.`id` DESC LIMIT 1000;
     * @param  array $params
     * @param  \Illuminate\Database\Query\Builder $query
     */
    protected function buildCardJoinQuery($params, $query)
    {
        $cardTableName       = $this->repo->card->getTableName();
        $paymentCardIdColumn = $this->dbColumn(Entity::CARD_ID);
        $cardIdColumn        = $this->repo->card->dbColumn(Entity::ID);

        $cardQueryParams = array_only($params, $this->cardQueryKeys);

        $joinQuery = $query->join($cardTableName, $paymentCardIdColumn, '=', $cardIdColumn);

        foreach ($cardQueryParams as $key => $value)
        {
           $joinQuery->where($cardTableName . '.' . $key, $value);
        }
    }

    protected function buildWDACardJoinQuery($params, $wdaQueryBuilder)
    {
        $cardTableName       = $this->repo->card->getTableName();
        $paymentCardIdColumn = $this->dbColumn(Entity::CARD_ID);
        $cardIdColumn        = $this->repo->card->dbColumn(Entity::ID);

        $cardQueryParams = array_only($params, $this->cardQueryKeys);

        $wdaQueryBuilder->addResource($cardTableName, "INNER", $paymentCardIdColumn.' = '.$cardIdColumn);

        foreach ($cardQueryParams as $key => $value)
        {
            $wdaQueryBuilder->filters($cardTableName, $key, [$value], Symbol::EQ);
        }
    }

    public function getCreatedAndFailedPaymentsForOrder($orderId)
    {
        $ts = time() - Payment\Entity::PAYMENT_WINDOW;

        return $this->newQuery()
                    ->whereIn(Entity::STATUS, [Status::CREATED, Status::FAILED])
                    ->where(Payment\Entity::ORDER_ID, '=', $orderId)
                    ->where(Payment\Entity::CREATED_AT, '>', $ts)
                    ->get();
    }

    public function getCapturedPaymentForOrder(string $orderId)
    {
        $payment = $this->newQuery()
                        ->whereNotNull(Entity::CAPTURED_AT)
                        ->where(Entity::ORDER_ID, '=', $orderId)
                        ->first();

        if (empty($payment) === false)
        {
            return $payment;
        }

        $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);

        $payment = $this->newQueryWithConnection($connectionType)
                        ->whereNotNull(Entity::CAPTURED_AT)
                        ->where(Entity::ORDER_ID, '=', $orderId)
                        ->first();

        return $payment;
    }

    public function getTopMerchantVolumeWiseBetweenTimestamp(int $from, int $to, int $limit)
    {
        try
        {
            if($this->app['api.route']->isWDAServiceRoute() and
                ($this->isExperimentEnabled($this->app['api.route']->getWdaRouteExperimentName()) === true))
            {
                return $this->getTopMerchantVolumeWiseBetweenTimestampFromWDA($from, $to, $limit);
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->error(TraceCode::WDA_MIGRATION_ERROR, [
                'wda_migration_error' => $ex->getMessage(),
                'route_name'          => $this->app['api.route']->getCurrentRouteName(),
            ]);
        }

        $pid = $this->dbColumn(Payment\Entity::MERCHANT_ID);
        $mid = $this->repo->merchant->dbColumn(Merchant\Entity::ID);

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
                    ->join($this->repo->merchant->getTableName(), $pid, '=', $mid)
                    ->selectRaw(
                       Payment\Entity::MERCHANT_ID . ','.
                       Merchant\Entity::NAME . ','.
                       Merchant\Entity::WEBSITE . ','.
                       'SUM(amount) / 100 AS volume' . ','.
                       'COUNT(*) AS count')
                    ->betweenTime($from, $to)
                    ->statusSuccess()
                    ->groupBy(
                        Payment\Entity::MERCHANT_ID,
                        Merchant\Entity::NAME,
                        Merchant\Entity::WEBSITE)
                    ->orderBy('volume', 'desc')
                    ->limit($limit)
                    ->get();
    }

    public function getTopMerchantVolumeWiseBetweenTimestampFromWDA(int $from, int $to, int $limit)
    {
        $this->trace->info(TraceCode::WDA_SERVICE_REQUEST, [
            'function'     => __FUNCTION__,
            'input_params' => ['from' => $from, 'to' => $to, 'limit' => $limit],
            'route_name'   => $this->app['api.route']->getCurrentRouteName(),
        ]);

        $startTimeMs = round(microtime(true) * 1000);

        $wdaClient = $this->app['wda-client']->wdaClient;

        $wdaQueryBuilder = new WDAQueryBuilder();

        $merchantTableName = Table::getTableNameForEntity(Constants\Entity::MERCHANT);

        $pid = $this->dbColumn(Payment\Entity::MERCHANT_ID);

        $mid = $this->repo->merchant->dbColumn(Merchant\Entity::ID);

        $wdaQueryBuilder->addQuery($this->getTableName(), Payment\Entity::MERCHANT_ID)
                        ->addQuery($merchantTableName, Merchant\Entity::NAME)
                        ->addQuery($merchantTableName, Merchant\Entity::WEBSITE)
                        ->addQuery($this->getTableName(), Payment\Entity::AMOUNT, 'SUM', 'volume', '/', '100')
                        ->addQuery($this->getTableName(), '*', 'COUNT', 'count');

        $wdaQueryBuilder->resources($this->getTableName(), $merchantTableName, "inner", $pid.' = '.$mid);

        $wdaQueryBuilder->filters($this->getTableName(), Entity::CREATED_AT, [$from], Symbol::GTE)
                        ->filters($this->getTableName(), Entity::CREATED_AT, [$to], Symbol::LTE)
                        ->filters($this->getTableName(), Entity::STATUS, [Status::FAILED, Status::CREATED], Symbol::NOT_IN);

        $wdaQueryBuilder->group($this->getTableName(), Payment\Entity::MERCHANT_ID, SortOrder::DESC)
                        ->group($merchantTableName, Merchant\Entity::NAME, SortOrder::DESC)
                        ->group($merchantTableName, Merchant\Entity::WEBSITE, SortOrder::DESC);

        $wdaQueryBuilder->sort($this->getTableName(), 'volume', SortOrder::DESC);

        $wdaQueryBuilder->size($limit);

        $wdaQueryBuilder->namespace($this->getEntityObject()->getConnection()->getDatabaseName());

        $wdaQueryBuilder->cluster(WDAService::ADMIN_CLUSTER);

        $this->trace->info(TraceCode::WDA_SERVICE_QUERY, [
            'wda_query_builder' => $wdaQueryBuilder->build()->serializeToJsonString(),
            'route_name'    => $this->app['api.route']->getCurrentRouteName(),
        ]);

        $responseArray = $wdaClient->fetchEntities($wdaQueryBuilder->build(), $this->newQuery()->getModel());

        $collection = new PublicCollection();

        foreach ($responseArray as $arr)
        {
            $collection->push($arr);
        }

        $endTimeMs = round(microtime(true) * 1000);

        $queryDuration = $endTimeMs - $startTimeMs;

        $this->trace->info(TraceCode::WDA_SERVICE_RESPONSE, [
            'method_name'   => __FUNCTION__,
            'duration_ms'    => $queryDuration,
            'route_name'    => $this->app['api.route']->getCurrentRouteName(),
        ]);

        return $collection;
    }

    public function fetchAuthorizedPaymentCountForMerchants(array $merchantIds)
    {
        $dateFormat = '\'%Y-%m-%d\'';

        $minCreatedAt = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);

        $query = $this->newQueryWithConnection($connectionType);

        return $query
                    ->selectRaw(Entity::MERCHANT_ID . ','.
                       'COUNT(*) AS count,' .
                       'DATE_FORMAT(FROM_UNIXTIME(created_at + 19800),' . $dateFormat . ') as dates'
                    )
                    ->where(Entity::STATUS, '=', Status::AUTHORIZED)
                    ->whereIn(Entity::MERCHANT_ID, $merchantIds)
                    ->where(Entity::CREATED_AT, '<', $minCreatedAt)
                    ->groupBy([Entity::MERCHANT_ID, 'dates'])
                    ->get();
    }

    public function fetchAuthorizedSummary()
    {
        $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);

        $query = $this->newQueryWithConnection($connectionType);

        return $query->where(Entity::STATUS, '=', Status::AUTHORIZED)
                     ->groupBy(Entity::MERCHANT_ID)
                     ->selectRaw(Entity::MERCHANT_ID . ','.
                        'SUM(' . Entity::BASE_AMOUNT . ') AS sum' . ','.
                        'COUNT(*) AS count')
                     ->get();
    }

    public function findByTransferIdAndMerchant(string $transferId, string $accountId, array $relations = [])
    {
        try
        {
            return $this->newQuery()
                        ->where(Entity::TRANSFER_ID, $transferId)
                        ->merchantId($accountId)
                        ->with($relations)
                        ->firstOrFailPublic();
        }
        catch (\Throwable $ex)
        {
            $connectionType = $this->getPaymentFetchReplicaConnection();

            return $this->newQueryWithConnection($connectionType)
                        ->where(Entity::TRANSFER_ID, $transferId)
                        ->merchantId($accountId)
                        ->with($relations)
                        ->firstOrFailPublic();
        }
    }

    public function fetchCapturedSummaryBetweenTimestamp($from, $to)
    {
        $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);

        $query = $this->newQueryWithConnection($connectionType);

        return $query->where(Entity::STATUS, '=', Status::CAPTURED)
                     ->whereBetween(Entity::CAPTURED_AT, [$from, $to])
                     ->groupBy(Entity::MERCHANT_ID)
                     ->selectRaw(Entity::MERCHANT_ID . ','.
                        'SUM(' . Entity::BASE_AMOUNT . ') AS sum' . ','.
                        'COUNT(*) AS count')
                     ->get();
    }

    /**
     * SELECT id FROM payments where id in
     * (id1 COLLATE utf8_general_ci, id2 COLLATE
     * utf8_general_ci,...) AND gateway= ?
     * AND created_at >= ?
     */
    public function fetchPaymentIdsbyCapsPaymentIds($capsPaymentIds, $gateway)
    {
        $rawCondition = '`id` in ("';

        foreach ($capsPaymentIds as $capsPaymentId)
        {
            $rawCondition .= $capsPaymentId . '" COLLATE utf8_general_ci,"';
        }

        $rawCondition = rtrim($rawCondition, ',"');
        $rawCondition .= ')';

        $nowMinus5Days = Carbon::today(Timezone::IST)->subDays(5)->getTimestamp();

        $query =  $this->newQueryWithConnection($this->getSlaveConnection())
                       ->whereRaw($rawCondition)
                       ->where(Entity::GATEWAY, $gateway)
                       ->where(Entity::CREATED_AT, '>=', $nowMinus5Days)
                       ->select(Entity::ID)
                       ->get();

        return $query->pluck('id')->toArray();
    }

    /**
     * Fetches the number of times a payment has been made against each offer id in $offerIds
     * grouped by offerId
     *
     * @param  array $cardIds  Card ids to check
     * @param  array $offerIds Offer ids to check
     *
     * @return array Count of payments
     */
    public function getPaymentCountForCardIdsAndOfferIds(array $cardIds, array $offerIds): array
    {
        $entityOfferTable = $this->repo->entity_offer->getTableName();
        $paymentStatusCol = $this->dbColumn(Entity::STATUS);
        $paymentCreatedAtCol = $this->dbColumn(Entity::CREATED_AT);
        $entityOfferEntityIdCol = $this->repo->entity_offer->dbColumn(EntityOffer\Entity::ENTITY_ID);
        $entityOfferEntityTypeCol = $this->repo->entity_offer->dbColumn(EntityOffer\Entity::ENTITY_TYPE);
        $entityOfferOfferIdCol = $this->repo->entity_offer->dbColumn(EntityOffer\Entity::OFFER_ID);
        $paymentCardIdCol = $this->dbColumn(Entity::CARD_ID);
        $paymentIdCol = $this->dbColumn(Entity::ID);

        //
        // SELECT count(payments.id) AS payment_count,
        //        entity_offer.offer_id
        // FROM `payments`
        // INNER JOIN `entity_offer` ON `payments`.`id` = `entity_offer`.`entity_id`
        // WHERE `payments`.`status` = 'captured'
        //   AND `entity_offer`.`entity_type` = 'payment'
        //   AND `entity_offer`.`offer_id` IN (?)
        //   AND `payments`.`card_id` IN (?)
        // GROUP BY `entity_offer`.`offer_id`
        // HAVING payment_count >= 1
        //

        // last 7 days data fetched from hot storage and merged with rest of the data fetched from warm storage
        $hotTimestamp = Carbon::now(Timezone::IST)->getTimestamp() - 604800;

        $hotData = $this->newQuery()
                        ->select(DB::raw("count($paymentIdCol) AS payment_count, $entityOfferOfferIdCol"))
                        ->join($entityOfferTable, $entityOfferEntityIdCol, '=', $paymentIdCol)
                        ->where($paymentStatusCol, '=', Status::CAPTURED)
                        ->where($entityOfferEntityTypeCol, '=', EntityName::PAYMENT)
                        ->whereIn($entityOfferOfferIdCol, $offerIds)
                        ->whereIn($paymentCardIdCol, $cardIds)
                        ->where($paymentCreatedAtCol, '>=', $hotTimestamp)
                        ->groupBy($entityOfferOfferIdCol)
                        ->having('payment_count', '>=', 1)
                        ->pluck('payment_count', 'offer_id')
                        ->toArray();

        $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_MERCHANT);

        $warmData = $this->newQueryWithConnection($connectionType)
                         ->select(DB::raw("count($paymentIdCol) AS payment_count, $entityOfferOfferIdCol"))
                         ->join($entityOfferTable, $entityOfferEntityIdCol, '=', $paymentIdCol)
                         ->where($paymentStatusCol, '=', Status::CAPTURED)
                         ->where($entityOfferEntityTypeCol, '=', EntityName::PAYMENT)
                         ->whereIn($entityOfferOfferIdCol, $offerIds)
                         ->whereIn($paymentCardIdCol, $cardIds)
                         ->where($paymentCreatedAtCol, '<', $hotTimestamp)
                         ->groupBy($entityOfferOfferIdCol)
                         ->having('payment_count', '>=', 1)
                         ->pluck('payment_count', 'offer_id')
                         ->toArray();

        foreach ($warmData as $offerId => $count)
        {
            if (array_key_exists($offerId, $hotData) === true)
            {
                $hotData[$offerId] += $count;
            }
            else
            {
                $hotData[$offerId] = $count;
            }
        }

        return $hotData;
    }

    /**
     * This query fetches number of successful payments
     * Which are created after minCreatedAt timestamp for each merchant with disputes
     * Note : we defined successful payment as a payment which has AUTHORIZED_AT as not null
     * Note : we consider only those merchants who have atleast 1 dispute created after minCreatedAt
     *
     * @param array $merchantIds
     * @param int $minCreatedAt the timestamp after which payment is considered valid
     *
     * @return array List of merchantIds with corresponding count of successful payments
     *               Sample Output: ["mid1" => 12000, "mid2" => 1500, "mid3" => 100000]
     */
    public function getPaymentsCountForMerchantsFromDataLakePresto(array $merchantIds , int $minCreatedAt): array
    {
        if (empty($merchantIds)) {
            return [];
        }

        $createdDate = Carbon::createFromTimestamp($minCreatedAt)->toDateString();

        $commaSeparatedMerchantIds = "'" . implode("', '", $merchantIds) . "'";

        $sql = sprintf(self::SUCCESSFUL_PAYMENTS_COUNT_SQL, $commaSeparatedMerchantIds, $createdDate, count($merchantIds));

        $queryResult = app('datalake.presto')->getDataFromDataLake($sql);

        $result = [];

        foreach ($queryResult as $row) {
            $result[$row['merchant_id']] = $row['payments_count'];
        }

        return $result;
    }

    /**
     * Fetches the usage of each offer.
     * An offer is considered as used if it has been associated with an authorized payment.
     *
     * @param array  $offerIds
     * @param string $merchantId
     * @param int    $minCreatedAt
     *
     * @return array
     */
    public function getOffersUsage(array $offerIds, string $merchantId, int $minCreatedAt): array
    {
        $paymentAuthorizedAtColumn = $this->dbColumn(Entity::AUTHORIZED_AT);
        $paymentCreatedAtCol       = $this->dbColumn(Entity::CREATED_AT);
        $paymentIdCol              = $this->dbColumn(Entity::ID);
        $paymentMerchantIdCol      = $this->dbColumn(Entity::MERCHANT_ID);

        $entityOfferTable              = $this->repo->entity_offer->getTableName();
        $entityOfferEntityIdCol        = $this->repo->entity_offer->dbColumn(EntityOffer\Entity::ENTITY_ID);
        $entityOfferEntityTypeCol      = $this->repo->entity_offer->dbColumn(EntityOffer\Entity::ENTITY_TYPE);
        $entityOfferEntityOfferTypeCol = $this->repo->entity_offer->dbColumn(EntityOffer\Entity::ENTITY_OFFER_TYPE);
        $entityOfferOfferIdCol         = $this->repo->entity_offer->dbColumn(EntityOffer\Entity::OFFER_ID);
        $entityOfferCreatedAtCol       = $this->repo->entity_offer->dbColumn(EntityOffer\Entity::CREATED_AT);

        $connectionType = $this->getDataWarehouseConnection(ConnectionType::DATA_WAREHOUSE_MERCHANT);

        $query = $this->newQueryWithConnection($connectionType)
            ->select(DB::raw("$entityOfferOfferIdCol, count(*) AS offer_usage"))
            ->join($entityOfferTable, $paymentIdCol, '=', $entityOfferEntityIdCol)
            ->whereNotNull($paymentAuthorizedAtColumn)
            ->where($paymentMerchantIdCol, '=', $merchantId)
            ->where($entityOfferEntityTypeCol, '=', EntityName::PAYMENT)
            ->where(static function (Builder $query) use($entityOfferEntityOfferTypeCol) {
                // The NULL check over here is for legacy reasons & is required
                // for scanning data older than '2020-12-17'
                $query->whereNull($entityOfferEntityOfferTypeCol)
                    ->orWhere($entityOfferEntityOfferTypeCol, '=', 'offer');
            })
            ->where($paymentCreatedAtCol, '>=', $minCreatedAt)
            ->where($entityOfferCreatedAtCol, '>=', $minCreatedAt)
            ->whereIn($entityOfferOfferIdCol, $offerIds)
            ->groupBy($entityOfferOfferIdCol)
            ->limit(count($offerIds));

        return $query->pluck('offer_usage', 'offer_id')->toArray();
    }

    public function fetchByPublicVaIdAndMerchant(string $virtualAccountId, Merchant\Entity $merchant)
    {
        $paymentReceiverId = $this->dbColumn(Payment\Entity::RECEIVER_ID);
        $paymentMerchantId = $this->dbColumn(Payment\Entity::MERCHANT_ID);

        $virtualAccountIdCol = $this->repo->virtual_account->dbColumn(VirtualAccount\Entity::ID);

        $paymentColumns = $this->dbColumn('*');

        $qrcodeId = $this->repo
                         ->virtual_account
                         ->dbColumn(VirtualAccount\Entity::QR_CODE_ID);

        $bankAccountId = $this->repo
                              ->virtual_account
                              ->dbColumn(VirtualAccount\Entity::BANK_ACCOUNT_ID);

        VirtualAccount\Entity::verifyIdAndSilentlyStripSign($virtualAccountId);

        return $this->newQuery()
                    ->select($paymentColumns)
                    ->join(Table::VIRTUAL_ACCOUNT, function ($join) use($paymentReceiverId, $qrcodeId, $bankAccountId)
                    {
                        $join->on($paymentReceiverId, '=', $qrcodeId);
                        $join->orOn($paymentReceiverId, '=', $bankAccountId);
                    })
                    ->where($virtualAccountIdCol, '=', $virtualAccountId)
                    ->where($paymentMerchantId, '=', $merchant->getId())
                    ->orderByCreatedAt()
                    ->get();
    }

    /**
     * Gets all authorized payments which belongs to a
     * paid order and are not disputed. All these
     * payments are supposed to be refunded.
     *
     * @return Base\PublicCollection
     */
    public function getAuthorizedPaymentsOfPaidOrderForRefund()
    {
        // Raw SQL:
        //
        // SELECT payments.*
        // FROM payments
        //     INNER JOIN orders on orders.id = payments.order_id
        // WHERE payments.authorized_at > ?
        //     AND orders.status = 'PAID'
        //     AND payments.status = 'AUTHORIZED'

        $orderTable  = $this->repo->order->getTableName();
        $orderId     = $this->repo->order->dbColumn(Order\Entity::ID);
        $orderStatus = $this->repo->order->dbColumn(Order\Entity::STATUS);

        $paymentCols      = $this->dbColumn('*');
        $paymentStatus    = $this->dbColumn(Entity::STATUS);
        $paymentDisputed  = $this->dbColumn(Entity::DISPUTED);
        $paymentOrderId   = $this->dbColumn(Entity::ORDER_ID);
        $paymentAuthorizedAt = $this->dbColumn(Entity::AUTHORIZED_AT);

        // For optimization purposes we only pick payments authorized in last 2 days. This picked
        // '2 days' is sufficient filter logically.

        $nowMinus2Days = Carbon::today(Timezone::IST)->subDays(2)->getTimestamp();

        $query = $this->newQueryWithConnection($this->getSlaveConnection());

        return $query->from(\DB::raw('`payments` FORCE INDEX (payments_status_index)'))
                     ->join($orderTable, $orderId, '=', $paymentOrderId)
                     ->select($paymentCols)
                     ->where($paymentAuthorizedAt, '>', $nowMinus2Days)
                     ->where($orderStatus, Order\Status::PAID)
                     ->where($paymentStatus, Status::AUTHORIZED)
                     ->where($paymentDisputed, 0)
                     ->with('merchant')
                     ->get();
    }

    public function getPaymentVolumeBetweenTimestamp($from, $to)
    {
        try
        {
            if($this->app['api.route']->isWDAServiceRoute() and
                ($this->isExperimentEnabled($this->app['api.route']->getWdaRouteExperimentName()) ===  true))
            {
                return  $this->getPaymentVolumeBetweenTimestampFromWda($from, $to);
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->error(TraceCode::WDA_MIGRATION_ERROR, [
                'wda_migration_error' => $ex->getMessage(),
                'function'            => __FUNCTION__ ,
                'route_name'          => $this->app['api.route']->getCurrentRouteName(),
            ]);
        }

        $vol = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
                    ->betweenTime($from, $to)
                    ->statusSuccess()
                    ->selectRaw('SUM(' . Entity::AMOUNT . ') AS amount' . ','.
                       'COUNT(*) AS count')
                    ->where(Entity::METHOD, '!=', Method::TRANSFER)
                    ->first();

        return $vol;
    }

    public function getPaymentVolumeBetweenTimestampFromWda($from, $to)
    {
        $this->trace->info(TraceCode::WDA_SERVICE_REQUEST, [
            'function'     => __FUNCTION__,
            'input_params' => ['from' => $from, 'to' => $to],
            'route_name'   => $this->app['api.route']->getCurrentRouteName(),
        ]);

        $startTimeMs = round(microtime(true) * 1000);

        $wdaClient = $this->app['wda-client']->wdaClient;

        $wdaQueryBuilder = new WDAQueryBuilder();

        $wdaQueryBuilder->addQuery($this->getTableName(), Entity::AMOUNT, "SUM", "amount")
                        ->addQuery($this->getTableName(), '*', "COUNT", "count");

        $wdaQueryBuilder->resources($this->getTableName());

        $wdaQueryBuilder->filters($this->getTableName(), Entity::CREATED_AT, [$from], Symbol::GTE)
                        ->filters($this->getTableName(), Entity::CREATED_AT, [$to], Symbol::LTE)
                        ->filters($this->getTableName(), Entity::STATUS, [Status::FAILED, Status::CREATED], Symbol::NOT_IN)
                        ->filters($this->getTableName(),Entity::METHOD, [Method::TRANSFER], Symbol::NEQ);

        $wdaQueryBuilder->namespace($this->getEntityObject()->getConnection()->getDatabaseName());

        $wdaQueryBuilder->cluster(WDAService::ADMIN_CLUSTER);

        $this->trace->info(TraceCode::WDA_SERVICE_QUERY, [
            'wda_query_builder' => $wdaQueryBuilder->build()->serializeToJsonString(),
            'route_name'    => $this->app['api.route']->getCurrentRouteName(),
        ]);

        $result = $wdaClient->fetch($wdaQueryBuilder->build(), $this->newQuery()->getModel());

        $endTimeMs = round(microtime(true) * 1000);

        $queryDuration = $endTimeMs - $startTimeMs;

        $this->trace->info(TraceCode::WDA_SERVICE_RESPONSE, [
            'method_name'   => __FUNCTION__,
            'duration_ms'    => $queryDuration,
            'route_name'    => $this->app['api.route']->getCurrentRouteName(),
        ]);

        return $result;
    }

    public function getCapturedAmountByGateway(string $gateway, int $from, int $to)
    {
        // checks replication lag of 5 mins
        // throws exception if lag is greater than the threshold
        return $this->newQueryOnSlave(300000)
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->whereBetween(Entity::CAPTURED_AT, [$from, $to])
                    ->sum(Entity::AMOUNT);
    }

    /**
     * @param int $from
     * @param int $to
     * @return mixed
     * @throws Exception\ServerErrorException
     */
    public function fetchPendingEmandateRegistrationForEnachOptimised(int $from, int $to)
    {
        $paymentIdColumn = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $paymentRecurringColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $tokenIdColumn = $this->repo->token->dbColumn(Token\Entity::ID);

        $tokenRecurringColumn = $this->repo->token->dbColumn(Token\Entity::RECURRING);

        $enachPaymentIdColumn = $this->repo->enach->dbColumn(Enach\Base\Entity::PAYMENT_ID);

        $enachRegistrationDateColumn = $this->repo->enach->dbColumn(Enach\Base\Entity::REGISTRATION_DATE);

        $selectCols = $this->repo->payment->dbColumn('*');

        $globalTokenQuery = $this->newQueryOnPaymentFetchReplica(600000)
            ->select($selectCols)
            ->join(Table::TOKEN, Entity::GLOBAL_TOKEN_ID, '=', $tokenIdColumn)
            ->join(Table::ENACH, $paymentIdColumn, '=', $enachPaymentIdColumn)
            ->where(Entity::RECURRING_TYPE, '=', RecurringType::INITIAL)
            ->where($paymentRecurringColumn, '=', 1)
            ->where($paymentMethodColumn, '=', Method::EMANDATE)
            ->where(Entity::GATEWAY, '=', Payment\Gateway::ENACH_RBL)
            ->whereBetween($enachRegistrationDateColumn, [$from, $to])
            ->where(Token\Entity::RECURRING_STATUS, '=', Token\RecurringStatus::INITIATED)
            ->where($tokenRecurringColumn, '!=', 1)
            ->whereNotNull(Entity::AUTHORIZED_AT)
            ->with(['localToken', 'globalToken', 'customer', 'enach']);

        $localTokenQuery = $this->newQueryOnPaymentFetchReplica(600000)
            ->select($selectCols)
            ->join(Table::TOKEN, Entity::TOKEN_ID, '=', $tokenIdColumn)
            ->join(Table::ENACH, $paymentIdColumn, '=', $enachPaymentIdColumn)
            ->where(Entity::RECURRING_TYPE, '=', RecurringType::INITIAL)
            ->where($paymentRecurringColumn, '=', 1)
            ->where($paymentMethodColumn, '=', Method::EMANDATE)
            ->where(Entity::GATEWAY, '=', Payment\Gateway::ENACH_RBL)
            ->whereBetween($enachRegistrationDateColumn, [$from, $to])
            ->where(Token\Entity::RECURRING_STATUS, '=', Token\RecurringStatus::INITIATED)
            ->where($tokenRecurringColumn, '!=', 1)
            ->whereNotNull(Entity::AUTHORIZED_AT)
            ->with(['localToken', 'globalToken', 'customer', 'enach']);

        return $localTokenQuery->union($globalTokenQuery)->get();
    }

    /**
     * @param int $from
     * @param int $to
     * @return
     * @throws Exception\ServerErrorException
     */
    public function fetchPendingEmandateRegistrationForEnach(int $from, int $to)
    {
        $paymentIdColumn = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $paymentRecurringColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $tokenIdColumn = $this->repo->token->dbColumn(Token\Entity::ID);

        $tokenRecurringColumn = $this->repo->token->dbColumn(Token\Entity::RECURRING);

        $enachPaymentIdColumn = $this->repo->enach->dbColumn(Enach\Base\Entity::PAYMENT_ID);

        $enachRegistrationDateColumn = $this->repo->enach->dbColumn(Enach\Base\Entity::REGISTRATION_DATE);

        $selectCols = $this->repo->payment->dbColumn('*');

        return $this->newQueryOnPaymentFetchReplica(600000)
                    ->select($selectCols)
                    ->join(
                        Table::TOKEN,
                        function ($join)
                        use($tokenIdColumn)
                        {
                            $join->on(Entity::TOKEN_ID, '=', $tokenIdColumn);
                            $join->orOn(Entity::GLOBAL_TOKEN_ID, '=', $tokenIdColumn);
                        })
                    ->join(Table::ENACH, $paymentIdColumn, '=', $enachPaymentIdColumn)
                    ->where(Entity::RECURRING_TYPE, '=', RecurringType::INITIAL)
                    ->where($paymentRecurringColumn, '=', 1)
                    ->where($paymentMethodColumn, '=', Method::EMANDATE)
                    ->where(Entity::GATEWAY, '=', Payment\Gateway::ENACH_RBL)
                    ->whereBetween($enachRegistrationDateColumn, [$from, $to])
                    ->where(Token\Entity::RECURRING_STATUS, '=', Token\RecurringStatus::INITIATED)
                    ->where($tokenRecurringColumn, '!=', 1)
                    ->whereNotNull(Entity::AUTHORIZED_AT)
                    ->with(['localToken', 'globalToken', 'customer', 'enach'])
                    ->get();
    }

    public function fetchPendingEmandateDebit(string $gateway, $from, $to)
    {
        $tokenIdColumn = $this->repo->token->dbColumn(Token\Entity::ID);

        $tokenRecurringColumn = $this->repo->token->dbColumn(Token\Entity::RECURRING);

        $paymentRecurringColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $paymentCreatedAtColumn = $this->repo->payment->dbColumn(Payment\Entity::CREATED_AT);

        $selectCols = $this->dbColumn('*');

        return $this->newQuery()
                    ->select($selectCols)
                    ->join(
                        Table::TOKEN,
                        function ($join)
                        use ($tokenIdColumn)
                        {
                          $join->on(Entity::TOKEN_ID, '=', $tokenIdColumn);
                          $join->orOn(Entity::GLOBAL_TOKEN_ID, '=', $tokenIdColumn);
                        })
                    ->where(Entity::RECURRING_TYPE, '=', RecurringType::AUTO)
                    ->where(Entity::STATUS, '=', Status::CREATED)
                    ->where($paymentRecurringColumn, '=', 1)
                    ->where($paymentMethodColumn, '=', Method::EMANDATE)
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->whereBetween($paymentCreatedAtColumn, [$from, $to])
                    ->where(Token\Entity::RECURRING_STATUS, '=', Token\RecurringStatus::CONFIRMED)
                    ->where($tokenRecurringColumn, '=', 1)
                    ->with(['localToken', 'globalToken', 'merchant', 'order', 'terminal'])
                    ->get();
    }

    public function fetchDebitEmandatePaymentPendingAuth(
        string $gateway, string $paymentId, string $accountNo)
    {
        $tokenIdColumn = $this->repo->token->dbColumn(Token\Entity::ID);

        $paymentIdColumn = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $paymentRecurringColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $selectCols = $this->dbColumn('*');

        //
        // The SQL query that will be run is –
        //
        // select `payments`.* from `payments` inner join `tokens`
        // on `token_id` = `tokens`.`id` or `global_token_id` = `tokens`.`id`
        // where `payments`.`id` = ? and
        // `account_number` = ? and
        // `recurring_type` = ? and
        // `status` = ? and
        // `payments`.`recurring` = ? and
        // `payments`.`method` = ? and
        // `gateway` = ?
        //
        return $this->newQuery()
                    ->select($selectCols)
                    ->join(
                      Table::TOKEN,
                      function ($join)
                      use ($tokenIdColumn)
                      {
                        $join->on(Entity::TOKEN_ID, '=', $tokenIdColumn);
                        $join->orOn(Entity::GLOBAL_TOKEN_ID, '=', $tokenIdColumn);
                      })
                    ->where($paymentIdColumn, $paymentId)
                    ->where(Token\Entity::ACCOUNT_NUMBER, $accountNo)
                    ->where(Entity::RECURRING_TYPE, RecurringType::AUTO)
                    ->where($paymentRecurringColumn, 1)
                    ->where($paymentMethodColumn, Method::EMANDATE)
                    ->where(Entity::GATEWAY, $gateway)
                    ->with('merchant')
                    ->firstOrFail();
    }

    public function fetchDebitNachPaymentPendingAuth(
        string $gateway, string $paymentId, string $accountNo)
    {
        $tokenIdColumn = $this->repo->token->dbColumn(Token\Entity::ID);

        $paymentIdColumn = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $paymentRecurringColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $selectCols = $this->dbColumn('*');

        //
        // The SQL query that will be run is –
        //
        // select `payments`.* from `payments` inner join `tokens`
        // on `token_id` = `tokens`.`id` or `global_token_id` = `tokens`.`id`
        // where `payments`.`id` = ? and
        // `account_number` = ? and
        // `recurring_type` = ? and
        // `status` = ? and
        // `payments`.`recurring` = ? and
        // `payments`.`method` = ?
        //
        return $this->newQuery()
            ->select($selectCols)
            ->join(
                Table::TOKEN,
                function ($join)
                use ($tokenIdColumn)
                {
                    $join->on(Entity::TOKEN_ID, '=', $tokenIdColumn);
                    $join->orOn(Entity::GLOBAL_TOKEN_ID, '=', $tokenIdColumn);
                })
            ->where($paymentIdColumn, $paymentId)
            ->where(Token\Entity::ACCOUNT_NUMBER, $accountNo)
            ->where(Entity::RECURRING_TYPE, RecurringType::AUTO)
            ->where($paymentRecurringColumn, 1)
            ->where($paymentMethodColumn, Method::NACH)
            ->where(Entity::GATEWAY, $gateway)
            ->with('merchant')
            ->firstOrFail();
    }

    public function fetchDebitEnachPaymentPendingAuth(
        string $gateway, string $paymentId, string $gatewayToken)
    {
        $tokenIdColumn = $this->repo->token->dbColumn(Token\Entity::ID);

        $paymentIdColumn = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $paymentRecurringColumn = $this->repo->payment->dbColumn(Payment\Entity::RECURRING);

        $paymentMethodColumn = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $selectCols = $this->dbColumn('*');

        //
        // The SQL query that will be run is –
        //
        // select `payments`.* from `payments` inner join `tokens`
        // on `token_id` = `tokens`.`id` or `global_token_id` = `tokens`.`id`
        // where `payments`.`id` = ? and
        // `account_number` = ? and
        // `recurring_type` = ? and
        // `status` = ? and
        // `payments`.`recurring` = ? and
        // `payments`.`method` = ? and
        // `gateway` = ?
        //
        return $this->newQuery()
                    ->select($selectCols)
                    ->join(
                      Table::TOKEN,
                      function ($join)
                      use ($tokenIdColumn)
                      {
                        $join->on(Entity::TOKEN_ID, '=', $tokenIdColumn);
                        $join->orOn(Entity::GLOBAL_TOKEN_ID, '=', $tokenIdColumn);
                      })
                    ->where($paymentIdColumn, $paymentId)
                    ->where(Token\Entity::GATEWAY_TOKEN, $gatewayToken)
                    ->where(Entity::RECURRING_TYPE, RecurringType::AUTO)
                    ->where($paymentRecurringColumn, 1)
                    ->where($paymentMethodColumn, Method::EMANDATE)
                    ->where(Entity::GATEWAY, $gateway)
                    ->with('merchant')
                    ->firstOrFail();
    }

    protected function addQueryParamBankReference($query, $params)
    {
        $this->joinQueryBankTransfer($query);

        $bankReference = $this->repo->bank_transfer->dbColumn(BankTransfer\Entity::UTR);

        $query->where($bankReference, '=', $params[BankTransfer\Entity::BANK_REFERENCE]);

        $query->select($this->getTableName() . '.*');
    }

    protected function addWDAQueryParamBankReference($wdaQueryBuilder, $params)
    {
        $this->joinWDAQueryBankTransfer($wdaQueryBuilder);

        $bankTransferTable = Table::getTableNameForEntity(Constants\Entity::BANK_TRANSFER);

        $wdaQueryBuilder->filters($bankTransferTable, BankTransfer\Entity::UTR, [$params[BankTransfer\Entity::BANK_REFERENCE]], Symbol::EQ);
    }

    protected function addQueryParamAcquirerData($query, $params)
    {
        $cardAcqDataSql = "IF(" . Entity::METHOD . " = '" . Method::CARD . "', " . Entity::REFERENCE2 . "=?, '')";
        $bankAcqDataSql = "IF(" . Entity::METHOD . " = '" . Method::NETBANKING . "', " . Entity::REFERENCE1 . "=?, '')";

        // Acquirer data column is picked based on method
        $query->where(function ($q) use ($cardAcqDataSql, $bankAcqDataSql, $params)
        {
            $q->whereRaw($cardAcqDataSql, $params[Entity::ACQUIRER_DATA])
              ->orWhereRaw($bankAcqDataSql, $params[Entity::ACQUIRER_DATA]);
        });

        $query->select($this->getTableName() . '.*');
    }

    protected function addWDAQueryParamAcquirerData($wdaQueryBuilder, $params)
    {
        throw new LogicException('Support for raw queries not yet implemented on WDA');
    }

    /**
     * Fetches payments for virtual account
     *
     * select * from `payments` inner join `virtual_accounts` on
     * `payments`.`receiver_id` = `virtual_accounts`.`qr_code_id` or
     * `payments`.`receiver_id` = `virtual_accounts`.`bank_account_id`
     *  where `payments`.`merchant_id` = ? and `virtual_accounts`.`id` = ?;
     *
     * @todo : Join has to be added for VPA as well
     *
     * @param $query
     * @param $params
     */
    protected function addQueryParamVirtualAccountId($query, $params)
    {
        $this->joinQueryVaReceiver($query);

        $virtualAccountIdCol = $this->repo->virtual_account->dbColumn(VirtualAccount\Entity::ID);

        $virtualAccountId = $params[Payment\Entity::VIRTUAL_ACCOUNT_ID];

        $query->where($virtualAccountIdCol, '=', $virtualAccountId);
    }

    protected function addWDAQueryParamVirtualAccountId($wdaQueryBuilder, $params)
    {
        $this->joinWDAQueryVaReceiver($wdaQueryBuilder);

        $virtualAccountId = $params[Payment\Entity::VIRTUAL_ACCOUNT_ID];

        $wdaQueryBuilder->filters(Table::VIRTUAL_ACCOUNT, VirtualAccount\Entity::ID, [$virtualAccountId], Symbol::EQ);
    }

    /**
     * select `payments`.* from `payments` where `payments`.`merchant_id` = ?
     * and `receiver_id` is not null
     *
     * @param $query
     * @param $params
     */
    protected function addQueryParamVirtualAccount($query, $params)
    {
        if ($params[Entity::VIRTUAL_ACCOUNT] !== '1')
        {
            return;
        }

        $query->whereNotNull(Entity::RECEIVER_ID);
    }

    protected function adWDAdQueryParamVirtualAccount($wdaQueryBuilder, $params)
    {
        if ($params[Entity::VIRTUAL_ACCOUNT] !== '1')
        {
            return;
        }

        $wdaQueryBuilder->filters($this->getTableName(), Entity::RECEIVER_ID, [], Symbol::NOT_NULL);
    }

    protected function addQueryParamIntlBankTransfer($query, $params)
    {
        if ($params[Entity::INTL_BANK_TRANSFER] !== '1')
        {
            return;
        }

        $query->where(Entity::METHOD,Entity::INTL_BANK_TRANSFER);
    }

    protected function addWDAQueryParamIntlBankTransfer($wdaQueryBuilder, $params)
    {
        if ($params[Entity::INTL_BANK_TRANSFER] !== '1')
        {
            return;
        }

        $wdaQueryBuilder->filters($this->getTableName(), Entity::METHOD, [Entity::INTL_BANK_TRANSFER], Symbol::EQ);
    }

    /**
     * @param $query
     */
    protected function joinQueryVaReceiver($query): void
    {
        $paymentReceiverId = $this->dbColumn(Payment\Entity::RECEIVER_ID);

        $qrcodeId = $this->repo
                         ->virtual_account
                         ->dbColumn(VirtualAccount\Entity::QR_CODE_ID);

        $bankAccountId = $this->repo
                              ->virtual_account
                              ->dbColumn(VirtualAccount\Entity::BANK_ACCOUNT_ID);

        $bankAccountId2 = $this->repo
                               ->virtual_account
                               ->dbColumn(VirtualAccount\Entity::BANK_ACCOUNT_ID2);

        $vpaId = $this->repo
                      ->virtual_account
                      ->dbColumn(VirtualAccount\Entity::VPA_ID);

        $query->join(Table::VIRTUAL_ACCOUNT, function($join) use ($paymentReceiverId, $qrcodeId, $bankAccountId, $vpaId, $bankAccountId2)
        {
            $join->on($paymentReceiverId, '=', $qrcodeId);
            $join->orOn($paymentReceiverId, '=', $bankAccountId);
            $join->orOn($paymentReceiverId, '=', $vpaId);
            $join->orOn($paymentReceiverId, '=', $bankAccountId2);
        });
    }

    protected function joinWDAQueryVaReceiver($wdaQueryBuilder): void
    {
        $paymentReceiverId = $this->dbColumn(Payment\Entity::RECEIVER_ID);

        $qrcodeId = $this->repo
            ->virtual_account
            ->dbColumn(VirtualAccount\Entity::QR_CODE_ID);

        $bankAccountId = $this->repo
            ->virtual_account
            ->dbColumn(VirtualAccount\Entity::BANK_ACCOUNT_ID);

        $bankAccountId2 = $this->repo
            ->virtual_account
            ->dbColumn(VirtualAccount\Entity::BANK_ACCOUNT_ID2);

        $vpaId = $this->repo
            ->virtual_account
            ->dbColumn(VirtualAccount\Entity::VPA_ID);

        $wdaQueryBuilder->addResource(Table::VIRTUAL_ACCOUNT, "INNER", $paymentReceiverId. ' = '. $qrcodeId. ' OR '. $paymentReceiverId. ' = '. $bankAccountId.
                                        ' OR '. $paymentReceiverId. ' = '. $vpaId. ' OR '. $paymentReceiverId. ' = '. $bankAccountId2);
    }

    protected function joinQueryBankTransfer($query)
    {
        $joins = $query->getQuery()->joins;

        $joins = $joins ?: [];

        $bankTransferTable = Table::getTableNameForEntity(Constants\Entity::BANK_TRANSFER);

        foreach ($joins as $join)
        {
            if ($join->table === $bankTransferTable)
            {
                return;
            }
        }

        $paymentId = $this->dbColumn(Entity::ID);
        $bankTransferPaymentId = $this->repo->bank_transfer->dbColumn(BankTransfer\Entity::PAYMENT_ID);

        $query->join($bankTransferTable, $paymentId, '=', $bankTransferPaymentId);
    }

    protected function joinWDAQueryBankTransfer($wdaQueryBuilder)
    {
        $joins = $wdaQueryBuilder->getJoinTables();

        $bankTransferTable = Table::getTableNameForEntity(Constants\Entity::BANK_TRANSFER);

        foreach ($joins as $join)
        {
            if ($join === $bankTransferTable)
            {
                return;
            }
        }

        $paymentId = $this->dbColumn(Entity::ID);

        $bankTransferPaymentId = $this->repo->bank_transfer->dbColumn(BankTransfer\Entity::PAYMENT_ID);

        $wdaQueryBuilder->addResource($bankTransferTable, "INNER", $paymentId.' = '.$bankTransferPaymentId);
    }

    protected function joinQueryTerminal(BuilderEx $query)
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $terminalTable = Table::getTableNameForEntity(Constants\Entity::TERMINAL);

        if ($query->hasJoin($terminalTable) === true)
        {
            return;
        }

        $paymentTerminalId = $this->dbColumn(Entity::TERMINAL_ID);
        $terminalId = $this->repo->terminal->dbColumn(Terminal\Entity::ID);

        $query->join($terminalTable, $paymentTerminalId, $terminalId);
    }

    protected function joinWDAQueryTerminal($wdaQueryBuilder)
    {
        $joins = $wdaQueryBuilder->getJoinTables();

        $terminalTable = Table::getTableNameForEntity(Constants\Entity::TERMINAL);

        foreach ($joins as $join)
        {
            if ($join === $terminalTable)
            {
                return;
            }
        }

        $paymentTerminalId = $this->dbColumn(Entity::TERMINAL_ID);

        $terminalId = $this->repo->terminal->dbColumn(Terminal\Entity::ID);

        $wdaQueryBuilder->addResource($terminalTable, "INNER", $paymentTerminalId.' = '.$terminalId);
    }

    public function getAliasesForPaymentsDbColumns($params): array
    {
        $dbColumns = [];

        foreach ($params as $param)
        {
            $dbColumn = $this->repo->payment->dbColumn($param);

            $dbColumns[] = $dbColumn . ' as payment_'. $param;
        }

        return $dbColumns;
    }

    public function fetchAxisPaysecurePayments($input)
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $params = $input;

        $terminalTableName = $this->repo->terminal->getTableName();
        $terminalTableGtidColName = $this->repo->terminal->dbColumn(Terminal\Entity::ID);
        $paymentTableGtidColName = $this->repo->payment->dbColumn(Payment\Entity::TERMINAL_ID);

        $terminalTableAquirerCol =  $this->repo->terminal->dbColumn(Terminal\Entity::GATEWAY_ACQUIRER);

        $paymentTableGatewayCol = $this->repo->payment->dbColumn(Payment\Entity::GATEWAY);

        $this->processFetchParams($params);

        $expands = $this->getExpandsForQueryFromInput($params);

        $query = $this->newQueryWithConnection($this->getDataWarehouseConnection());
        $query = $query->with($expands);
        $query = $this->buildFetchQuery($query, $params);

        $query = $query->join($terminalTableName, $terminalTableGtidColName, '=', $paymentTableGtidColName)
            ->where($terminalTableAquirerCol, '=', 'axis')
            ->where($paymentTableGatewayCol, '=', 'paysecure')
            ->with('terminal');

        return $this->getPaginated($query, $params);
    }

    /**
     * calculates the sum of `fee` and `tax` for the payments
     *  - captured for a merchant in a given time frame
     *  - based on filter type passed OTHER, CARD_LT_2K, CARD_GT_2K
     *
     * - Here cut off amount is checked on base_amount to handle multiple currencies
     *   In payments table base_amount field will hold the amount in
     *   INR(paise) regardless of what type of currency been used
     *
     * @param string $merchantId
     * @param int    $start
     * @param int    $end
     * @param string $filterType
     *
     * @return mixed
     * @throws Exception\LogicException
     */
    public function fetchFeesAndTaxForPaymentByType(
        string $merchantId,
        int $start,
        int $end,
        string $filterType)
    {
        //
        // will consider all the payments
        //
        $query = $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
                      ->selectRaw('SUM(' . Entity::TAX . ') AS tax, SUM(' . Entity::FEE . ') AS fee')
                      ->whereBetween(Entity::CAPTURED_AT, [$start, $end])
                      ->whereNotNull(Entity::TRANSACTION_ID);

        $query->merchantId($merchantId);

        switch ($filterType)
        {
            case InvoiceType::OTHERS:
                $query = $query->whereNull(Entity::CARD_ID);

                break;

            case InvoiceType::CARD_LTE_2K:
                $query = $query->whereNotNull(Entity::CARD_ID)
                               ->where(Entity::BASE_AMOUNT, '<=', Calculator\Tax\IN\Constants::CARD_TAX_CUT_OFF);

                break;

            case InvoiceType::CARD_GT_2K:
                $query = $query->whereNotNull(Entity::CARD_ID)
                               ->where(Entity::BASE_AMOUNT, '>', Calculator\Tax\IN\Constants::CARD_TAX_CUT_OFF);

                break;

            default:
                throw new Exception\LogicException('Invalid merchant invoice type: ', $filterType);
        }

        return $query->first();
    }

    /**
     * select `payments`.*, `bank_accounts`.`id` as `bank_account_id` from `payments` inner join
     * `bank_transfers` on `bank_transfers`.`payment_id` = `payments`.`id` inner join `virtual_accounts`
     * on `bank_transfers`.`virtual_account_id` = `virtual_accounts`.`id` inner join `bank_accounts` on
     * `bank_accounts`.`id` = `virtual_accounts`.`bank_account_id` where `method` = 'bank_transfer' and
     * `receiver_id` is null limit 1000
     *
     *  @return collection
     */
    public function fetchBankTransferPaymentWithoutReceiver()
    {
        $paymentId = $this->repo->payment->dbColumn(Entity::ID);

        $bankTransferTable = $this->repo->bank_transfer->getTableName();

        $bankTransferPaymentId = $this->repo->bank_transfer->dbColumn(BankTransfer\Entity::PAYMENT_ID);

        $bankTransferVirtualAccountId = $this->repo->bank_transfer->dbColumn(BankTransfer\Entity::VIRTUAL_ACCOUNT_ID);

        $virtualAccountId = $this->repo->virtual_account->dbColumn(VirtualAccount\Entity::ID);

        $virtualAccountTable = $this->repo->virtual_account->getTableName();

        $virtualAccountBankAccountId = $this->repo->virtual_account->dbColumn(VirtualAccount\Entity::BANK_ACCOUNT_ID);

        $bankAccountTable = $this->repo->bank_account->getTableName();

        $bankAccountId = $this->repo->bank_account->dbColumn(BankAccount\Entity::ID);

        return $this->newQuery()
                    ->select('payments.*', 'bank_accounts.id as bank_account_id')
                    ->where(Payment\Entity::METHOD, Method::BANK_TRANSFER)
                    ->join($bankTransferTable, $bankTransferPaymentId , '=', $paymentId)
                    ->join($virtualAccountTable, $bankTransferVirtualAccountId, '=', $virtualAccountId)
                    ->join($bankAccountTable, $bankAccountId, '=', $virtualAccountBankAccountId)
                    ->whereNull(Payment\Entity::RECEIVER_ID)
                    ->limit(1000)
                    ->get();
    }

    public function fetchPaymentsWithoutTerminal($method, $rows)
    {
        return $this->newQuery()
                    ->where(Payment\Entity::METHOD, $method)
                    ->whereNull(Payment\Entity::TERMINAL_ID)
                    ->limit($rows)
                    ->get();
    }

    public function buildUpdateMdrQuery(string $lastUpdatedPaymentId = null, int $lastUpdatedPaymentCapturedAt)
    {
        $query = $this->newQuery()->with('transaction')
                      ->where(Entity::GATEWAY, Payment\Gateway::HITACHI)
                      ->whereBetween(Entity::CAPTURED_AT, [$lastUpdatedPaymentCapturedAt, 1533925800]);

        if ($lastUpdatedPaymentId !== null)
        {
            $query->where(Entity::ID, '>', $lastUpdatedPaymentId);
        }

        return $query;
    }

    public function getLastCreatedEmandatePaymentByGateway($gateway)
    {
        return $this->newQuery()
            ->where(Entity::METHOD, Method::EMANDATE)
            ->where(Entity::GATEWAY, $gateway)
            ->first();
    }

    public function getByTokenIdAndCustomerId(string $tokenId, string $customerId)
    {
        $payment = $this->newQueryWithConnection($this->getSlaveConnection())
                        ->where(Entity::TOKEN_ID, $tokenId)
                        ->where(Entity::CUSTOMER_ID, $customerId)
                        ->first();

        if (empty($payment) === false)
        {
            return $payment;
        }

        $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);

        return $this->newQueryWithConnection($connectionType)
                        ->where(Entity::TOKEN_ID, $tokenId)
                        ->where(Entity::CUSTOMER_ID, $customerId)
                        ->first();
    }

    public function getByInvoiceId(string $invoiceId)
    {
        return $this->newQuery()
                    ->where(Entity::INVOICE_ID, $invoiceId)
                    ->first();
    }

    public function getCapturedPaymentsForInvoice(string $invoiceId)
    {
            $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_MERCHANT);

            $query = $this->newQueryWithConnection($connectionType);

            return $query
                        ->where(Entity::INVOICE_ID, $invoiceId)
                        ->where(Entity::STATUS, '=', Status::CAPTURED)
                        ->get();
    }

    public function fetchCreatedPaymentsBetween(string $gateway, int $from, int $to)
    {
        return $this->newQuery()
                    ->betweenTime($from, $to)
                    ->where(Entity::STATUS, '=', Status::CREATED)
                    ->where(Payment\Entity::GATEWAY, '=', $gateway)
                    ->get();
    }

    public function fetchPaymentsGivenIds(array $paymentIds, int $limit)
    {
        $payments = $this->newQuery()
                         ->whereIn(Payment\Entity::ID, $paymentIds)
                         ->limit($limit)
                         ->get();

        if (count($payments) === count($paymentIds))
        {
            return $payments;
        }

        $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_MERCHANT);

        $warmPayments = $this->newQueryWithConnection($connectionType)
                             ->whereIn(Payment\Entity::ID, $paymentIds)
                             ->limit($limit)
                             ->get();

        return $this->mergeCollectionsBasedOnKey($payments, $warmPayments, Entity::ID);
    }

    public function fetchPaymentsGivenIdsFromTidb(array $paymentIds, int $limit, string $conn): Base\PublicCollection
    {
        $query = $this->newQueryWithConnection($this->getConnectionFromType($conn));

        return $query->whereIn(Payment\Entity::ID, $paymentIds)
                     ->limit($limit)
                     ->get();
    }

    public function findPaymentsWithCardVault(string $vault, int $limit)
    {
        $window = 1200;

        $cardRepo = $this->repo->card;

        $cardTableName = $cardRepo->getTableName();

        $cardIdColumn = $cardRepo->dbColumn(Card\Entity::ID);

        $cardVaultColumn = $cardRepo->dbColumn(Card\Entity::VAULT);

        $paymentData = $this->dbColumn('*');

        $timestamp = time() - $window;

        $createdAt  = $this->dbColumn(Entity::CREATED_AT);

        $paymentCardIdColumn  = $this->dbColumn(Entity::CARD_ID);

        return $this->newQuery()
                    ->join($cardTableName, $paymentCardIdColumn, '=', $cardIdColumn)
                    ->where($cardVaultColumn, '=', $vault)
                    ->where($createdAt, '<=', $timestamp)
                    ->select($paymentData)
                    ->limit($limit)
                    ->get();
    }

    public function determineLiveOrTestModeForEntityWithGateway($id, $gateway)
    {
        $obj = $this->connection(Mode::LIVE)->newQuery()->where(Entity::GATEWAY, $gateway)->find($id);

        if ($obj !== null)
        {
            return Mode::LIVE;
        }

        $obj = $this->connection(Mode::TEST)->newQuery()->where(Entity::GATEWAY, $gateway)->find($id);

        if ($obj !== null)
        {
            return Mode::TEST;
        }

        // Check id in archived data replica as the entity might be archived
        $obj = $this->newQueryWithConnection(Connection::ARCHIVED_DATA_REPLICA_LIVE)->where(Entity::GATEWAY, $gateway)->find($id);

        if ($obj !== null)
        {
            return Mode::LIVE;
        }

        $obj = $this->newQueryWithConnection(Connection::ARCHIVED_DATA_REPLICA_TEST)->where(Entity::GATEWAY, $gateway)->find($id);

        if ($obj !== null)
        {
            return Mode::TEST;
        }

        //
        // We need to set connection to null
        // because it will be set to test if the
        // id is not found in any of the database.
        // So even if the db connection is later set
        // to live, query connection will be set to
        // test.
        //
        $this->connection(null);

        return null;
    }

    public function determineLiveOrTestModeForEntityWithNotNullGateway($id, $gateway)
    {
        $obj = $this->connection(Mode::LIVE)->newQuery()->find($id);

        if (($obj !== null) and
            ($obj->getAuthenticationGateway() !== null))
        {
            return Mode::LIVE;
        }

        $obj = $this->connection(Mode::TEST)->newQuery()->find($id);

        if (($obj !== null) and
            ($obj->getAuthenticationGateway() !== null))
        {
            return Mode::TEST;
        }

        // Check id in archived data replica as the entity might be archived
        $obj = $this->newQueryWithConnection(Connection::ARCHIVED_DATA_REPLICA_LIVE)->find($id);

        if (($obj !== null) and
            ($obj->getAuthenticationGateway() !== null))
        {
            return Mode::LIVE;
        }

        $obj = $this->newQueryWithConnection(Connection::ARCHIVED_DATA_REPLICA_TEST)->find($id);

        if (($obj !== null) and
            ($obj->getAuthenticationGateway() !== null))
        {
                return Mode::TEST;
        }

        //
        // We need to set connection to null
        // because it will be set to test if the
        // id is not found in any of the database.
        // So even if the db connection is later set
        // to live, query connection will be set to
        // test.
        //
        $this->connection(null);

        return null;
    }

    public function fetchBySubscriptionId(string $subscriptionId)
    {
        $subscriptionId = Base\PublicEntity::stripDefaultSign($subscriptionId);

        $payment = $this->newQuery()
                        ->where(Entity::SUBSCRIPTION_ID, $subscriptionId)
                        ->first();

        if (empty($payment) === false)
        {
            return $payment;
        }

        $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_MERCHANT);

        return $this->newQueryWithConnection($connectionType)
                    ->where(Entity::SUBSCRIPTION_ID, $subscriptionId)
                    ->first();
    }

    public function fetchSubscriptionIdAndRecurringType(string $subscriptionId, string $tokenId, $recurringTypes, $paymentStatuses)
    {
        $subscriptionId = Base\PublicEntity::stripDefaultSign($subscriptionId);

        $query = $this->newQuery()
                      ->where(Entity::SUBSCRIPTION_ID, $subscriptionId)
                      ->whereIn(Entity::RECURRING_TYPE, $recurringTypes)
                      ->whereIn(Entity::STATUS, $paymentStatuses);

        if (empty($tokenId) != true)
        {
            $query = $query->where(Entity::TOKEN_ID, $tokenId);
        }

        $payments = $query->orderBy(Entity::CREATED_AT, 'desc')->get();

        $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);

        $query = $this->newQueryWithConnection($connectionType)
                      ->where(Entity::SUBSCRIPTION_ID, $subscriptionId)
                      ->whereIn(Entity::RECURRING_TYPE, $recurringTypes)
                      ->whereIn(Entity::STATUS, $paymentStatuses);

        if (empty($tokenId) != true)
        {
            $query = $query->where(Entity::TOKEN_ID, $tokenId);
        }

        $warmPayments = $query->orderBy(Entity::CREATED_AT, 'desc')->get();

        return $this->mergeCollectionsBasedOnKey($payments, $warmPayments, Entity::ID);
    }

    public function fetchByIdandSubscriptionId(string $paymentId, string $subscriptionId)
    {
        Entity::verifyIdAndStripSign($paymentId);

        $subscriptionId = Base\PublicEntity::stripDefaultSign($subscriptionId);

        try
        {
            return $this->newQuery()
                        ->where(Entity::SUBSCRIPTION_ID, $subscriptionId)
                        ->findOrFailPublic($paymentId);
        }
        catch (\Throwable $ex)
        {
            $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_MERCHANT);

            return $this->newQueryWithConnection($connectionType)
                        ->where(Entity::SUBSCRIPTION_ID, $subscriptionId)
                        ->findOrFailPublic($paymentId);
        }
    }

    public function fetchBySubscriptionIdEmailAndContactNotNull(string $subscriptionId)
    {
        $subscriptionId = Base\PublicEntity::stripDefaultSign($subscriptionId);

        $query = $this->newQuery();

        return $query
                    ->where(Entity::SUBSCRIPTION_ID, $subscriptionId)
                    ->whereNotNull(Entity::EMAIL)
                    ->whereNotNull(Entity::CONTACT)
                    ->where(function ($query) {
                        $query->where(Entity::RECURRING_TYPE, '=', 'initial')
                              ->orWhere(Entity::RECURRING_TYPE, '=', 'card_change');
                        })
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->first();
    }

    public function fetchLastNPaymentsForDowntime($from, $to, $type, $key, $value, $limit)
    {
        $paymentCreatedAtCol = $this->repo->payment->dbColumn(Payment\Entity::CREATED_AT);
        $paymentMerchantIdCol = $this->repo->payment->dbColumn(Payment\Entity::MERCHANT_ID);

        $connectionType = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT);

        $query = $this->newQueryWithConnection($connectionType)
                      ->select($paymentCreatedAtCol, Payment\Entity::AUTHORIZED_AT, Payment\Entity::STATUS, $paymentMerchantIdCol);

        $paymentCardIdCol = $this->dbColumn(Payment\Entity::CARD_ID);
        $cardIdCol = $this->repo->card->dbColumn(Card\Entity::ID);
        $query = $query->join(TABLE::CARD, $paymentCardIdCol, '=', $cardIdCol);

        if ($key == DowntimeDetection::ISSUER)
        {
            $query = $query->where(Card\Entity::ISSUER, $value);
        }
        else if ($key == DowntimeDetection::NETWORK)
        {
            $query = $query->where(Card\Entity::NETWORK, Card\Network::getFullName($value));
        }
        else
        {
            throw new Exception\LogicException(
                'key should be either issuer or network');
        }

        $paymentInternationalCol = $this->dbColumn(Payment\Entity::INTERNATIONAL);
        $query = $query->where(Payment\Entity::RECURRING, false)
                       ->where(function($query)
                                {
                                    $query->where(Payment\Entity::TWO_FACTOR_AUTH, '<>' , 'skipped')
                                          ->orWhereNull(Payment\Entity::TWO_FACTOR_AUTH);
                                })
                       ->where($paymentInternationalCol, false);

        if ((empty($from) == false) and
            (empty($to) == false))
        {
            $query = $query->whereBetween($paymentCreatedAtCol, array($from, $to));
        }
        else if (empty($from) == false)
        {
            $query = $query->where($paymentCreatedAtCol, '>' ,$from);
        }

        if ($type === DowntimeDetection::SUCCESS_RATE)
        {
            $query = $query->where( Payment\Entity::STATUS, '<>', Status::CREATED);
        }

        return $query->orderBy($paymentCreatedAtCol, 'desc')
                     ->limit($limit)
                     ->get();
    }

    public function fetchLastNUpiPaymentsForDowntime($from, $to, $type, $key, $value, $limit)
    {
        $connectionType = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT);

        $query = $this->newQueryWithConnection($connectionType)
            ->select(Payment\Entity::CREATED_AT, Payment\Entity::AUTHORIZED_AT, Payment\Entity::STATUS, Payment\Entity::MERCHANT_ID);

        $query = $query->where(Payment\Entity::METHOD, Method::UPI);

        if ($key == DowntimeDetection::PROVIDER)
        {
            $query = $query->where(Payment\Entity::VPA, 'like', '%@'.$value);
        }
        else
        {
            throw new Exception\LogicException(
                'key should be provider');
        }

        if ((empty($from) == false) and
            (empty($to) == false))
        {
            $query = $query->whereBetween(Payment\Entity::CREATED_AT, array($from, $to));
        }
        else if (empty($from) == false)
        {
            $query = $query->where(Payment\Entity::CREATED_AT, '>' ,$from);
        }

        if ($type === DowntimeDetection::SUCCESS_RATE)
        {
            $query = $query->where( Payment\Entity::STATUS, '<>', Status::CREATED);
        }

        return $query->orderBy(Payment\Entity::CREATED_AT, 'desc')
            ->limit($limit)
            ->get();
    }

    public function fetchLastNNetbankingPaymentsForDowntime($from, $to, $type, $key, $value, $limit)
    {
        $connectionType = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT);

        $query = $this->newQueryWithConnection($connectionType)
            ->select(Payment\Entity::CREATED_AT, Payment\Entity::AUTHORIZED_AT, Payment\Entity::STATUS, Payment\Entity::MERCHANT_ID);

        $query = $query->where(Payment\Entity::METHOD, Method::NETBANKING);

        if ($key == DowntimeDetection::BANK)
        {
            $query = $query->where(Payment\Entity::BANK, $value);
        }
        else
        {
            throw new Exception\LogicException(
                'key should be bank for netbanking');
        }

        if ((empty($from) == false) and
            (empty($to) == false))
        {
            $query = $query->whereBetween(Payment\Entity::CREATED_AT, array($from, $to));
        }
        else if (empty($from) == false)
        {
            $query = $query->where(Payment\Entity::CREATED_AT, '>' ,$from);
        }

        if ($type === DowntimeDetection::SUCCESS_RATE)
        {
            $query = $query->where( Payment\Entity::STATUS, '<>', Status::CREATED);
        }

        return $query->orderBy(Payment\Entity::CREATED_AT, 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Overriding newQuery to always have conditions for payment method
     * and bank in case of restricted orgs like SBI. For now this is a
     * very specific customization as we don't know how this may grow
     * further but once we have more clarity this can be made more generic
     * by having custom required conditions per restricted org defined
     * in Fetch.php file just like we do for search rules and accesses
     *
     * @return mixed
     */
    protected function newQuery()
    {
        $app = \App::getFacadeRoot();

        $orgId = $app['basicauth']->getOrgId();

        //
        // Skip further checks in case of auths/routes where this is not set.
        // This happens with routes like invoice_view_live/invoice_view_test.
        // We also need to revisit the checks later if restricted orgs ever onboard
        // merchants as we do not know if same restrictions will apply to them.
        if (empty($orgId) === true)
        {
            return parent::newQuery();
        }

        /** @var Org\Entity $org */
        $org = $this->repo->org->findOrFailPublic(Org\Entity::verifyIdAndSilentlyStripSign($orgId));

        $isRestricted = ($org->getType() === Org\Entity::RESTRICTED);

        if ($isRestricted === false)
        {
            return parent::newQuery();
        }

        return parent::newQuery()
                        ->where(Entity::METHOD, Method::NETBANKING)
                        ->where(Entity::BANK, $org->getCustomCode());
    }

    public function hasMerchantTransacted(string $merchantId)
    {
        try
        {
            $result = $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
                           ->from(\DB::raw('`payments` FORCE INDEX (payments_merchant_id_status_created_at_index_all_replicas)'))
                           ->where(Entity::MERCHANT_ID, "=", $merchantId)
                           ->where(Entity::BASE_AMOUNT, ">", 0)
                           ->whereIn(Entity::STATUS, [Status::CAPTURED, Status::AUTHORIZED])
                           ->limit(1)
                           ->get()
                           ->pluck(Entity::MERCHANT_ID)
                           ->toArray();;

            if (empty($result) === true)
            {
                return false;
            }

            return true;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::DB_QUERY_EXCEPTION);
        }

        return false;
    }

    public function findFirstDataAuthSeparatedPaymentIdsBetween(int $start, int $end)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->whereBetween(Entity::CREATED_AT, [$start, $end])
                    ->where(Entity::GATEWAY, '=', Gateway::FIRST_DATA)
                    ->where(Entity::AUTHENTICATION_GATEWAY, '=', Gateway::MPI_BLADE)
                    ->pluck(Entity::ID);
    }

    public function saveOrFail($payment, array $options = array())
    {
       if ($payment->isExternal() === false)
       {
          $emiPlan = $this->stripEmiRelation($payment);

          parent::saveOrFail($payment, $options);

          $this->addEmiRelationIfApplicable($payment, $emiPlan);

          return $payment;
        }

        $this->saveExternalEntity($payment);
    }

    public function save($payment, array $options = array())
    {
        if ($payment->isExternal() === false)
        {
            $emiPlan = $this->stripEmiRelation($payment);

            try
            {
                // Changed from save -> saveOrFail and ignoring exception as only saveOrFail is overridden as of now for dual write
                parent::saveOrFail($payment, $options);
            }
            catch (\Throwable $exception) {}

            $this->addEmiRelationIfApplicable($payment, $emiPlan);

            return $payment;
        }

        $this->saveExternalEntity($payment);
    }

    public function stripEmiRelation(& $payment)
    {
        $emiPlan = null;

        if ($payment->isEmi() &&
            $payment->emiPlan != null &&
            $payment->emiPlan->isExternal())
        {
            $emiPlan = $payment->emiPlan;

            $payment->emiPlan()->dissociate();

            //We should keep the plan id and just remove the relationship.
            $payment[Entity::EMI_PLAN_ID] = $emiPlan['id'];
        }

        return $emiPlan;
    }

    public function addEmiRelationIfApplicable(& $payment, $emiPlan)
    {
        if ($emiPlan != null)
        {
            $payment->emiPlan()->associate($emiPlan);
        }
    }

    public function getValidatePaymentsForPaymentPages(PaymentLink\Entity $paymentPage)
    {
        $timeStamp = Carbon::today(Timezone::IST)->subMinutes(45)->getTimestamp();

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->where(Entity::PAYMENT_LINK_ID, $paymentPage->getId())
                    ->where(Entity::MERCHANT_ID, $paymentPage->getMerchantId())
                    ->where(Entity::CREATED_AT, '>', $timeStamp)
                    ->whereIn(Entity::STATUS,[Status::CREATED, Status::AUTHORIZED])
                    ->get();
    }

    public function getCapturedPaymentsForPaymentPage(PaymentLink\Entity $paymentPage)
    {
        $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_MERCHANT);

        $query = $this->newQueryWithConnection($connectionType);

        return $query
                    ->where(Entity::PAYMENT_LINK_ID, $paymentPage->getId())
                    ->where(Entity::MERCHANT_ID, $paymentPage->getMerchantId())
                    ->whereIn(Entity::STATUS, [Status::CAPTURED, Status::REFUNDED])
                    ->count();
    }

    public function getPaymentsSortedByCreatedAt(array $ids)
    {
        $payments = $this->newQuery()
                         ->whereIn(Entity::ID, $ids)
                         ->orderBy(Entity::CREATED_AT, 'desc')
                         ->get();

        if (count($payments) === count($ids))
        {
            return $payments;
        }

        $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_MERCHANT);

        $warmPayments = $this->newQueryWithConnection($connectionType)
                             ->whereIn(Entity::ID, $ids)
                             ->orderBy(Entity::CREATED_AT, 'desc')
                             ->get();

        return $this->mergeCollectionsBasedOnKey($payments, $warmPayments, Entity::ID);
    }

    public function isNewCustomerToMerchant($merchantId, $contact): bool
    {
        $minCreatedAt = Carbon::now()->subSeconds(self::SECONDS_IN_A_YEAR)->getTimestamp();

        $startTime = millitime();

        // check number of successful payments in last 12 months by contact.
        $userPastPayments = $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
                    ->select(DB::raw("/*+ MAX_EXECUTION_TIME(200) */ `id`"))
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::CONTACT, $contact)
                    ->where(Entity::CREATED_AT, '>=', $minCreatedAt)
                    ->limit(1)
                    ->get();

        $userPastPayments = $userPastPayments->toArray();

        $this->trace->info(TraceCode::RTB_NEW_CUSTOMER_QUERY_TIME,
            [
                'timeTaken' => millitime() - $startTime,
                'count'     => count($userPastPayments),
                'isNewUser' => empty($userPastPayments),
            ]
        );

        return empty($userPastPayments);
    }

    protected function traceBeforeReturnFromFetchPaymentWithForceIndex($startTimeMs, $connectionForTrace, $didUseElasticSearch = false)
    {
        $endTimeMs = round(microtime(true) * 1000);

        $queryDuration = $endTimeMs - $startTimeMs;

        $this->trace->info(TraceCode::TRACE_BEFORE_RETURN_FROM_FETCH_PAYMENTS_WITH_INDEX, [
            'duration'          => $queryDuration,
            'connection'        => $connectionForTrace,
            'did_use_elastic'   => $didUseElasticSearch,
        ]);
    }

    public function reload(&$payment)
    {
        if ($payment->isExternal() === false)
        {
            $reloadedEntity = $this->findOrFailArchived($payment->getKey());

            $attributes = $reloadedEntity->getAttributes();

            $payment->setRawAttributes($attributes, true);

            return $payment;
        }

        return $payment;
    }

    public function lockForUpdateAndReload($paymentEntity, bool $withTrashed = false)
    {
        if ($paymentEntity->isExternal() === true)
        {
            return;
        }

        parent::lockForUpdateAndReload($paymentEntity, $withTrashed);
    }

    public function fetchInitialPaymentIdForToken($tokenId, $merchantId)
    {
        $payment = $this->newQueryWithConnection($this->getSlaveConnection())
                        ->where(Entity::TOKEN_ID, '=', $tokenId)
                        ->whereIn(Payment\Entity::RECURRING_TYPE, ['initial', 'card_change'])
                        ->where(Payment\Entity::MERCHANT_ID, $merchantId)
                        ->whereIn(Payment\Entity::STATUS, [Status::CAPTURED, Status::REFUNDED])
                        ->first();

        if (empty($payment) === false)
        {
            return $payment;
        }

        $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);

        return $this->newQueryWithConnection($connectionType)
                        ->where(Entity::TOKEN_ID, '=', $tokenId)
                        ->whereIn(Payment\Entity::RECURRING_TYPE, ['initial', 'card_change'])
                        ->where(Payment\Entity::MERCHANT_ID, $merchantId)
                        ->whereIn(Payment\Entity::STATUS, [Status::CAPTURED, Status::REFUNDED])
                        ->first();
    }

    public function getRecurringInitialPayment(string $tokenId, string $merchantId, string $method)
    {
        $payment = $this->newQueryWithConnection($this->getSlaveConnection())
                        ->where(Entity::TOKEN_ID, $tokenId)
                        ->where(Entity::MERCHANT_ID, $merchantId)
                        ->where(Entity::METHOD, $method)
                        ->where(Payment\Entity::RECURRING_TYPE, '=', 'initial')
                        ->where(Entity::STATUS, Status::AUTHORIZED)
                        ->first();

        if (empty($payment) === false)
        {
            return $payment;
        }

        $connectionType = $this->getDataWarehouseSourceAPIConnection(ConnectionType::DATA_WAREHOUSE_ADMIN);

        $payment = $this->newQueryWithConnection($connectionType)
                        ->where(Entity::TOKEN_ID, $tokenId)
                        ->where(Entity::MERCHANT_ID, $merchantId)
                        ->where(Entity::METHOD, $method)
                        ->where(Payment\Entity::RECURRING_TYPE, '=', 'initial')
                        ->where(Entity::STATUS, Status::AUTHORIZED)
                        ->first();

        return $payment;
    }

    public function getPaymentsWithReferenceId($gateway, $status, $limit)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::GATEWAY, $gateway)
            ->status($status)
            ->whereNotNull(Entity::REFERENCE2)
            ->whereNull(Entity::REFERENCE16)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->limit($limit)
            ->get();
    }

    public function getPaymentsWithoutReferenceId($gateway,
                                                  $status,
                                                  $method = '',
                                                  $paymentIds = [],
                                                  $includeMerchantList = [],
                                                  $excludeMerchantList = [],
                                                  $limit = 0,
                                                  $offset = 0)
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->where(Entity::GATEWAY, $gateway)
                      ->where(Entity::METHOD, $method)
                      ->status($status);

        // run cron for specific merchants
        if (empty($includeMerchantList) === false)
        {
            $query = $query->whereIn(Entity::MERCHANT_ID, $includeMerchantList);
        }

        // do not run cron for specific merchants
        if (empty($excludeMerchantList) === false)
        {
            $query = $query->whereNotIn(Entity::MERCHANT_ID, $excludeMerchantList);
        }

        // run cron for specific payment
        if (empty($paymentIds) === false)
        {
            $query = $query->whereIn(Entity::ID, $paymentIds);
        }
        else
        {
            $query = $query->whereNull(Entity::REFERENCE2);
        }

        $query = $query->orderBy(Payment\Entity::CREATED_AT, 'desc');

        if ($limit > 0)
        {
            $query = $query->limit($limit);
        }

        if ($offset > 0)
        {
            $query = $query->offset($offset);
        }

        return $query->get();
    }

    //select * from `payments`
    // where `payments`.`token_id` = JtXT7fDRwqDzP3
    // and `payments`.`token_id` is not null
    // and `method` = nach
    // limit 5

    public function getPaymentCountByToken($tokenId)
    {
        return $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
            ->select($this->dbColumn('*'))
            ->where(Payment\Entity::TOKEN_ID, '=', $tokenId)
            ->WhereNotNull(Payment\Entity::TOKEN_ID)
            ->where(Payment\Entity::METHOD, Payment\Method::NACH)
            ->limit(5);
    }

    public function fetchPaymentCountByTokenForCardInRange($tokenId, $start, $end)
    {
        return $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
            ->select($this->dbColumn('*'))
            ->where(Payment\Entity::TOKEN_ID, '=', $tokenId)
            ->where(Payment\Entity::METHOD, '=', Method::CARD)
            ->where(Payment\Entity::RECURRING_TYPE, '=', 'auto')
            ->where(Payment\Entity::CREATED_AT, '>', $start)
            ->where(Payment\Entity::CREATED_AT, '<', $end)
            ->where(Payment\Entity::STATUS, '!=', 'failed')
            ->count();
    }

    public function getDualWriteMismatchPayments(int $from, int $to): array
    {
        $query = sprintf(
                    "WITH pids AS
                              (SELECT id AS pid FROM payments WHERE updated_at >= %s AND updated_at < %s)
                            SELECT id FROM
                                (SELECT id, count(*) AS cnt FROM (
                                    SELECT * FROM pids LEFT JOIN payments ON pid = id UNION
                                    SELECT * FROM pids LEFT JOIN payments_new ON pid = id)
                                AS payments_union GROUP BY id)
                            AS agg_payments WHERE cnt > 1;",
                    $from, $to);

        $payments = DB::connection($this->getPaymentFetchReplicaConnection())->select(DB::RAW($query));

        $paymentIds = [];

        foreach ($payments as $payment)
        {
            $paymentIds[] = $payment->id;
        }

        return $paymentIds;
    }

    public function fetchPaymentsByContacts(array $contacts, int $skip, int $count) : Base\PublicCollection
    {
        $nowMinus6Months = Carbon::now()->subMonths(6)->getTimestamp();

        return $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
            ->whereIn(Entity::CONTACT, $contacts)
            ->where(Entity::CREATED_AT, '>=', $nowMinus6Months)
            ->with(['merchant', 'refunds'])
            ->skip($skip)
            ->take($count)
            ->latest()
            ->get();
    }

    public function fetchDebitEmiPaymentsWithRelationsBetween($from, $to, $bank,$gateway)
    {
        $tRepo = $this->repo->terminal;

        $tTableName = $tRepo->getTableName();

        $terminalEmi = $tRepo->dbColumn(Terminal\Entity::EMI);

        $paymentTerminalId = $this->dbColumn(Entity::TERMINAL_ID);

        $terminalGateway = $tRepo->dbColumn(Terminal\Entity::GATEWAY);

        $paymentData = $this->dbColumn('*');

        $terminalId = $tRepo->dbColumn(Terminal\Entity::ID);

        $paymentStatus = $this->dbColumn(Entity::STATUS);

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->join($tTableName, $paymentTerminalId, '=', $terminalId)
            ->whereBetween(Entity::AUTHORIZED_AT, [$from, $to])
            ->whereIn($paymentStatus, [Status::CAPTURED, Status::REFUNDED, Status::AUTHORIZED])
            ->where(Entity::BANK, '=', $bank)
            ->where(Entity::METHOD, '=', Method::EMI)
            ->where($terminalGateway, '=', $gateway)
            ->where($terminalEmi, '=', true)
            ->with('card.globalCard', 'emiPlan', 'merchant', 'terminal')
            ->select($paymentData)
            ->get();
    }

    private function getWdaConnectionType(string $connection)
    {
        if($connection === Connection::DATA_WAREHOUSE_ADMIN_TEST or $connection === Connection::DATA_WAREHOUSE_ADMIN_LIVE)
        {
            return ConnectionType::DATA_WAREHOUSE_ADMIN;
        }
        else if($connection === Connection::DATA_WAREHOUSE_MERCHANT_TEST or $connection === Connection::DATA_WAREHOUSE_MERCHANT_LIVE)
        {
            return ConnectionType::DATA_WAREHOUSE_MERCHANT;
        }
        else
        {
           return  ConnectionType::REPLICA;
        }
    }

    public function getDisputeGateway($disputeId)
    {
        $pid = $this->dbColumn(Payment\Entity::ID);
        $disputePaymentIdColumn = $this->repo->dispute->dbColumn(\RZP\Models\Dispute\Entity::PAYMENT_ID);
        $disputeIdColumn = $this->repo->dispute->dbColumn(\RZP\Models\Dispute\Entity::ID);

        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->select(\RZP\Models\Payment\Entity::GATEWAY)
            ->join(Table::DISPUTE, $pid, '=', $disputePaymentIdColumn)
            ->where($disputeIdColumn, '=', $disputeId)
            ->pluck(\RZP\Models\Payment\Entity::GATEWAY);
    }
}

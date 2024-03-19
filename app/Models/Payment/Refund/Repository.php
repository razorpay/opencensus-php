<?php

namespace RZP\Models\Payment\Refund;

use Database\Connection;
use DB;
use Carbon\Carbon;

use Razorpay\Trace\Logger as Trace;
use RZP\Exception;
use RZP\Http\Route;
use RZP\Models\Base;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Feature;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Card;

use RZP\Models\Payment\Status;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use RZP\Base\ConnectionType;
use RZP\Constants\Mode;
use RZP\Constants\Table;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Refund;
use RZP\Gateway\Wallet\Freecharge;
use RZP\Constants\Entity as EntityConstants;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;
use RZP\Models\Reversal\Entity as ReversalEntity;
use RZP\Gateway\Wallet\Base\Entity as WalletEntity;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Models\Base\Traits\ExternalScroogeRepo;
use RZP\Trace\TraceCode;

class Repository extends Base\Repository
{
    use ExternalScroogeRepo;
    use ScroogeRepo;
    protected $entity = 'refund';

    protected $entityFetchParamRules = [
        Entity::PAYMENT_ID    => 'sometimes|alpha_dash|min:14|max:18',
        Entity::PUBLIC_STATUS => 'sometimes|filled|in:processed,processing,failed',
        Entity::NOTES         => 'sometimes|notes_fetch',
    ];

    // These are proxy allowed params to search on.
    protected $proxyFetchParamRules = [
        Entity::NOTES                       => 'sometimes|string|max:500',
        Entity::REVERSAL_ID                 => 'filled|public_id|size:18',
        self::EXPAND . '.*'                 => 'filled|string|in:reversal,transaction,transaction.settlement|custom:expand',
        ReversalEntity::INITIATOR_ID        => 'sometimes|string|min:14|max:18',
        ReversalEntity::CUSTOMER_REFUND_ID  => 'filled|string|size:19',
        Entity::TERMINAL_ID                 => 'sometimes|alpha_dash|min:14|max:18',
        Entity::SETTLED_BY                  => 'sometimes|string'
    ];

    protected $appFetchParamRules = [
        Entity::AMOUNT          => 'sometimes|integer',
        Entity::MERCHANT_ID     => 'sometimes|alpha_dash',
        Entity::TRANSACTION_ID  => 'sometimes|alpha_dash|min:14|max:18',
        Entity::BATCH_ID        => 'sometimes|alpha_dash|min:14|max:20',
        Entity::NOTES           => 'sometimes|notes_fetch',
        Entity::STATUS          => 'sometimes|string|max:30',
        Entity::GATEWAY         => 'sometimes|string|max:30',
        Payment\Entity::METHOD  => 'sometimes|string|max:30',
        'payment_gateway'       => 'sometimes|string|max:30',
    ];

    protected $signedIds = [
        Entity::BATCH_ID,
        Entity::PAYMENT_ID,
        Entity::TRANSACTION_ID,
        Entity::REVERSAL_ID,
    ];

    /**
     * This validates the expand route to allow reversal expand only for linked account merchants
     * @param $attribute
     * @param $value
     *
     * @throws \RZP\Exception\ExtraFieldsException
     */
    protected function validateExpand($attribute, $value)
    {
        if ((optional($this->merchant)->isLinkedAccount() === false) and
            $value === EntityConstants::REVERSAL)
        {
            throw new Exception\ExtraFieldsException('expand=reversal');
        }
    }

    public function fetchEmiRefundsWithCardTerminalsBetween($from, $to, $bank, $type = 'credit')
    {

        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchEmiRefundsWithCardTerminalsBetween',
            'route'        => $this->route
        ]);

        $tRepo = $this->repo->terminal;

        $cRepo = $this->repo->card;

        $paymentRepo = $this->repo->payment;

        $tTableName = $tRepo->getTableName();

        $cardTableName = $cRepo->getTableName();

        $pTableName = $paymentRepo->getTableName();

        $terminalEmi = $tRepo->dbColumn(Terminal\Entity::EMI);

        $paymentId = $paymentRepo->dbColumn(Payment\Entity::ID);

        $paymentTerminalId = $paymentRepo->dbColumn(Payment\Entity::TERMINAL_ID);

        $paymentCardIdCol = $paymentRepo->dbColumn(Payment\Entity::CARD_ID);

        $refundData = $this->dbColumn('*');

        $terminalId = $tRepo->dbColumn(Terminal\Entity::ID);

        $cardIdCol = $cRepo->dbColumn(Card\Entity::ID);

        $cardType = $cRepo->dbColumn(Card\Entity::TYPE);

        $paymentStatus = $paymentRepo->dbColumn(Payment\Entity::STATUS);

        $paymentBank = $paymentRepo->dbColumn(Payment\Entity::BANK);
        $paymentMethod = $paymentRepo->dbColumn(Payment\Entity::METHOD);
        $refundCreatedAt = $this->dbColumn(Entity::CREATED_AT);

        $refunds = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->join($pTableName, $paymentId, '=', Refund\Entity::PAYMENT_ID)
            ->join($tTableName, $paymentTerminalId, '=', $terminalId)
            ->join($cardTableName, $paymentCardIdCol, '=', $cardIdCol)
            ->whereBetween($refundCreatedAt, [$from, $to])
            ->where($paymentStatus, '=', Payment\Status::REFUNDED)
            ->where($paymentBank, '=', $bank)
            ->where($paymentMethod, '=', Payment\Method::EMI)
            ->where($terminalEmi, '=', false)
            ->where($cardType, '=' ,$type)
            ->with('payment', 'payment.card.globalCard', 'payment.emiPlan', 'payment.merchant')
            ->select($refundData)
            ->get();

        if($this->repo->refund->isScroogeReadMigrationEnabledTidb() == true){
            $refundstidb = $this->repo->refund_tidb->fetchEmiRefundsWithCardTerminalsBetweenFromTidb($from,$to,$bank,$type);

            (new Service())->compareRefundsAndLogDifference(
                $refunds->all(), $refundstidb->all(), [
                'method_name' => __FUNCTION__,
                'type' => TraceCode::SCROOGE_MISC_QUERIES_MIGRATION_TIDB,
            ]);

            if($this->repo->refund->isScroogeReadMigrationTidb() == true){
                return $refundstidb;
            }
        }

        if($this->repo->terminal->isTerminalsTidbReadMigrationEnabled(__FUNCTION__) === true)
        {
            $refunds = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
                ->join($pTableName, $paymentId, '=', Refund\Entity::PAYMENT_ID)
                ->join(Terminal\Constants::TS_TIDB_TABLE, $paymentTerminalId, '=', Terminal\Constants::TS_TERMINAL_ID)
                ->join($cardTableName, $paymentCardIdCol, '=', $cardIdCol)
                ->whereBetween($refundCreatedAt, [$from, $to])
                ->where($paymentStatus, '=', Payment\Status::REFUNDED)
                ->where($paymentBank, '=', $bank)
                ->where($paymentMethod, '=', Payment\Method::EMI)
                ->whereRaw('JSON_CONTAINS( ' . Terminal\Constants::TS_METHODS . ', \'["' . Payment\Method::EMI . '"]\')'.'= false')
                ->whereNull(Terminal\Constants::TS_DELETED_AT)
                ->where($cardType, '=' ,$type)
                ->with('payment', 'payment.card.globalCard', 'payment.emiPlan', 'payment.merchant')
                ->select($refundData)
                ->get();
        } else {
            (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);
        }

        return $refunds;
    }

    public function fetchCardRefundsForMerchantAndGatewayBetween($from, $to, $merchantIds)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchCardRefundsForMerchantAndGatewayBetween',
            'route'        => $this->route
        ]);
        $paymentRepo = $this->repo->payment;

        $pTableName = $paymentRepo->getTableName();

        $paymentId = $paymentRepo->dbColumn(Payment\Entity::ID);

        $refundData = $this->dbColumn('*');

        $paymentGateway = $paymentRepo->dbColumn(Payment\Entity::GATEWAY);
        $paymentMethod = $paymentRepo->dbColumn(Payment\Entity::METHOD);
        $refundProcessedAt = $this->dbColumn(Entity::PROCESSED_AT);

        $paymentMerchantId = $this->dbColumn(Entity::MERCHANT_ID);

        $refunds= $this->newQueryWithConnection($this->getDataWarehouseConnection())
            ->join($pTableName, $paymentId, '=', Refund\Entity::PAYMENT_ID)
            ->whereBetween($refundProcessedAt, [$from, $to])
            ->whereIn( $paymentMerchantId , $merchantIds)
            ->where($paymentMethod, '=', Payment\Method::CARD)
            ->where($paymentGateway, '=', 'cybersource')
            ->with('payment', 'payment.card.globalCard', 'payment.terminal')
            ->orderBy($this->dbColumn(Refund\Entity::PROCESSED_AT), 'desc')
            ->select($refundData)
            ->get();


        try{
        if($this->repo->refund->isScroogeReadMigrationEnabledTidb() == true) {
            $refundstidb = $this->repo->refund_tidb->fetchCardRefundsForMerchantAndGatewayBetweenFromTidb($from, $to, $merchantIds);

            (new Service())->compareRefundsAndLogDifference(
                $refunds, $refundstidb, [
                'method_name' => __FUNCTION__,
                'type' => TraceCode::SCROOGE_MISC_QUERIES_MIGRATION_TIDB_FETCH_CARDS,
            ]);

            if ($this->repo->refund->isScroogeReadMigrationTidbForFetchCards() == true) {
                return $refundstidb;
            }
            return $refunds;

        }
        }catch(\Throwable $ex){
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::SCROOGE_ENTITY_FETCH_FAILURE,
                [   'from' => $from,
                    'to'=>$to,
                    'merchantIds' => $merchantIds,
                ]);
            return $refunds;
        }
        return $refunds;

    }

    protected function addQueryParamInitiatorId($query, $params)
    {
        $dbAttr = $this->repo->reversal->dbColumn(ReversalEntity::INITIATOR_ID);

        $query->whereHas(EntityConstants::REVERSAL, function($query) use ($params, $dbAttr) {
            $query->where($dbAttr, $params[ReversalEntity::INITIATOR_ID]);
        });
    }

    protected function addQueryParamCustomerRefundId($query, $params)
    {
        $dbAttr = $this->repo->reversal->dbColumn(ReversalEntity::CUSTOMER_REFUND_ID);

        Refund\Entity::verifyIdAndSilentlyStripSign($params[ReversalEntity::CUSTOMER_REFUND_ID]);

        $query->whereHas(EntityConstants::REVERSAL, function($query) use ($params, $dbAttr) {
            $query->where($dbAttr, $params[ReversalEntity::CUSTOMER_REFUND_ID]);
        });
    }

    protected function addQueryParamGateway($query, $params)
    {
        $gateway = $params[Refund\Entity::GATEWAY];

        $refundGateway = $this->dbColumn(Refund\Entity::GATEWAY);

        Payment\Gateway::validateGateway($gateway);

        $query->where($refundGateway, '=', $gateway);
    }

    protected function addQueryParamPublicStatus($query, $params)
    {
        // We are disabling filtering for merchants like flipkart for which we are
        // modifying public status based on some buisness logics and is not stored in API DB

        $disableStatusFilter =  $this->merchant->isFeatureEnabled(Feature\Constants::SHOW_REFUND_PUBLIC_STATUS);

        if ($disableStatusFilter === true)
        {
            return;
        }

        $showApiRefundStatus = $this->merchant->isFeatureEnabled(Feature\Constants::REFUND_PENDING_STATUS);;

        switch($params[Entity::PUBLIC_STATUS])
        {
            case Refund\Status::PROCESSED:
                ($showApiRefundStatus === true) ?
                    $query->where(Entity::STATUS, '=', Refund\Status::PROCESSED) : $query->whereNotNull(Entity::SPEED_PROCESSED);

                break;

            case Refund\Status::PROCESSING:
                ($showApiRefundStatus === true) ?
                    $query->whereIn(Entity::STATUS, [Refund\Status::CREATED, Refund\Status::INITIATED]) : $query->whereNull(Entity::SPEED_PROCESSED);

                break;

            case Refund\Status::FAILED:
                $query->where(Entity::STATUS, '=', Refund\Status::REVERSED);

                break;
        }
    }

    public function findOrFailPublicByParams($id, $merchantId, $paymentId = null)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'findOrFailPublicByParams',
            'route'        => $this->route
        ]);
        $query = $this->newQuery()->where(Refund\Entity::MERCHANT_ID, '=', $merchantId);

        if ($paymentId !== null)
        {
            $query->where(Refund\Entity::PAYMENT_ID, '=', $paymentId);
        }

        return $query->findOrFailPublic($id);
    }

    public function findForPaymentAndMerchant(Payment\Entity $payment, Merchant\Entity $merchant)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'findForPaymentAndMerchant',
            'route'        => $this->route
        ]);
        return $this->newQuery()
                    ->where(Refund\Entity::PAYMENT_ID, '=', $payment->getId())
                    ->merchantId($merchant->getId())
                    ->get();
    }

    public function findForPayment(Payment\Entity $payment)
    {
        return $this->findForPaymentId($payment->getId());
    }

    public function findForPaymentIdFromAPI(string $paymentId)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'findForPaymentIdFromAPI',
            'route'        => $this->route
        ]);
        return $this->newQuery()
                    ->where(Refund\Entity::PAYMENT_ID, '=', $paymentId)
                    ->get();
    }

    public function findByPublicIdFromAPI(string $publicRefundId)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'findByPublicIdFromAPI',
            'route' => $this->route
        ]);

        $id = Refund\Entity::verifyIdAndStripSign($publicRefundId);

        return $this->newQuery()
            ->where(Refund\Entity::ID, '=', $id)
            ->first();
    }

    public function fetchFirstForPaymentId(string $paymentId)
    {

        if ($this->isScroogeReadMigrationEnabled() === true) {
            return $this->fetchFirstRefundByPayment($paymentId);
        }
        return $this->fetchFirstForPaymentIdFromApi($paymentId);
    }

    public function fetchFirstForPaymentIdFromApi(string $paymentId)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchFirstForPaymentId',
            'route'        => $this->route
        ]);
        return $this->newQuery()
            ->where(Refund\Entity::PAYMENT_ID, '=', $paymentId)
            ->first();
    }

    public function findBetweenTimestamps($from, $to)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'findBetweenTimestamps',
            'route'        => $this->route
        ]);
        return $this->newQuery()
                    ->where(Refund\Entity::CREATED_AT, '>=', $from)
                    ->where(Refund\Entity::CREATED_AT, '<=', $to)
                    ->get();
    }

    public function findBetweenTimestampsForGateway($from, $to, $gateway)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'findBetweenTimestampsForGateway',
            'route'        => $this->route
        ]);
        return $this->newQuery()
                    ->where('refunds.created_at', '>=', $from)
                    ->where('refunds.created_at', '<=', $to)
                    ->where('refunds.gateway', '=', $gateway)
                    ->get();
    }

    public function getRefundedAmountByGateway(string $gateway, int $from, int $to)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'getRefundedAmountByGateway',
            'route'        => $this->route
        ]);
        $refundPaymentId = $this->dbColumn(Entity::PAYMENT_ID);
        $refundAmount = $this->dbColumn(Entity::BASE_AMOUNT);
        $refundCreatedAt = $this->dbColumn(Entity::CREATED_AT);
        $refundGateway = $this->dbColumn(Entity::GATEWAY);

        $paymentId = $this->repo->payment->dbColumn(Payment\Entity::ID);
        $paymentCapturedAt = $this->repo->payment->dbColumn(Payment\Entity::CAPTURED_AT);

        return $this->newQuery()
                    ->join(Table::PAYMENT, $refundPaymentId, '=', $paymentId)
                    ->where($refundGateway, '=', $gateway)
                    ->whereNotNull($paymentCapturedAt)
                    ->whereBetween($refundCreatedAt, [$from, $to])
                    ->sum($refundAmount);
    }

    public function fetchByIdPaymentIdMerchantId($id, $paymentId, $merchantId)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchByIdPaymentIdMerchantId',
            'route'        => $this->route
        ]);
        return $this->newQuery()
                    ->where(Refund\Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Refund\Entity::MERCHANT_ID, '=', $merchantId)
                    ->findOrFailPublic($id);
    }

    /**
     * @param string $reversalId
     * @param string $accountId
     * @param array  $relations
     *
     * @return \RZP\Models\Payment\Refund\Entity
     */
    public function findByReversalIdAndMerchant(
                                            string $reversalId,
                                            string $accountId,
                                            array $relations = []): Refund\Entity
    {
        if ($this->isScroogeReadMigrationEnabled() === true) {
            return $this->fetchRefundByReversalIdAndMerchant($reversalId, $accountId, $relations);
        }
        return $this->findByReversalIdAndMerchantFromApi($reversalId, $accountId,$relations);
    }

    public function findByReversalIdAndMerchantFromApi(
        string $reversalId,
        string $accountId,
        array $relations = []): Refund\Entity
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'findByReversalIdAndMerchant',
            'route'        => $this->route
        ]);
        return $this->newQuery()
            ->where(Entity::REVERSAL_ID, $reversalId)
            ->merchantId($accountId)
            ->with($relations)
            ->firstOrFailPublic();
    }

    public function findForPaymentAndAmount($paymentId, $amount)
    {
        if ($this->isScroogeReadMigrationEnabled() === true) {
            return $this->findForPaymentIdAndAmount($paymentId, $amount);
        }
        return $this->findForPaymentAndAmountFromApi($paymentId,$amount);
    }

    public function findForPaymentAndAmountFromApi($paymentId, $amount)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'findForPaymentAndAmountFromApi',
            'route'        => $this->route,
            'payment_id'   => $paymentId,
            'amount'   => $amount
        ]);
        return $this->newQuery()
            ->where(Refund\Entity::PAYMENT_ID, $paymentId)
            ->where(Refund\Entity::AMOUNT, $amount)
            ->get();
    }

    public function findForPaymentAndBaseAmount($paymentId, $baseAmount)
    {
        if ($this->isScroogeReadMigrationEnabled() === true) {
            return $this->fetchRefundByPaymentAndBaseAmount($paymentId,$baseAmount);
        }
        return $this->findForPaymentAndBaseAmountFromApi($paymentId,$baseAmount);
    }

    public function findForPaymentAndBaseAmountFromApi($paymentId, $amount)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'findForPaymentAndBaseAmount',
            'route'        => $this->route
        ]);
        return $this->newQuery()
            ->where(Refund\Entity::PAYMENT_ID, $paymentId)
            ->where(Refund\Entity::BASE_AMOUNT, $amount)
            ->get();
    }

    public function fetchEntitiesForReport($merchantId, $from, $to, $count, $skip, $relations = [])
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchEntitiesForReport',
            'route'        => $this->route
        ]);
        return $this->fetchBetweenTimestampWithRelations(
                        $merchantId, $from, $to, $count, $skip, $relations);
    }

    public function fetchRefundSummaryBetweenTimestamp($from, $to)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchRefundSummaryBetweenTimestamp',
            'route'        => $this->route
        ]);
        return $this->newQuery()
                    ->whereBetween(Entity::CREATED_AT, [$from, $to])
                    ->groupBy(Entity::MERCHANT_ID)
                    ->selectRaw(Entity::MERCHANT_ID . ','.
                       'SUM(' . Entity::BASE_AMOUNT . ') AS sum' . ','.
                       'COUNT(*) AS count')
                    ->get();
    }

    /**
     * Fetches all refunds which have no transactions, but the
     * corresponding payments have transactions.
     * This should ideally always return an empty collection.
     *
     * @return array
     */
    public function fetchRefundsWithoutTransactionsAndWithPaymentTransactions()
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchRefundsWithoutTransactionsAndWithPaymentTransactions',
            'route'        => $this->route
        ]);
        return $this->newQuery()
                    ->join(
                                Table::PAYMENT,
                                Table::REFUND . '.' . Refund\Entity::PAYMENT_ID,
                                '=',
                                Table::PAYMENT . '.' . Payment\Entity::ID)
                    ->select(Table::REFUND . '.*')
                    ->whereNull(Table::REFUND . '.' . Refund\Entity::TRANSACTION_ID)
                    ->whereNotNull(Table::PAYMENT . '.' . Payment\Entity::TRANSACTION_ID)
                    ->with('payment', 'merchant')
                    ->get();
    }

    /**
     * Fetches payment details along with refund using refund ID
     *
     * @param $refundId
     * @return mixed
     */
    public function fetchRefundByRefundIdsFromApi($refundIds)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchRefundByRefundIds',
            'route'        => $this->route
        ]);
        return $this->newQuery()
                    ->select(Table::REFUND. '.' . Refund\Entity::PAYMENT_ID,
                             Table::REFUND. '.' . Refund\Entity::REFERENCE1,
                             Table::REFUND. '.' . Refund\Entity::ID)
                    ->whereIn(Table::REFUND. '.' . Refund\Entity::ID, $refundIds)
                    ->get();
    }
    public function fetchRefundByRefundIds(array $refundIds)
    {
        if ($this->isScroogeReadMigrationEnabled() === true) {
            return $this->findRefundByIds($refundIds);
        }
        return $this->fetchRefundByRefundIdsFromApi($refundIds);
    }

    public function fetchRefundsForGatewayBetweenTimestamps($type, $gatewayCode, $from, $to, $gateway)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchRefundsForGatewayBetweenTimestamps',
            'route'        => $this->route
        ]);
        $attrs = $this->dbColumn('*');

        $query = $this->newQuery();

        $refunds = $query->select($attrs)->join(
            $this->repo->payment->getTableName(),
            function ($join) use ($from, $to, $type, $gatewayCode, $gateway)
            {
                $rPaymentId = $this->dbColumn(Refund\Entity::PAYMENT_ID);
                $rCreatedAt = $this->dbColumn(Refund\Entity::CREATED_AT);
                $rBaseAmount = $this->dbColumn(Refund\Entity::BASE_AMOUNT);
                $rGateway = $this->dbColumn(Refund\Entity::GATEWAY);

                $pRepo = $this->repo->payment;
                $pId = $pRepo->dbColumn(Payment\Entity::ID);
                $pType = $pRepo->dbColumn($type);

                $join->on($rPaymentId, '=', $pId)
                     ->where($rCreatedAt, '>=', $from)
                     ->where($rCreatedAt, '<=', $to)
                     ->where($pType, '=', $gatewayCode)
                     ->where($rGateway, '=', $gateway)
                     ->where($rBaseAmount, '!=', 0);
            })
            ->with('payment')
            ->get();

        return $refunds;
    }

    public function fetchRefundsForGatewaysBetweenTimestamps($type, $gatewayCodes, $from, $to, $gateway)
    {
        if ($this->isScroogeReadMigrationForGateways() === true) {
            return $this->compareAndFetchRefundsForGatewaysBetweenTimestampsFromTidb($type, $gatewayCodes, $from, $to, $gateway);
        }
        return $this->fetchRefundsForGatewaysBetweenTimestampsFromApi($type, $gatewayCodes, $from, $to, $gateway);
    }

    public function fetchRefundsForGatewaysBetweenTimestampsFromApi($type, $gatewayCodes, $from, $to, $gateway)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchRefundsForGatewaysBetweenTimestamps',
            'route'        => $this->route
        ]);
        $attrs = $this->dbColumn('*');

        // replication lag threshold of 5 minutes
        $query = $this->newQueryOnSlave(300000);

        $refunds = $query->select($attrs)->join(
            $this->repo->payment->getTableName(),
            function ($join) use ($from, $to, $type, $gatewayCodes, $gateway)
            {
                $rPaymentId = $this->dbColumn(Refund\Entity::PAYMENT_ID);
                $rCreatedAt = $this->dbColumn(Refund\Entity::CREATED_AT);
                $rBaseAmount = $this->dbColumn(Refund\Entity::BASE_AMOUNT);
                $rGateway = $this->dbColumn(Refund\Entity::GATEWAY);

                $pRepo        = $this->repo->payment;
                $pId          = $pRepo->dbColumn(Payment\Entity::ID);
                $pType        = $pRepo->dbColumn($type);
                $gatewayCodes = (array) $gatewayCodes;

                $join->on($rPaymentId, '=', $pId)
                     ->where($rCreatedAt, '>=', $from)
                     ->where($rCreatedAt, '<=', $to)
                     ->whereIn($pType, $gatewayCodes)
                     ->where($rGateway, '=', $gateway)
                     ->where($rBaseAmount, '!=', 0);
            })
            ->with('payment')
            ->get();

        return $refunds;
    }

    public function fetchRefundsForMethodGatewaysBetweenTimestamps(
        $type,
        $gatewayCodes,
        $from,
        $to,
        $gateway,
        $method = 'netbanking')
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchRefundsForMethodGatewaysBetweenTimestamps',
            'route'        => $this->route
        ]);
        $attrs = $this->dbColumn('*');

        $query = $this->newQuery();

        $refunds = $query->select($attrs)->join(
            $this->repo->payment->getTableName(),
            function ($join) use ($from, $to, $type, $gatewayCodes, $gateway, $method)
            {
                $rPaymentId = $this->dbColumn(Refund\Entity::PAYMENT_ID);
                $rCreatedAt = $this->dbColumn(Refund\Entity::CREATED_AT);
                $rBaseAmount = $this->dbColumn(Refund\Entity::BASE_AMOUNT);
                $rGateway = $this->dbColumn(Refund\Entity::GATEWAY);

                $pRepo        = $this->repo->payment;
                $pId          = $pRepo->dbColumn(Payment\Entity::ID);
                $pType        = $pRepo->dbColumn($type);
                $pMethod      = $pRepo->dbColumn(Payment\Entity::METHOD);
                $gatewayCodes = (array) $gatewayCodes;

                $join->on($rPaymentId, '=', $pId)
                     ->where($rCreatedAt, '>=', $from)
                     ->where($rCreatedAt, '<=', $to)
                     ->where($pMethod, '=', $method)
                     ->whereIn($pType, $gatewayCodes)
                     ->where($rGateway, '=', $gateway)
                     ->where($rBaseAmount, '!=', 0);
            })
            ->with('payment')
            ->get();

        return $refunds;
    }

    public function fetchFailedRefundsByGateway()
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchFailedRefundsByGateway',
            'route'        => $this->route
        ]);
        $refundPaymentIdAttr = $this->dbColumn(Entity::PAYMENT_ID);

        $refundStatus = $this->dbColumn(Refund\Entity::STATUS);

        $paymentIdAttr = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $refundGateway = $this->dbColumn(Refund\Entity::GATEWAY);

        $data =  $this->newQuery()
                       ->select(DB::raw('refunds.gateway as gateway, count(*) AS count'))
                       ->join(Table::PAYMENT, $refundPaymentIdAttr, '=', $paymentIdAttr)
                       ->where($refundStatus, '=', Refund\Status::FAILED)
                       ->groupBy($refundGateway)
                       ->get();

        return $data;
    }

    public function fetchFailedRefundsForGatewayBetweenTimestamps($from, $to, $gateway)
    {
//        if ($this->isScroogeReadMigrationEnabled2() === true && $this->app->environment('production') === true) {
//            return $this->fetchFailedRefundsForGatewayBetweenTimestampsRelationalLoad($from, $to, $gateway);
//        }
        return $this->fetchFailedRefundsForGatewayBetweenTimestampsFromApi( $from, $to, $gateway);
    }

    public function fetchFailedRefundsForGatewayBetweenTimestampsFromApi($from, $to, $gateway)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchFailedRefundsForGatewayBetweenTimestamps',
            'route'        => $this->route
        ]);
        $refundAttrs = $this->dbColumn('*');

        $refundPaymentIdAttr = $this->dbColumn(Entity::PAYMENT_ID);

        $refundStatus = $this->dbColumn(Refund\Entity::STATUS);

        $paymentIdAttr = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $refundGateway = $this->dbColumn(Refund\Entity::GATEWAY);

        $refundCreatedAt = $this->dbColumn(Refund\Entity::CREATED_AT);

        $query =  $this->newQuery()
                       ->select($refundAttrs)
                       ->join(Table::PAYMENT, $refundPaymentIdAttr, '=', $paymentIdAttr)
                       ->where($refundStatus, '=', Refund\Status::FAILED)
                       ->where($refundCreatedAt, '>=', $from)
                       ->where($refundCreatedAt, '<=', $to)
                       ->with(['payment']);

        if (empty($gateway) === false)
        {
            $query->where($refundGateway, '=', $gateway);
        }

        return $query->get();
    }

    /**
     * Fetches all refunds for card gateways where refund is processed after
     * six months from payment created at . It could not be processed via API
     *
     * @param $from
     * @param $to
     * @param $gateway
     * @param $acquirer
     * @param $timeRange
     *
     * @return Base\PublicCollection
     */
    public function fetchFailedCardRefundsToProcessManually($from, $to, $gateway, $acquirer, $timeRange)
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchFailedCardRefundsToProcessManually',
            'route'        => $this->route
        ]);
        $refundAttributes = $this->dbColumn('*');

        $refundPaymentIdAttr = $this->dbColumn(Entity::PAYMENT_ID);

        $refundStatus = $this->dbColumn(Refund\Entity::STATUS);

        $refundCreatedAt = $this->dbColumn(Refund\Entity::CREATED_AT);

        $refundGateway = $this->dbColumn(REFUND\Entity::GATEWAY);

        $paymentIdAttr = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $paymentCreatedAt =  $this->repo->payment->dbColumn(Payment\Entity::CREATED_AT);

        $paymentMethod  = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $paymentRefundStatus = $this->repo->payment->dbColumn(Payment\Entity::REFUND_STATUS);

        $TerminalId = $this->repo->terminal->dbColumn(Terminal\Entity::ID);

        $paymentTerminalAttr = $this->repo->payment->dbColumn(Payment\Entity::TERMINAL_ID);

        $terminalAcquirerAttr = $this->repo->terminal->dbColumn(Terminal\Entity::GATEWAY_ACQUIRER);

        $paymentCreatedBefore = Carbon::now()->subSeconds($timeRange)->timestamp;

        return $this->newQuery()
                    ->select($refundAttributes)
                    ->join(Table::PAYMENT, $refundPaymentIdAttr, '=', $paymentIdAttr)
                    ->join(Table::TERMINAL,$paymentTerminalAttr, '=', $TerminalId)
                    ->where($refundStatus, '=',Refund\Status::FAILED)
                    ->where($terminalAcquirerAttr, '=',$acquirer)
                    ->whereNotNull($paymentRefundStatus)
                    ->where($refundCreatedAt, '>=', $from)
                    ->where($refundCreatedAt, '<=', $to)
                    ->where($refundGateway, '=', $gateway)
                    ->whereIn($paymentMethod, [Payment\Method::CARD, Payment\Method::EMI])
                    // Doesn't matter when the refund was created, since payment is older.
                    // API can not process it, thus picking refund only based on payment date
                    ->where($paymentCreatedAt, '<=', $paymentCreatedBefore)
                    ->with(['payment'])
                    ->get();
    }


    public function fetchRefundsForTpvBetweenTimestamps(
        string $type,
        string $gatewayCode,
        int $from,
        int $to,
        string $gateway,
        bool $tpvEnabled = false)
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchRefundsForTpvBetweenTimestamps',
            'route'        => $this->route
        ]);
        // SELECT `refunds`.*
        // FROM `refunds`
        // INNER JOIN `payments` ON `refunds`.`payment_id` = `payments`.`id`
        // INNER JOIN `terminals` ON `payments`.`terminal_id` = `terminals`.`id`
        // WHERE `refunds`.`created_at` >= $from
        //   AND `refunds`.`created_at` < $to
        //   AND `payments`.`bank` = $gatewayCode
        //   AND `refunds`.`gateway` = $gateway
        //   AND `terminals`.`tpv` = $tpvEnabled

        $attrs = $this->dbColumn('*');

        $pRepo = $this->repo->payment;
        $pTableName = $pRepo->getTableName();

        $tRepo = $this->repo->terminal;
        $tTableName = $tRepo->getTableName();

        $rPaymentId = $this->dbColumn(Refund\Entity::PAYMENT_ID);
        $rCreatedAt = $this->dbColumn(Refund\Entity::CREATED_AT);
        $rBaseAmount = $this->dbColumn(Refund\Entity::BASE_AMOUNT);
        $rGateway = $this->dbColumn(Refund\Entity::GATEWAY);
        $pId = $pRepo->dbColumn(Payment\Entity::ID);
        $pType = $pRepo->dbColumn($type);

        $pTerminalId = $pRepo->dbColumn(Payment\Entity::TERMINAL_ID);

        $tId = $tRepo->dbColumn(Terminal\Entity::ID);
        $tTpv = $tRepo->dbColumn(Terminal\Entity::TPV);

        return $this->newQuery()
                    ->select($attrs)
                    ->join($pTableName, $rPaymentId, '=', $pId)
                    ->join($tTableName, $pTerminalId, '=', $tId)
                    ->where($rCreatedAt, '>=', $from)
                    ->where($rCreatedAt, '<=', $to)
                    ->where($pType, '=', $gatewayCode)
                    ->where($rGateway, '=', $gateway)
                    ->where($tTpv, '=', $tpvEnabled)
                    ->where($rBaseAmount, '!=', 0)
                    ->with('payment')
                    ->get();
    }

    public function fetchCorporateRefundsBetweenTimestamps(
        string $type,
        string $gatewayCode,
        int $from,
        int $to,
        string $gateway)
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchCorporateRefundsBetweenTimestamps',
            'route'        => $this->route
        ]);
        // SELECT `refunds`.*
        // FROM `refunds`
        // INNER JOIN `payments` ON `refunds`.`payment_id` = `payments`.`id`
        // INNER JOIN `terminals` ON `payments`.`terminal_id` = `terminals`.`id`
        // WHERE `refunds`.`created_at` >= $from
        //   AND `refunds`.`created_at` <= $to
        //   AND `payments`.`bank` = $gatewayCode
        //   AND `refunds`.`gateway` = $gateway

        $attrs = $this->dbColumn('*');

        $pRepo = $this->repo->payment;
        $pTableName = $pRepo->getTableName();

        $tRepo = $this->repo->terminal;
        $tTableName = $tRepo->getTableName();

        $rBaseAmount = $this->dbColumn(Refund\Entity::BASE_AMOUNT);
        $rPaymentId = $this->dbColumn(Refund\Entity::PAYMENT_ID);
        $rCreatedAt = $this->dbColumn(Refund\Entity::CREATED_AT);
        $rGateway = $this->dbColumn(Refund\Entity::GATEWAY);

        $pId = $pRepo->dbColumn(Payment\Entity::ID);
        $pType = $pRepo->dbColumn($type);
        $pTerminalId = $pRepo->dbColumn(Payment\Entity::TERMINAL_ID);

        $tId = $tRepo->dbColumn(Terminal\Entity::ID);
        $tCorp = $tRepo->dbColumn(Terminal\Entity::CORPORATE);

        return $this->newQuery()
                    ->select($attrs)
                    ->join($pTableName, $rPaymentId, '=', $pId)
                    ->join($tTableName, $pTerminalId, '=', $tId)
                    ->where($rCreatedAt, '>=', $from)
                    ->where($rCreatedAt, '<=', $to)
                    ->where($pType, '=', $gatewayCode)
                    ->where($rGateway, '=', $gateway)
                    ->where($rBaseAmount, '!=', 0)
                    ->with('payment')
                    ->get();
    }

    public function fetchRefundsByBatchAndPayment($batch, $payment)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchRefundsByBatchAndPayment',
            'route'        => $this->route
        ]);
        return $this->newQuery()
                    ->where(Refund\Entity::PAYMENT_ID, '=', $payment->getId())
                    ->where(Refund\Entity::MERCHANT_ID, '=', $batch->getMerchantId())
                    ->where(Refund\Entity::BATCH_ID, '=', $batch->getId())
                    ->get();
    }

    public function fetchFailedRefundsByMethod(string $method)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchFailedRefundsByMethod',
            'route'        => $this->route
        ]);
        $refundPaymentId = $this->dbColumn(Refund\Entity::PAYMENT_ID);
        $refundStatus    = $this->dbColumn(Refund\Entity::STATUS);

        $paymentId      = $this->repo->payment->dbColumn(Payment\Entity::ID);
        $paymentMethod  = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $query =  $this->newQuery()
                       ->select($this->dbColumn('*'))
                       ->join(Table::PAYMENT, $refundPaymentId, '=', $paymentId)
                       ->where($refundStatus, '=', Refund\Status::FAILED)
                       ->where($paymentMethod, '=', $method)
                       ->with(['payment','payment.terminal'])
                       ->inRandomOrder()
                       ->limit(200);

        return $query->get();
    }

    public function fetchIrctcDeltaRefunds(string $merchantId, int $from, int $to)
    {

        if ($this->isScroogeReadMigrationForIrctc() === true) {
            return $this->compareAndFetchIrctcDeltaRefundsFromTidb($merchantId, $from, $to);
        }
        return $this->fetchIrctcDeltaRefundsFromApi($merchantId, $from, $to);

    }

    public function fetchIrctcDeltaRefundsFromApi(string $merchantId, int $from, int $to)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchIrctcDeltaRefunds',
            'route'        => $this->route
        ]);
        $query = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT))
                      ->select($this->dbColumn('*'))
                      ->whereIn(Entity::ID, function ($query) use($merchantId, $from, $to)
                        {
                            $pId = $this->repo->payment->dbColumn(Payment\Entity::ID);

                            $pOrderId = $this->repo->payment->dbColumn(Payment\Entity::ORDER_ID);

                            $rPaymentId = $this->dbColumn(Entity::PAYMENT_ID);

                            $rMerchantId = $this->dbColumn(Entity::MERCHANT_ID);

                            $orderId = $this->repo->order->dbColumn(Order\Entity::ID);

                            $pCreatedAt = $this->repo->payment->dbColumn(Entity::CREATED_AT);

                            $receipt = $this->dbColumn(Entity::RECEIPT);

                            $status = $this->repo->order->dbColumn(Order\Entity::STATUS);

                            $query->select(\DB::raw('max(refunds.id)'))
                                  ->from('refunds')
                                  ->join(Table::PAYMENT, $rPaymentId, '=', $pId)
                                  ->join(Table::ORDER, $orderId, $pOrderId)
                                  ->where($rMerchantId, '=', $merchantId)
                                  ->where($pCreatedAt, '>=', $from)
                                  ->where($pCreatedAt, '<=', $to)
                                  ->whereNull($receipt)
                                  ->where($status, '!=', Order\Status::PAID)
                                  ->groupBy($orderId);
                      });

        return $query->get();
    }

    public function findByPaymentIdAndReference3(string $paymentId, int $seqNo)
    {

        if ($this->isScroogeReadMigrationEnabled2() === true) {
            return $this->compareAndFindByPaymentIdAndReference3FromScrooge($paymentId, $seqNo);
        }
        return $this->findByPaymentIdAndReference3FromApi($paymentId, $seqNo);

    }

    public function findByPaymentIdAndReference3FromApi(string $paymentId, int $seqNo)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'findByPaymentIdAndReference3',
            'route'        => $this->route
        ]);

        return $this->newQuery()
            ->where(Refund\Entity::PAYMENT_ID, '=', $paymentId)
            ->where(Refund\Entity::REFERENCE3, '=', $seqNo)
            ->firstOrFailPublic();
    }

    /**
     * update `refunds` set `processed_at` = refunds.last_attempted_at
     * where `processed_at` is null and `last_attempted_at` is not null
     * and `status` = 'processed' and `created_at` <= $createdAt
     * order by `created_at` asc limit $limit
     *
     * @param $limit
     * @param $createdAt
     * @return int Numbers of rows affected
     */
    public function updateProcessedAt($limit, $createdAt)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'updateProcessedAt',
            'route'        => $this->route
        ]);
        $count = $this->newQueryWithoutTimestamps()
                      ->whereNull(Refund\Entity::PROCESSED_AT)
                      ->whereNotNull(Refund\Entity::LAST_ATTEMPTED_AT)
                      ->where(Refund\Entity::STATUS, Refund\Status::PROCESSED)
                      ->where(Refund\Entity::CREATED_AT, '<=', $createdAt)
                      ->orderBy(Refund\Entity::CREATED_AT)
                      ->limit($limit)
                      ->update([
                            Refund\Entity::PROCESSED_AT => DB::raw('refunds.last_attempted_at'),
                        ]);

        return $count;
    }

    public function updateRefundReference1(array $refund)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'updateRefundReference1',
            'route'        => $this->route
        ]);
        return $this->newQueryWithoutTimestamps()
                    ->where(Refund\Entity::ID, $refund[Refund\Entity::ID])
                    ->where(Refund\Entity::STATUS, Refund\Status::PROCESSED)
                    ->update([
                        Refund\Entity::REFERENCE1 => $refund[Refund\Entity::REFERENCE1],
                    ]);
    }

    public function findByReceiptAndMerchant(string $receipt, string $merchantId)
    {
        if ($this->isScroogeReadMigrationEnabled() === true) {
            return $this->findForPaymentByReceiptAndMerchant($receipt, $merchantId);
        }
        return $this->findByReceiptAndMerchantFromApi($receipt, $merchantId);
    }

    public function findByReceiptAndMerchantFromApi(string $receipt, string $merchantId)
    {

        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'findByReceiptAndMerchant',
            'route'        => $this->route
        ]);
        return $this->newQuery()
            ->where(Refund\Entity::RECEIPT, '=', $receipt)
            ->where(Refund\Entity::MERCHANT_ID, '=', $merchantId)
            ->first();
    }
    public function getAliasesForRefundsDbColumns($params): array
    {
        $dbColumns = [];

        foreach ($params as $param)
        {
            $dbColumn = $this->repo->refund->dbColumn($param);

            $dbColumns[] = $dbColumn . ' as refund_'. $param;
        }

        return $dbColumns;
    }

    public function saveOrFail($refund, array $options = array())
    {
        $payment = $this->stripPaymentRelationIfApplicable($refund);

        parent::saveOrFail($refund, $options);

        $this->associatePaymentIfApplicable($refund, $payment);
    }

    public function associatePaymentIfApplicable($refund, $payment)
    {
        if ($payment === null)
        {
            return;
        }

        $refund->payment()->associate($payment);
    }

    protected function stripPaymentRelationIfApplicable($refund)
    {
        $payment = $refund->payment;

        if (($payment == null) ||
            ($payment->isExternal() === false))
        {
            return;
        }

        $refund->payment()->dissociate();

        $refund->setPaymentId($payment->getId());

        return $payment;
    }

    public function fetchDebitEmiRefundsWithRelationsBetween($from, $to, $bank, $gateway)
    {
        $this->app['trace']->info(TraceCode::QUERY_REFUNDS_TABLE, [
            'method'       => 'fetchDebitEmiRefundsWithRelationsBetween'
        ]);

        $tRepo = $this->repo->terminal;

        $paymentRepo = $this->repo->payment;

        $tTableName = $tRepo->getTableName();

        $pTableName = $paymentRepo->getTableName();

        $terminalEmi = $tRepo->dbColumn(Terminal\Entity::EMI);

        $paymentId = $paymentRepo->dbColumn(Payment\Entity::ID);

        $paymentTerminalId = $paymentRepo->dbColumn(Payment\Entity::TERMINAL_ID);

        $terminalGateway = $tRepo->dbColumn(Terminal\Entity::GATEWAY);

        $refundData = $this->dbColumn('*');

        $terminalId = $tRepo->dbColumn(Terminal\Entity::ID);

        $paymentStatus = $paymentRepo->dbColumn(Payment\Entity::STATUS);

        $paymentBank = $paymentRepo->dbColumn(Payment\Entity::BANK);
        $paymentMethod = $paymentRepo->dbColumn(Payment\Entity::METHOD);
        $refundCreatedAt = $this->dbColumn(Entity::CREATED_AT);

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->join($pTableName, $paymentId, '=', Refund\Entity::PAYMENT_ID)
            ->join($tTableName, $paymentTerminalId, '=', $terminalId)
            ->whereBetween($refundCreatedAt, [$from, $to])
            ->where($paymentStatus, '=', Payment\Status::REFUNDED)
            ->where($paymentBank, '=', $bank)
            ->where($paymentMethod, '=', Payment\Method::EMI)
            ->where($terminalGateway, '=', $gateway)
            ->where($terminalEmi, '=', true)
            ->with('payment', 'payment.card.globalCard', 'payment.emiPlan', 'payment.merchant')
            ->select($refundData)
            ->get();
    }

    public function findManyWithRelations($ids, $relations, $columns = array('*'), $useWarehouse = false)
    {
        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;
        $variant = $this->app->razorx->getTreatment(UniqueIdEntity::generateUniqueId(), RazorxTreatment::REFUND_FIND_MANY_RELATIONS, $mode);
        if ($useWarehouse === true && $variant === 'on')
        {
            return $this->repo->refund_tidb->findManyWithRelations($ids, $relations, $columns, true);
        }

        return parent::findManyWithRelations($ids, $relations, $columns, $useWarehouse);
    }
}

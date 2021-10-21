<?php

namespace RZP\Models\Payment\Refund;

use DB;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Http\Route;
use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;

use RZP\Models\Payment\Status;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Refund;
use RZP\Gateway\Wallet\Freecharge;
use RZP\Constants\Entity as EntityConstants;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;
use RZP\Models\Reversal\Entity as ReversalEntity;
use RZP\Gateway\Wallet\Base\Entity as WalletEntity;
use RZP\Models\Payment\Refund\Entity as RefundEntity;

class Repository extends Base\Repository
{
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

    public function fetchEmiRefundsWithCardTerminalsBetween($from, $to, $bank)
    {
        $tRepo = $this->repo->terminal;

        $paymentRepo = $this->repo->payment;

        $tTableName = $tRepo->getTableName();

        $pTableName = $paymentRepo->getTableName();

        $terminalEmi = $tRepo->dbColumn(Terminal\Entity::EMI);

        $paymentId = $paymentRepo->dbColumn(Payment\Entity::ID);

        $paymentTerminalId = $paymentRepo->dbColumn(Payment\Entity::TERMINAL_ID);

        $refundData = $this->dbColumn('*');

        $terminalId = $tRepo->dbColumn(Terminal\Entity::ID);

        $paymentStatus = $paymentRepo->dbColumn(Payment\Entity::STATUS);

        $paymentBank = $paymentRepo->dbColumn(Payment\Entity::BANK);
        $paymentMethod = $paymentRepo->dbColumn(Payment\Entity::METHOD);
        $refundCreatedAt = $this->dbColumn(Entity::CREATED_AT);

        return $this->newQuery()
            ->join($pTableName, $paymentId, '=', Refund\Entity::PAYMENT_ID)
            ->join($tTableName, $paymentTerminalId, '=', $terminalId)
            ->whereBetween($refundCreatedAt, [$from, $to])
            ->where($paymentStatus, '=', Payment\Status::REFUNDED)
            ->where($paymentBank, '=', $bank)
            ->where($paymentMethod, '=', Payment\Method::EMI)
            ->where($terminalEmi, '=', false)
            ->with('payment', 'payment.card.globalCard', 'payment.emiPlan', 'payment.merchant')
            ->select($refundData)
            ->get();
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

    protected function addQueryParamPaymentGateway($query, $params)
    {
        $gateway = $params['payment_gateway'];

        $paymentGateway = $this->repo->payment->dbColumn(Payment\Entity::GATEWAY);

        Payment\Gateway::validateGateway($gateway);

        $this->joinQueryPayment($query);

        $query->where($paymentGateway, '=', $gateway);

        $query->select($query->getModel()->getTable().'.*');
    }

    protected function addQueryParamMethod($query, $params)
    {
        $method = $params[Payment\Entity::METHOD];

        Payment\Method::validateMethod($method);

        $this->joinQueryPayment($query);

        $query->where(Payment\Entity::METHOD, '=', $method);

        $query->select($query->getModel()->getTable().'.*');
    }

    protected function addQueryParamPublicStatus($query, $params)
    {
        // We are disabling filtering for merchants like flipkart for which we are
        // modifying public status based on some buisness logics and is not stored in API DB
        $disableStatusFilter = Refund\Core::fetchPublicStatusFromScrooge($this->merchant->getId());

        if ($disableStatusFilter === true)
        {
            return;
        }

        $showApiRefundStatus = Refund\Core::fetchPublicStatusFromApi($this->merchant->getId());

        switch($params[Entity::PUBLIC_STATUS])
        {
            case Refund\Status::PROCESSED:
                ($showApiRefundStatus === true) ?
                    $query->where(Entity::STATUS, '=', Refund\Status::PROCESSED) : $query->whereNotNull(Entity::SPEED_PROCESSED);

                break;

            case Refund\Status::PROCESSING:
                ($showApiRefundStatus === true) ?
                    $query->where(Entity::STATUS, '!=', Refund\Status::PROCESSED) : $query->whereNull(Entity::SPEED_PROCESSED);

                break;

            case Refund\Status::FAILED:
                $query->where(Entity::STATUS, '=', Refund\Status::REVERSED);

                break;
        }
    }

    protected function joinQueryPayment($query)
    {
        $joins = $query->getQuery()->joins;

        $joins = ($joins) ?? [];

        foreach ($joins as $join)
        {
            if ($join->table === $this->repo->payment->getTableName())
            {
                return;
            }
        }

        $paymentId = $this->repo->payment->dbColumn(Payment\Entity::ID);
        $refundPaymentId = $this->dbColumn(Refund\Entity::PAYMENT_ID);

        $paymentTable = $this->repo->payment->getTableName();

        $query->join($paymentTable, $paymentId, '=', $refundPaymentId);
    }

    public function findOrFailPublicByParams($id, $merchantId, $paymentId = null)
    {
        $query = $this->newQuery()->where(Refund\Entity::MERCHANT_ID, '=', $merchantId);

        if ($paymentId !== null)
        {
            $query->where(Refund\Entity::PAYMENT_ID, '=', $paymentId);
        }

        return $query->findOrFailPublic($id);
    }

    public function findForPaymentAndMerchant(Payment\Entity $payment, Merchant\Entity $merchant)
    {
        return $this->newQuery()
                    ->where(Refund\Entity::PAYMENT_ID, '=', $payment->getId())
                    ->merchantId($merchant->getId())
                    ->get();
    }

    public function findForPayment(Payment\Entity $payment)
    {
        return $this->findForPaymentId($payment->getId());
    }

    public function findForPaymentId(string $paymentId)
    {
        return $this->newQuery()
                    ->where(Refund\Entity::PAYMENT_ID, '=', $paymentId)
                    ->get();
    }

    public function fetchFirstForPaymentId(string $paymentId)
    {
        return $this->newQuery()
                    ->where(Refund\Entity::PAYMENT_ID, '=', $paymentId)
                    ->first();
    }

    public function findBetweenTimestamps($from, $to)
    {
        return $this->newQuery()
                    ->where(Refund\Entity::CREATED_AT, '>=', $from)
                    ->where(Refund\Entity::CREATED_AT, '<=', $to)
                    ->get();
    }

    public function findBetweenTimestampsForGateway($from, $to, $gateway)
    {
        return $this->newQuery()
                    ->where('refunds.created_at', '>=', $from)
                    ->where('refunds.created_at', '<=', $to)
                    ->where('refunds.gateway', '=', $gateway)
                    ->get();
    }

    public function getRefundedAmountByGateway(string $gateway, int $from, int $to)
    {
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
        return $this->newQuery()
                    ->where(Entity::REVERSAL_ID, $reversalId)
                    ->merchantId($accountId)
                    ->with($relations)
                    ->firstOrFailPublic();
    }
    public function findForPaymentAndAmount($paymentId, $amount)
    {
        return $this->newQuery()
                    ->where(Refund\Entity::PAYMENT_ID, $paymentId)
                    ->where(Refund\Entity::AMOUNT, $amount)
                    ->get();
    }

    public function findForPaymentAndBaseAmount($paymentId, $amount)
    {
        return $this->newQuery()
                    ->where(Refund\Entity::PAYMENT_ID, $paymentId)
                    ->where(Refund\Entity::BASE_AMOUNT, $amount)
                    ->get();
    }

    public function fetchEntitiesForReport($merchantId, $from, $to, $count, $skip, $relations = [])
    {
        return $this->fetchBetweenTimestampWithRelations(
                        $merchantId, $from, $to, $count, $skip, $relations);
    }

    public function fetchRefundSummaryBetweenTimestamp($from, $to)
    {
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
    public function fetchRefundByRefundIds($refundIds)
    {
        return $this->newQuery()
                    ->select(Table::REFUND. '.' . Refund\Entity::PAYMENT_ID,
                             Table::REFUND. '.' . Refund\Entity::REFERENCE1,
                             Table::REFUND. '.' . Refund\Entity::ID)
                    ->whereIn(Table::REFUND. '.' . Refund\Entity::ID, $refundIds)
                    ->get();
    }

    public function fetchGatewayRefundedRefundsWithoutTxns()
    {
        $refundAttrs = $this->dbColumn('*');
        $refundPaymentIdAttr = $this->dbColumn(Entity::PAYMENT_ID);
        $refundTransactionIdAttr = $this->dbColumn(Entity::TRANSACTION_ID);
        $refundGatewayRefundedAttr = $this->dbColumn(Entity::GATEWAY_REFUNDED);

        $paymentIdAttr = $this->repo->payment->dbColumn(Payment\Entity::ID);
        $paymentTransactionIdAttr = $this->repo->payment->dbColumn(Payment\Entity::TRANSACTION_ID);

        return $this->newQuery()
                    ->join(Table::PAYMENT, $refundPaymentIdAttr, '=', $paymentIdAttr)
                    ->select($refundAttrs)
                    ->whereNull($refundTransactionIdAttr)
                    ->whereNotNull($paymentTransactionIdAttr)
                    ->where($refundGatewayRefundedAttr, '=', 1)
                    ->with(['payment', 'merchant'])
                    ->get();
    }

    public function fetchRefundsForGatewayBetweenTimestamps($type, $gatewayCode, $from, $to, $gateway)
    {
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

    /**
     * Join with the corresponding gateway and check that this particular payment
     * has no gateway entity for the refund.
     *
     * @param $gateway
     * @param $ts
     * @return mixed
     */
    public function fetchMissingRefundsOfGateway($gateway, $ts)
    {
        // SELECT `refunds`.*
        // FROM `refunds`
        // INNER JOIN `payments` ON `refunds`.`payment_id` = `payments`.`id`
        // WHERE `refunds`.`gateway` = '$gateway'
        //     AND `payments`.`refund_status` IS NOT NULL
        //     AND `payments`.`transaction_id` IS NOT NULL
        //     AND `refunds`.`transaction_id` IS NOT NULL
        //     AND `refunds`.`created_at` > '$ts'
        //     AND refunds.id NOT IN
        //         (SELECT refunds.id
        //          FROM refunds
        //          JOIN billdesk ON refunds.id = refund_id);

        $paymentTable = Table::PAYMENT;
        $refundTable = Table::REFUND;
        $gatewayTable = Table::getTableNameForEntity($gateway);

        $refundIdAttr = $this->dbColumn(Entity::ID);
        $refundPaymentIdAttr = $this->dbColumn(Entity::PAYMENT_ID);
        $refundCreatedAtAttr = $this->dbColumn(Entity::CREATED_AT);
        $refundTransactionIdAttr = $this->dbColumn(Entity::TRANSACTION_ID);
        $refundGatewayAttr = $this->dbColumn(Refund\Entity::GATEWAY);

        $paymentIdAttr = $this->repo->payment->dbColumn(Payment\Entity::ID);
        $paymentRefundStatusAttr = $this->repo->payment->dbColumn(Payment\Entity::REFUND_STATUS);
        $paymentTransactionIdAttr = $this->repo->payment->dbColumn(Payment\Entity::TRANSACTION_ID);

        $gatewayRefundIdAttr = 'refund_id';

        $refundAttributes = $this->dbColumn('*');

        $response = $this->newQuery()
                         ->select($refundAttributes)
                         ->join($paymentTable, $refundPaymentIdAttr, '=', $paymentIdAttr)
                         ->where($refundGatewayAttr, '=', $gateway)
                         ->whereNotNull($paymentRefundStatusAttr)
                         ->whereNotNull($paymentTransactionIdAttr)
                         ->whereNotNull($refundTransactionIdAttr)
                         ->where($refundCreatedAtAttr, '>', $ts)
                         ->whereRaw($refundIdAttr . ' NOT IN ' .
                                 '(' .
                                     ' SELECT ' . $refundIdAttr .
                                     ' FROM ' . $refundTable .
                                     ' JOIN ' . $gatewayTable . ' ON ' . $refundIdAttr . ' = ' . $gatewayRefundIdAttr .
                                 ')'
                         )
                         ->get();

        return $response;
    }

    public function fetchRefundsByBatchAndPayment($batch, $payment)
    {
        return $this->newQuery()
                    ->where(Refund\Entity::PAYMENT_ID, '=', $payment->getId())
                    ->where(Refund\Entity::MERCHANT_ID, '=', $batch->getMerchantId())
                    ->where(Refund\Entity::BATCH_ID, '=', $batch->getId())
                    ->get();
    }

    public function fetchRefundsByGatewayAndAttempts($gateways, $attempts)
    {
        //
        // Select * from refunds join payments on refunds.payment_id = payments.id
        // where payments.gateway IN ($gateway) and refunds.attempts > $attempt and
        // refunds.last_attempted_at < $timeLimit and refunds.status = "failed"
        // order by rand() limit 50
        //

        $attrs = $this->dbColumn('*');

        $pRepo = $this->repo->payment;
        $pTableName = $pRepo->getTableName();

        $rPaymentId = $this->dbColumn(Refund\Entity::PAYMENT_ID);
        $rAttempts = $this->dbColumn(Refund\Entity::ATTEMPTS);
        $rStatus = $this->dbColumn(Refund\Entity::STATUS);
        $rLastAttemptedAt = $this->dbColumn(Refund\Entity::LAST_ATTEMPTED_AT);
        $rCreatedAt = $this->dbColumn(Refund\Entity::CREATED_AT);
        $rGateway = $this->dbColumn(Refund\Entity::GATEWAY);

        $pId = $pRepo->dbColumn(Payment\Entity::ID);

        $timeLimit = Carbon::now(Timezone::IST)->subMinutes(30)->getTimestamp();

        // TODO: If the number of gateways exceeds by half of total,
        // inverse the `whereIn` condition.
        // Adding a createdAt check as refund entity as track ids were different
        // for older refunds, 1493323209 is April 28, 2017 1:30:09 AM when 1st
        // processed refund was done.

        $query = $this->newQuery()
                    ->select($attrs)
                    ->join($pTableName, $rPaymentId, '=', $pId)
                    ->where($rAttempts, '<', $attempts)
                    ->where($rStatus, '=', Refund\Status::FAILED)
                    ->where($rCreatedAt, '>', 1493323209)
                    ->whereIn($rGateway, $gateways)
                    ->where($rLastAttemptedAt, '<', $timeLimit)
                    ->with(['payment','payment.terminal'])
                    ->inRandomOrder()
                    ->limit(100);

        return $query->get();
    }

    public function fetchFailedRefundsByMethod(string $method)
    {
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

    public function fetchRefundsForPnbClaims($from, $to, $gateway)
    {
        $pId = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $rPaymentId = $this->dbColumn(Entity::PAYMENT_ID);

        $rGateway = $this->dbColumn(Refund\Entity::GATEWAY);

        $pAuthorizedAt = $this->repo->payment->dbColumn(Payment\Entity::AUTHORIZED_AT);

        return $this->newQuery()
                    ->select($this->dbColumn('*'))
                    ->join(Table::PAYMENT, $rPaymentId, '=', $pId)
                    ->where($pAuthorizedAt, '<=', $from)
                    ->where($rGateway, '=', $gateway)
                    ->whereBetween($this->dbColumn(Entity::CREATED_AT), [$from, $to])
                    ->with(['payment','payment.terminal'])
                    ->get();
    }

    public function fetchIrctcDeltaRefunds(string $merchantId, int $from, int $to)
    {
        $query = $this->newQuery()
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
        return $this->newQueryWithoutTimestamps()
                    ->where(Refund\Entity::ID, $refund[Refund\Entity::ID])
                    ->where(Refund\Entity::STATUS, Refund\Status::PROCESSED)
                    ->update([
                        Refund\Entity::REFERENCE1 => $refund[Refund\Entity::REFERENCE1],
                    ]);
    }

    public function findByReceiptAndMerchant(string $receipt, string $merchantId)
    {
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
}

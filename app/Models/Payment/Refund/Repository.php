<?php

namespace RZP\Models\Payment\Refund;

use RZP\Gateway\Wallet\Base\Entity as WalletEntity;
use RZP\Gateway\Wallet\Freecharge;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\Payment\Refund;
use RZP\Exception;
use RZP\Constants\Table;
use Carbon\Carbon;
use RZP\Constants\Timezone;

class Repository extends Base\Repository
{
    protected $entity = 'refund';

    protected $entityFetchParamRules = array(
        Entity::PAYMENT_ID      => 'sometimes|alpha_dash|min:14|max:18',
    );

    protected $proxyFetchParamRules = [
        Entity::NOTES           => 'sometimes|string|max:500',
    ];

    protected $appFetchParamRules = array(
        Entity::AMOUNT          => 'sometimes|integer',
        Entity::MERCHANT_ID     => 'sometimes|alpha_dash',
        Entity::TRANSACTION_ID  => 'sometimes|alpha_dash|min:14|max:18',
        Entity::BATCH_ID        => 'sometimes|alpha_dash|min:14|max:20',
        Entity::NOTES           => 'sometimes|notes_fetch',
        Entity::STATUS          => 'sometimes|string|max:30',
        Payment\Entity::GATEWAY => 'sometimes|string|max:30',
        Payment\Entity::METHOD  => 'sometimes|string|max:30',
    );

    protected $signedIds = [
        Entity::BATCH_ID,
        Entity::PAYMENT_ID,
        Entity::TRANSACTION_ID,
    ];

    protected function addQueryParamGateway($query, $params)
    {
        $gateway = $params[Payment\Entity::GATEWAY];

        Payment\Gateway::validateGateway($gateway);

        $this->joinQueryPayment($query);

        $query->where(Payment\Entity::GATEWAY, '=', $gateway);

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

    public function findForPaymentAndMerchant($payment, $merchant)
    {
        return $this->newQuery()
                    ->where(Refund\Entity::PAYMENT_ID, '=', $payment->getId())
                    ->merchantId($merchant->getId())
                    ->get();
    }

    public function findForPayment($payment)
    {
        return $this->newQuery()
                    ->where(Refund\Entity::PAYMENT_ID, '=', $payment->getId())
                    ->get();
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
                    ->join('payments', 'refunds.payment_id', '=', 'payments.id')
                    ->select('refunds.*', 'payments.gateway')
                    ->where('refunds.created_at', '>=', $from)
                    ->where('refunds.created_at', '<=', $to)
                    ->where('payments.gateway', '=', $gateway)
                    ->get();
    }

    public function getRefundedAmountByGateway(string $gateway, int $from, int $to)
    {
        $refundPaymentId = $this->dbColumn(Entity::PAYMENT_ID);
        $refundAmount = $this->dbColumn(Entity::BASE_AMOUNT);
        $refundCreatedAt = $this->dbColumn(Entity::CREATED_AT);

        $paymentId = $this->repo->payment->dbColumn(Payment\Entity::ID);
        $paymentGateway = $this->repo->payment->dbColumn(Payment\Entity::GATEWAY);
        $paymentCapturedAt = $this->repo->payment->dbColumn(Payment\Entity::CAPTURED_AT);

        return $this->newQuery()
                    ->join(Table::PAYMENT, $refundPaymentId, '=', $paymentId)
                    ->where($paymentGateway, '=', $gateway)
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
                       'SUM(' . Entity::AMOUNT . ') AS sum' . ','.
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

                $pRepo = $this->repo->payment;
                $pId = $pRepo->dbColumn(Payment\Entity::ID);
                $pType = $pRepo->dbColumn($type);
                $pGateway = $pRepo->dbColumn(Payment\Entity::GATEWAY);

                $join->on($rPaymentId, '=', $pId)
                     ->where($rCreatedAt, '>=', $from)
                     ->where($rCreatedAt, '<=', $to)
                     ->where($pType, '=', $gatewayCode)
                     ->where($pGateway, '=', $gateway);
            })
            ->with('payment')
            ->get();

        return $refunds;
    }

    public function fetchRefundsForTpvBetweenTimestamps($type, $gatewayCode, $from, $to, $gateway, $tpvEnabled = false)
    {
        // SELECT `refunds`.*
        // FROM `refunds`
        // INNER JOIN `payments` ON `refunds`.`payment_id` = `payments`.`id`
        // INNER JOIN `terminals` ON `payments`.`terminal_id` = `terminals`.`id`
        // WHERE `refunds`.`created_at` >= $from
        //   AND `refunds`.`created_at` < $to
        //   AND `payments`.`bank` = $gatewayCode
        //   AND `payments`.`gateway` = $gateway
        //   AND `terminals`.`tpv` = $tpvEnabled

        $attrs = $this->dbColumn('*');

        $pRepo = $this->repo->payment;
        $pTableName = $pRepo->getTableName();

        $tRepo = $this->repo->terminal;
        $tTableName = $tRepo->getTableName();

        $rPaymentId = $this->dbColumn(Refund\Entity::PAYMENT_ID);
        $rCreatedAt = $this->dbColumn(Refund\Entity::CREATED_AT);

        $pId = $pRepo->dbColumn(Payment\Entity::ID);
        $pType = $pRepo->dbColumn($type);
        $pGateway = $pRepo->dbColumn(Payment\Entity::GATEWAY);
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
                    ->where($pGateway, '=', $gateway)
                    ->where($tTpv, '=', $tpvEnabled)
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
        // WHERE `payments`.`gateway` = '$gateway'
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

        $paymentIdAttr = $this->repo->payment->dbColumn(Payment\Entity::ID);
        $paymentGatewayAttr = $this->repo->payment->dbColumn(Payment\Entity::GATEWAY);
        $paymentRefundStatusAttr = $this->repo->payment->dbColumn(Payment\Entity::REFUND_STATUS);
        $paymentTransactionIdAttr = $this->repo->payment->dbColumn(Payment\Entity::TRANSACTION_ID);

        $gatewayRefundIdAttr = 'refund_id';

        $refundAttributes = $this->dbColumn('*');

        $response = $this->newQuery()
                         ->select($refundAttributes)
                         ->join($paymentTable, $refundPaymentIdAttr, '=', $paymentIdAttr)
                         ->where($paymentGatewayAttr, '=', $gateway)
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

    public function fetchWalletFreechargeRefundsForValidation()
    {
        //
        //    SELECT `refunds`.*
        //    FROM `refunds`
        //    INNER JOIN `payments` ON `refunds`.`payment_id` = `payments`.`id`
        //    INNER JOIN `wallet` ON `wallet`.refund_id = `refunds`.`id`
        //    WHERE `payments`.`gateway` = 'wallet_freecharge'
        //        AND `refunds`.`transaction_id` IS NOT NULL
        //        AND `wallet`.`status_code` = 'INITIATED';
        //

        $gateway = Payment\Gateway::WALLET_FREECHARGE;
        $gatewayTable = Table::getTableNameForEntity($gateway);
        $paymentTable = Table::PAYMENT;

        $refundIdAttr = $this->dbColumn(Entity::ID);
        $refundPaymentIdAttr = $this->dbColumn(Entity::PAYMENT_ID);
        $refundTransactionIdAttr = $this->dbColumn(Entity::TRANSACTION_ID);

        $paymentIdAttr = $this->repo->payment->dbColumn(Payment\Entity::ID);
        $paymentGatewayAttr = $this->repo->payment->dbColumn(Payment\Entity::GATEWAY);

        $gatewayStatusCodeAttr = $this->repo
                                      ->wallet
                                      ->dbColumn(WalletEntity::STATUS_CODE);

        $gatewayRefundIdAttr = $this->repo
                                    ->wallet
                                    ->dbColumn(WalletEntity::REFUND_ID);

        $refundAttributes = $this->dbColumn('*');

        $response = $this->newQuery()
                         ->select($refundAttributes)
                         ->join($paymentTable, $refundPaymentIdAttr, '=', $paymentIdAttr)
                         ->join($gatewayTable, $refundIdAttr, '=', $gatewayRefundIdAttr)
                         ->where($paymentGatewayAttr, '=', $gateway)
                         ->whereNotNull($refundTransactionIdAttr)
                         ->where($gatewayStatusCodeAttr, '=', Freecharge\Status::TRANSACTION_INITIATED)
                         ->limit(300)
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

        $pId = $pRepo->dbColumn(Payment\Entity::ID);
        $pGateway = $pRepo->dbColumn(Payment\Entity::GATEWAY);

        $timeLimit = Carbon::now(Timezone::IST)->subMinutes(30)->getTimestamp();

        // TODO: If the number of gateways exceeds by half of total,
        // inverse the `whereIn` condition.
        // Adding a createdAt check as refund entity as track ids were different
        // for older refunds, 1493323209 is April 28, 2017 1:30:09 AM when 1st
        // processed refund was done.

        return $this->newQuery()
                    ->select($attrs)
                    ->join($pTableName, $rPaymentId, '=', $pId)
                    ->where($rAttempts, '<', $attempts)
                    ->where($rStatus, '=', Refund\Status::FAILED)
                    ->where($rCreatedAt, '>', 1493323209)
                    ->whereIn($pGateway, $gateways)
                    ->where($rLastAttemptedAt, '<', $timeLimit)
                    ->with(['payment','payment.terminal'])
                    ->limit(50)
                    ->inRandomOrder()
                    ->get();
    }

    public function fetchFailedRefundsByMethod(string $method)
    {
        $refundPaymentId = $this->dbColumn(Refund\Entity::PAYMENT_ID);
        $refundStatus    = $this->dbColumn(Refund\Entity::STATUS);

        $paymentId      = $this->repo->payment->dbColumn(Payment\Entity::ID);
        $paymentGateway = $this->repo->payment->dbColumn(Payment\Entity::GATEWAY);
        $paymentMethod  = $this->repo->payment->dbColumn(Payment\Entity::METHOD);

        $query =  $this->newQuery()
                       ->select($this->dbColumn('*'))
                       ->join(Table::PAYMENT, $refundPaymentId, '=', $paymentId)
                       ->where($refundStatus, '=', Status::FAILED)
                       ->where($paymentMethod, '=', $method)
                       ->with(['payment','payment.terminal'])
                       ->limit(50);

        return $query->get();
    }

    public function fetchRefundsForPnbClaims($from, $to, $gateway)
    {
        $pId = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $rPaymentId = $this->dbColumn(Entity::PAYMENT_ID);

        $pGateway = $this->repo->payment->dbColumn(Payment\Entity::GATEWAY);

        $pAuthorizedAt = $this->repo->payment->dbColumn(Payment\Entity::AUTHORIZED_AT);

        return $this->newQuery()
                    ->select($this->dbColumn('*'))
                    ->join(Table::PAYMENT, $rPaymentId, '=', $pId)
                    ->where($pAuthorizedAt, '<=', $from)
                    ->where($pGateway, '=', $gateway)
                    ->whereBetween($this->dbColumn(Entity::CREATED_AT), [$from, $to])
                    ->with(['payment','payment.terminal'])
                    ->get();
    }

    public function fetchByMerchantBetweenTimestamps(string $merchantId, int $from, int $to, $receipt = null)
    {
        $query = $this->newQuery()
                      ->where(Refund\Entity::MERCHANT_ID, '=', $merchantId)
                      ->where(Refund\Entity::CREATED_AT, '>=', $from)
                      ->where(Refund\Entity::CREATED_AT, '<=', $to);

        if (empty($receipt) === true)
        {
            $query->whereNull(Refund\Entity::RECEIPT);
        }
        else
        {
            $query->where(Refund\Entity::RECEIPT, '=', $receipt);
        }

        return $query->get();
    }
}

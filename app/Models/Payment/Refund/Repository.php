<?php

namespace RZP\Models\Payment\Refund;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Payment\Refund;
use RZP\Exception;
use RZP\Constants\Table;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'refund';

    protected $entityFetchParamRules = array(
        Entity::PAYMENT_ID      => 'sometimes|alpha_num|max:14',
    );

    protected $proxyFetchParamRules = [
        Entity::NOTES           => 'sometimes|string|max:500',
    ];

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::PAYMENT_ID      => 'sometimes|alpha_num',
        Entity::TRANSACTION_ID  => 'sometimes|alpha_num',
        Entity::NOTES           => 'sometimes|string|max:500',
    );

    protected $esWhitelistedParams = [
        Entity::NOTES
    ];

    public function findOrFailPublicByParams($id, $merchantId, $paymentId = null)
    {
        $query = $this->newQuery()->where(Refund\Entity::MERCHANT_ID, '=', $merchantId);

        if ($paymentId !== null)
        {
            $query->where(Refund\Entity::PAYMENT_ID, '=', $paymentId);
        }

        return $query->findOrFailPublic($id);
    }

    public function findForPayment($payment, $merchant)
    {
        return $this->newQuery()
                    ->where(Refund\Entity::PAYMENT_ID, '=', $payment->getId())
                    ->merchantId($merchant->getId())
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

    public function fetchByIdPaymentIdMerchantId($id, $paymentId, $merchantId)
    {
        return $this->newQuery()
                    ->where(Refund\Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Refund\Entity::MERCHANT_ID, '=', $merchantId)
                    ->findOrFailPublic($id);
    }

    public function fetchEntitiesForReport($merchantId, $from, $to)
    {
        return $this->fetchBetweenTimestampWithRelations(
                        $merchantId, $from, $to, ['payment']);
    }

    public function fetchRefundSummaryBetweenTimestamp($from , $to)
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

    public function fetchRefundsForGatewayBetweenTimestamps($type, $gatewayCode, $from, $to, $gateway)
    {
        $ptable = Payment\Entity::getTableName();

        $attrs = Refund\Entity::getAttributeWithTableName('*');

        $query = $this->newQuery();

        $refunds = $query->select($attrs)->join(
            $ptable,
            function ($join) use ($from, $to, $type, $gatewayCode, $gateway)
            {
                $rPaymentId = Refund\Entity::getAttributeWithTableName(Refund\Entity::PAYMENT_ID);
                $rCreatedAt = Refund\Entity::getAttributeWithTableName(Refund\Entity::CREATED_AT);

                $pid = Payment\Entity::getAttributeWithTableName(Payment\Entity::ID);
                $ptype = Payment\Entity::getAttributeWithTableName($type);
                $pgateway = Payment\Entity::getAttributeWithTableName(Payment\Entity::GATEWAY);

                $join->on($rPaymentId, '=', $pid)
                     ->where($rCreatedAt, '>=', $from)
                     ->where($rCreatedAt, '<=', $to)
                     ->where($ptype, '=', $gatewayCode)
                     ->where($pgateway, '=', $gateway);
            })
            ->with('payment')
            ->get();

        return $refunds;
    }

    /**
     * Join with the corresponding gateway and check that this particular payment
     * has no gateway entity for the refund.
     * We send the
     *
     * @param $gateway
     * @param $ts
     * @return mixed
     */
    public function fetchMissingRefundsOfGateway($gateway, $ts)
    {
        // SELECT *
        // FROM refunds
        // JOIN payments ON refunds.payment_id = payments.id
        // WHERE refunds.id NOT IN
        //      (SELECT refunds.id
        //       FROM refunds
        //       JOIN $gateway ON refunds.id = refund_id)
        // AND payments.gateway = '$gateway'
        // AND payments.refund_status IS NOT NULL
        // AND refunds.created_at > '$ts';

        $paymentTable = Table::PAYMENT;
        $refundTable = Table::REFUND;
        $gatewayTable = constant(Table::class . '::' . strtoupper($gateway));

        $refundId = Entity::getAttributeWithTableName(Entity::ID);
        $refundPaymentId = Entity::getAttributeWithTableName(Entity::PAYMENT_ID);
        $refundCreatedAt = Entity::getAttributeWithTableName(Entity::CREATED_AT);

        $paymentId = Payment\Entity::getAttributeWithTableName(Payment\Entity::ID);
        $paymentGateway = Payment\Entity::getAttributeWithTableName(Payment\Entity::GATEWAY);
        $paymentRefundStatus = Payment\Entity::getAttributeWithTableName(Payment\Entity::REFUND_STATUS);

        $gatewayRefundId = 'refund_id';

        $response = $this->newQuery()
                         ->join($paymentTable, $refundPaymentId, '=', $paymentId)
                         ->where($paymentGateway, '=', $paymentGateway)
                         ->whereNotNull($paymentRefundStatus)
                         ->where($refundCreatedAt, '>', $ts)
                         ->whereRaw($refundId . ' NOT IN ' .
                                 '(' .
                                     ' SELECT ' . $refundId .
                                     ' FROM ' . $refundTable .
                                     ' JOIN ' . $gatewayTable . ' ON ' . $refundId . ' = ' . $gatewayRefundId .
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
}

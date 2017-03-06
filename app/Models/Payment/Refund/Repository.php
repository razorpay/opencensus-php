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
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::TRANSACTION_ID  => 'sometimes|alpha_dash|min:14|max:18',
        Entity::NOTES           => 'sometimes|string|max:500',
    );

    protected $signedIds = [
        Entity::PAYMENT_ID
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
        $refundAttrs = $this->getAttributeWithTableName('*');
        $refundPaymentIdAttr = $this->getAttributeWithTableName(Entity::PAYMENT_ID);
        $refundTransactionIdAttr = $this->getAttributeWithTableName(Entity::TRANSACTION_ID);
        $refundGatewayRefundedAttr = $this->getAttributeWithTableName(Entity::GATEWAY_REFUNDED);

        $paymentIdAttr = $this->manager->payment->getAttributeWithTableName(Payment\Entity::ID);
        $paymentTransactionIdAttr = $this->manager->payment->getAttributeWithTableName(Payment\Entity::TRANSACTION_ID);

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
        $attrs = $this->getAttributeWithTableName('*');

        $query = $this->newQuery();

        $refunds = $query->select($attrs)->join(
            $this->manager->payment->getTableName(),
            function ($join) use ($from, $to, $type, $gatewayCode, $gateway)
            {
                $rPaymentId = $this->getAttributeWithTableName(Refund\Entity::PAYMENT_ID);
                $rCreatedAt = $this->getAttributeWithTableName(Refund\Entity::CREATED_AT);

                $pRepo = $this->manager->payment;
                $pId = $pRepo->getAttributeWithTableName(Payment\Entity::ID);
                $pType = $pRepo->getAttributeWithTableName($type);
                $pGateway = $pRepo->getAttributeWithTableName(Payment\Entity::GATEWAY);

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

        $attrs = $this->getAttributeWithTableName('*');

        $pRepo = $this->manager->payment;
        $pTableName = $pRepo->getTableName();

        $tRepo = $this->manager->terminal;
        $tTableName = $tRepo->getTableName();

        $rPaymentId = $this->getAttributeWithTableName(Refund\Entity::PAYMENT_ID);
        $rCreatedAt = $this->getAttributeWithTableName(Refund\Entity::CREATED_AT);

        $pId = $pRepo->getAttributeWithTableName(Payment\Entity::ID);
        $pType = $pRepo->getAttributeWithTableName($type);
        $pGateway = $pRepo->getAttributeWithTableName(Payment\Entity::GATEWAY);
        $pTerminalId = $pRepo->getAttributeWithTableName(Payment\Entity::TERMINAL_ID);

        $tId = $tRepo->getAttributeWithTableName(Terminal\Entity::ID);
        $tTpv = $tRepo->getAttributeWithTableName(Terminal\Entity::TPV);

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

        $refundIdAttr = $this->getAttributeWithTableName(Entity::ID);
        $refundPaymentIdAttr = $this->getAttributeWithTableName(Entity::PAYMENT_ID);
        $refundCreatedAtAttr = $this->getAttributeWithTableName(Entity::CREATED_AT);
        $refundTransactionIdAttr = $this->getAttributeWithTableName(Entity::TRANSACTION_ID);

        $paymentIdAttr = $this->manager->payment->getAttributeWithTableName(Payment\Entity::ID);
        $paymentGatewayAttr = $this->manager->payment->getAttributeWithTableName(Payment\Entity::GATEWAY);
        $paymentRefundStatusAttr = $this->manager->payment->getAttributeWithTableName(Payment\Entity::REFUND_STATUS);
        $paymentTransactionIdAttr = $this->manager->payment->getAttributeWithTableName(Payment\Entity::TRANSACTION_ID);

        $gatewayRefundIdAttr = 'refund_id';

        $refundAttributes = $this->getAttributeWithTableName('*');

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

        $refundIdAttr = $this->getAttributeWithTableName(Entity::ID);
        $refundPaymentIdAttr = $this->getAttributeWithTableName(Entity::PAYMENT_ID);
        $refundTransactionIdAttr = $this->getAttributeWithTableName(Entity::TRANSACTION_ID);

        $paymentIdAttr = $this->manager->payment->getAttributeWithTableName(Payment\Entity::ID);
        $paymentGatewayAttr = $this->manager->payment->getAttributeWithTableName(Payment\Entity::GATEWAY);

        $gatewayStatusCodeAttr = $this->manager
                                      ->wallet
                                      ->getAttributeWithTableName(WalletEntity::STATUS_CODE);

        $gatewayRefundIdAttr = $this->manager
                                    ->wallet
                                    ->getAttributeWithTableName(WalletEntity::REFUND_ID);

        $refundAttributes = $this->getAttributeWithTableName('*');

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
}

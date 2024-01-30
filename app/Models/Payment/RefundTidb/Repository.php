<?php

namespace RZP\Models\Payment\RefundTidb;

use DB;
use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Card;
use RZP\Models\Order;

use RZP\Models\Payment\Refund\Entity;
use RZP\Models\Payment\Refund\ScroogeRepo;
use RZP\Models\Terminal;
use RZP\Base\ConnectionType;
use RZP\Models\Payment\Refund;
use RZP\Models\Base\Traits\ExternalScroogeRepo;
use RZP\Models\Reversal\Entity as ReversalEntity;

class Repository extends Base\Repository
{
    use ExternalScroogeRepo;
    use ScroogeRepo;

    protected $entity = 'refund_tidb';

    public function fetchEmiRefundsWithCardTerminalsBetweenFromTidb($from, $to, $bank, $type = 'credit')
    {
        (new Terminal\Service())->pushTerminalReadJoinMetrics(__FUNCTION__);

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

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->join($pTableName, $paymentId, '=', Refund\Entity::PAYMENT_ID)
            ->join($tTableName, $paymentTerminalId, '=', $terminalId)
            ->join($cardTableName, $paymentCardIdCol, '=', $cardIdCol)
            ->whereBetween($refundCreatedAt, [$from, $to])
            ->where($paymentStatus, '=', Payment\Status::REFUNDED)
            ->where($paymentBank, '=', $bank)
            ->where($paymentMethod, '=', Payment\Method::EMI)
            ->where($terminalEmi, '=', false)
            ->where($cardType, '=', $type)
            ->with('payment', 'payment.card.globalCard', 'payment.emiPlan', 'payment.merchant')
            ->select($refundData)
            ->get();
    }

    public function fetchCardRefundsForMerchantAndGatewayBetweenFromTidb($from, $to, $merchantIds)
    {
        $paymentRepo = $this->repo->payment;

        $pTableName = $paymentRepo->getTableName();

        $paymentId = $paymentRepo->dbColumn(Payment\Entity::ID);

        $refundData = $this->dbColumn('*');

        $paymentGateway = $paymentRepo->dbColumn(Payment\Entity::GATEWAY);
        $paymentMethod = $paymentRepo->dbColumn(Payment\Entity::METHOD);
        $refundProcessedAt = $this->dbColumn(Entity::PROCESSED_AT);

        $paymentMerchantId = $this->dbColumn(Entity::MERCHANT_ID);

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->join($pTableName, $paymentId, '=', Refund\Entity::PAYMENT_ID)
            ->whereBetween($refundProcessedAt, [$from, $to])
            ->whereIn($paymentMerchantId, $merchantIds)
            ->where($paymentMethod, '=', Payment\Method::CARD)
            ->where($paymentGateway, '=', 'cybersource')
            ->with('payment', 'payment.card.globalCard', 'payment.terminal')
            ->orderBy($this->dbColumn(Refund\Entity::PROCESSED_AT), 'desc')
            ->select($refundData)
            ->get();
    }

    /*public function fetchLaReversalsOfTransferFromTidb(string $transferId, string $merchantId)
    {
        $paymentRepo = $this->repo->payment;

        $reversalColumns = $this->repo->reversal->dbColumn('*');

        $reversalId = $this->repo->refund_tidb->dbColumn(Refund\Entity::REVERSAL_ID);

        $reversalsId = $this->repo->reversal->dbColumn(ReversalEntity::ID);

        $refundNotes = $this->repo->refund_tidb->dbColumn(Refund\Entity::NOTES);

        $reversalTable = $this->repo->reversal->getTableName();

        $reversalEntityType = $this->repo->reversal->dbColumn(ReversalEntity::ENTITY_TYPE);

        $reversalEntityId = $this->repo->reversal->dbColumn(ReversalEntity::ENTITY_ID);

        $refundMerchantId = $this->repo->refund_tidb->dbColumn(Refund\Entity::MERCHANT_ID);

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->join($reversalTable, $reversalsId, '=', $reversalId)
            ->select($refundNotes, $reversalColumns)
            ->where($reversalEntityId, $transferId)
            ->where($reversalEntityType, ReversalEntity::TRANSFER)
            ->where($refundMerchantId, $merchantId)
            ->get();
    }
*/

    public function fetchRefundsForGatewaysBetweenTimestampsFromTidb($type, $gatewayCodes, $from, $to, $gateway)
    {
        $paymentRepo = $this->repo->payment;

        $pTableName = $paymentRepo->getTableName();

        $pId = $paymentRepo->dbColumn(Payment\Entity::ID);

        $pType = $paymentRepo->dbColumn($type);

        $gatewayCodes = (array)$gatewayCodes;

        $refundData = $this->dbColumn('*');

        $rBaseAmount = $this->dbColumn(Refund\Entity::BASE_AMOUNT);

        $rGateway = $this->dbColumn(Refund\Entity::GATEWAY);

        $refundCreatedAt = $this->dbColumn(Entity::CREATED_AT);

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->join($pTableName, $pId, '=', Refund\Entity::PAYMENT_ID)
            ->whereBetween($refundCreatedAt, [$from, $to])
            ->whereIn($pType, $gatewayCodes)
            ->where($rGateway, '=', $gateway)
            ->where($rBaseAmount, '!=', 0)
            ->select($refundData)
            ->get();
    }

    public function fetchFailedRefundsForGatewayBetweenTimestampsFromTidb($from, $to, $gateway)
    {
        $refundAttrs = $this->dbColumn('*');

        $refundPaymentIdAttr = $this->dbColumn(Entity::PAYMENT_ID);

        $refundStatus = $this->dbColumn(Refund\Entity::STATUS);

        $paymentIdAttr = $this->repo->payment->dbColumn(Payment\Entity::ID);

        $refundGateway = $this->dbColumn(Refund\Entity::GATEWAY);

        $refundCreatedAt = $this->dbColumn(Refund\Entity::CREATED_AT);

        $query = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->select($refundAttrs)
            ->join(Table::PAYMENT, $refundPaymentIdAttr, '=', $paymentIdAttr)
            ->where($refundStatus, '=', Refund\Status::FAILED)
            ->where($refundCreatedAt, '>=', $from)
            ->where($refundCreatedAt, '<=', $to)
            ->with(['payment']);

        if (empty($gateway) === false) {
            $query->where($refundGateway, '=', $gateway);
        }

        return $query->get();
    }

    //Review Properly
    public function fetchIrctcDeltaRefundsFromTidb($merchantId, $from, $to)
    {
        $paymentRepo = $this->repo->payment;

        $orderRepo = $this->repo->order;

        $pOrderId = $this->repo->payment->dbColumn(Payment\Entity::ORDER_ID);

        $pTableName = $paymentRepo->getTableName();

        $oTableName = $orderRepo->getTableName();

        $rPaymentId = $this->dbColumn(Entity::PAYMENT_ID);

        $rMerchantId = $this->dbColumn(Entity::MERCHANT_ID);

        $orderId = $this->repo->order->dbColumn(Order\Entity::ID);

        $pCreatedAt = $this->repo->payment->dbColumn(Entity::CREATED_AT);

        $receipt = $this->dbColumn(Entity::RECEIPT);

        $status = $this->repo->order->dbColumn(Order\Entity::STATUS);

        $pId = $paymentRepo->dbColumn(Payment\Entity::ID);

        $innerQuery = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->select(\DB::raw('max(`scrooge-live`.`refunds`.`id`) as max_refund_id'))
            ->from($this->getTableName())
            ->join($pTableName, $rPaymentId, '=', $pId)
            ->join($oTableName, $orderId, $pOrderId)
            ->where($rMerchantId, '=', $merchantId)
            ->where($pCreatedAt, '>=', $from)
            ->where($pCreatedAt, '<=', $to)
            ->whereNull($receipt)
            ->where($status, '!=', Order\Status::PAID)
            ->groupBy($orderId);

// Build the main query using a subquery in the join condition
        $query = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->select($this->dbColumn('*'))
            ->from($this->getTableName())
            ->join(\DB::raw('(' . $innerQuery->toSql() . ') as max_refund_subquery'), function ($join) {
                $join->on('scrooge-live.refunds.id', '=', 'max_refund_subquery.max_refund_id');
            })
            ->mergeBindings($innerQuery->getQuery()); // Bind the inner query parameters

        return $query->get();

    }
}

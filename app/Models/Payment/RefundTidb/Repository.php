<?php

namespace RZP\Models\Payment\RefundTidb;

use DB;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Card;

use RZP\Models\Payment\Refund\ScroogeRepo;
use RZP\Models\Terminal;
use RZP\Base\ConnectionType;
use RZP\Models\Payment\Refund;
use RZP\Models\Base\Traits\ExternalScroogeRepo;

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
}

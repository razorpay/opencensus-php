<?php

namespace RZP\Models\Transaction\FeeBreakup;

use RZP\Base\ConnectionType;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Payment;
use RZP\Models\Transaction;
use RZP\Constants\Partitions;
use RZP\Models\Base\Traits\PartitionRepo;

class Repository extends Base\Repository
{
    use PartitionRepo;

    protected $entity = 'fee_breakup';

    protected $appFetchParamRules = array(
        Entity::TRANSACTION_ID          => 'sometimes|alpha_num|size:14',
        Entity::PRICING_RULE_ID         => 'sometimes|alpha_num|size:14',
    );

    public function fetchFeesBreakupForInvoice($merchantId, $from, $to)
    {
        $feeBreakupAmount = $this->repo
                                 ->fee_breakup
                                 ->dbColumn(Entity::AMOUNT);

        $feeBreakupTransactionId = $this->repo
                                        ->fee_breakup
                                        ->dbColumn(Entity::TRANSACTION_ID);

        $transactionId = $this->repo
                              ->transaction
                              ->dbColumn(Transaction\Entity::ID);

        $entityId = $this->repo
                         ->transaction
                         ->dbColumn(Transaction\Entity::ENTITY_ID);

        $transactionMerchantId = $this->repo
                                      ->transaction
                                      ->dbColumn(Transaction\Entity::MERCHANT_ID);

        $type = $this->repo
                     ->transaction
                     ->dbColumn(Transaction\Entity::TYPE);

        $transactionCreatedAt = $this->repo
                                     ->transaction
                                     ->dbColumn(Transaction\Entity::CREATED_AT);

        $paymentId = $this->repo
                          ->payment
                          ->dbColumn(Payment\Entity::ID);

        $capturedAt = $this->repo
                           ->payment
                           ->dbColumn(Payment\Entity::CAPTURED_AT);

        // pushing the query to admin-tidb-cluster as sumo-logs show the routes gets called for upto 6 months data
        // and hence, the query will have to crunch a lot of data.
        // merchant-tidb-cluster should not deal with such heavy queries
        $connectionType = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN);

        $feesBreakup = $this->newQueryWithConnection($connectionType)
                            ->selectRaw(Entity::NAME . ','.
                                'SUM(' .$feeBreakupAmount .') AS sum')
                            ->join(Table::TRANSACTION, $feeBreakupTransactionId, '=', $transactionId)
                            ->join(Table::PAYMENT, $entityId, '=', $paymentId)
                            ->where($transactionMerchantId, $merchantId)
                            ->where($type, 'payment')
                            ->whereNotNull($capturedAt)
                            ->whereBetween($transactionCreatedAt, [$from, $to])
                            ->groupBy(Entity::NAME)
                            ->get();

        return $feesBreakup;
    }

    public function fetchByTransactionId(string $transactionId)
    {
        $connectionType = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT);

        $feesBreakups = $this->newQueryWithConnection($connectionType)
                            ->where(Entity::TRANSACTION_ID, $transactionId)
                            ->get();

        return $feesBreakups;
    }

    public function deleteFeeBreakupForId(string $id)
    {
        $this->newQuery()
            ->where(Entity::ID, $id)
            ->delete();
    }

    protected function getPartitionStrategy() : string
    {
        return Partitions::DAILY;
    }

    protected function getDesiredOldPartitionsCount() : int
    {
        return 7;
    }
}

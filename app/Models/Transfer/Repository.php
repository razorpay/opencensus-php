<?php

namespace RZP\Models\Transfer;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Settlement;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Constants\Entity as E;

class Repository extends Base\Repository
{
    protected $entity = 'transfer';

    protected $expands = [
        Entity::TO,
    ];

    protected $entityFetchParamRules = [
        Entity::RECIPIENT               => 'sometimes|string|max:20',
        Entity::RECIPIENT_SETTLEMENT_ID => 'filled|string|public_id',
        self::EXPAND . '.*'             => 'filled|string|in:recipient_settlement,',
    ];

    protected $appFetchParamRules = [
        Entity::TRANSACTION_ID      => 'sometimes|alpha_num|size:14',
        Entity::MERCHANT_ID         => 'sometimes|alpha_num|size:14',
        Entity::SOURCE              => 'sometimes|string|min:14',
        Entity::RECIPIENT           => 'sometimes|string|min:14'
    ];

    /**
     * Fetch all transfers from a merchant, done on a payment
     *
     * @param string          $sourceType
     * @param string          $sourceId
     * @param Merchant\Entity $merchant
     * @param array           $status
     */
    public function fetchBySourceTypeAndIdAndMerchant(string $sourceType, string $sourceId, Merchant\Entity $merchant, array $status = [])
    {
        $query = $this->newQuery()
                      ->where(Entity::SOURCE_TYPE, $sourceType)
                      ->where(Entity::SOURCE_ID, $sourceId)
                      ->merchantId($merchant->getId());
        if (count($status) > 0)
        {
            $query = $query->whereIn(Entity::STATUS, $status);
        }

        return $query->get();
    }

    /**
     * Fetch transfer using id and linked account merchant id
     *
     * @param string          $transferId
     * @param string          $paymentId
     * @param Merchant\Entity $merchant
     */
    public function fetchByPublicIdAndLinkedAccountMerchant(string $id, Merchant\Entity $merchant)
    {
        $entity = $this->getEntityClass();

        $entity::verifyIdAndStripSign($id);

        return $this->newQuery()
                    ->where(Entity::TO_ID, $merchant->getId())
                    ->where(Entity::TO_TYPE, E::MERCHANT)
                    ->merchantId($merchant->parent->getId())
                    ->findOrFailPublic($id);
    }

    protected function addQueryParamSource($query, $params)
    {
        $sourceId = $params[Entity::SOURCE];

        Entity::stripSignWithoutValidation($sourceId);

        $query->where(Entity::SOURCE_ID, $sourceId);
    }

    protected function addQueryParamRecipient($query, $params)
    {
        $toId = $params[Entity::RECIPIENT];

        Entity::stripSignWithoutValidation($toId);

        $query->where(Entity::TO_ID, $toId);
    }

    protected function addQueryParamRecipientSettlementId($query, $params)
    {
        $id = $params[Entity::RECIPIENT_SETTLEMENT_ID];

        Settlement\Entity::verifyIdAndStripSign($id);

        $query->where(Entity::RECIPIENT_SETTLEMENT_ID, $id);
    }

    /**
     * Query: SELECT DISTINCT `source_id` FROM `transfers` WHERE `source_type` = $sourceType AND
     * `status` = 'pending' LIMIT $count
     *
     * @param string $sourceType
     * @param int $count
     *
     * @return mixed
     */
    public function fetchPendingTransfersToRetry(string $sourceType, int $count = 100)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select(Entity::SOURCE_ID)
                    ->where(Entity::SOURCE_TYPE, $sourceType)
                    ->where(Entity::STATUS, Status::PENDING)
                    ->limit($count)
                    ->distinct()
                    ->get()
                    ->pluck(Entity::SOURCE_ID)
                    ->toArray();
    }

    //update transfers set recipient_settlement_id = 'ES69iARhvUoyCo' where id in
    //  (
    //  select tr.id from transactions t inner join payments p
    //  ON t.entity_id = p.id inner join transfers tr on tr.id = p.transfer_id and
    //  t.type = 'payment' and tr.recipient_settlement_id is null  and t.settlement_id = 'ES69iARhvUoyCo'
    //  )
    public function updatetransfersWithSettelement(string $settelementId)
    {
        Settlement\Entity::verifyIdAndStripSign($settelementId);

        $paymentId            = $this->repo->payment->dbColumn(Payment\Entity::ID);
        $paymentIdColumn      = $this->repo->payment->dbColumn(Entity::ID);
        $transferIdColumn     = $this->repo->payment->dbColumn(Payment\Entity::TRANSFER_ID);
        $entityIdCol          = $this->repo->transaction->dbColumn(Transaction\Entity::ENTITY_ID);
        $entityType           = $this->repo->transaction->dbColumn(Transaction\Entity::TYPE);
        $settlementCol        = $this->repo->transaction->dbColumn(Transaction\Entity::SETTLEMENT_ID);
        $settlementIdColumn   = $this->dbColumn(Entity::RECIPIENT_SETTLEMENT_ID);
        $transferColumn       = $this->dbColumn(Entity::ID);

        $transfers =  $this->newQuery()
                           ->join(Table::PAYMENT,$transferIdColumn, '=', $transferColumn)
                           ->join(Table::TRANSACTION,$entityIdCol, '=', $paymentIdColumn)
                           ->where($entityType, Constant::PAYMENT)
                           ->whereNull($settlementIdColumn)
                           ->where($settlementCol, $settelementId)
                           ->select($transferColumn)
                           ->get();

                      $this->newQuery()
                            ->whereIn($transferColumn, $transfers)
                            ->update
                            ([
                              Entity::RECIPIENT_SETTLEMENT_ID => $settelementId
                            ]);
        return $transfers;
    }

    /**
     * Query: SELECT DISTINCT `source_id` FROM `transfers` WHERE `source_type` = $sourceType AND
     * `status` = 'failed' AND `processed_at` < ? AND `attempts` < 4 LIMIT $count
     *
     * @param string $sourceType
     * @param int $count
     *
     * @return mixed
     */
    public function fetchFailedTransfersToRetry(string $sourceType, int $count = 100)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select(Entity::SOURCE_ID)
                    ->where(Entity::SOURCE_TYPE, $sourceType)
                    ->where(Entity::STATUS, Status::FAILED)
                    ->where(
                        Entity::PROCESSED_AT,
                        '<',
                        Carbon::today(Timezone::IST)->getTimestamp()
                    )
                    ->where(Entity::ATTEMPTS, '<', Constant::MAX_ALLOWED_ORDER_TRANSFER_PROCESS_ATTEMPTS)
                    ->limit($count)
                    ->distinct()
                    ->get()
                    ->pluck(Entity::SOURCE_ID)
                    ->toArray();
    }

    /**
     * Query: UPDATE `transfers` SET `status` = $status WHERE `source_type` = $sourceType AND `source_id` = $sourceId
     *
     * @param string $sourceType
     * @param string $sourceId
     * @param string $status
     * @return mixed
     */
    public function updateTransferStatusBySourceTypeAndId(string $sourceType, string $sourceId, string $status)
    {
        return $this->newQuery()
                    ->where(Entity::SOURCE_TYPE, $sourceType)
                    ->where(Entity::SOURCE_ID, $sourceId)
                    ->update([Entity::STATUS => $status]);
    }

    /**
     * Query for fetching the transfers by paymentId
     *
     * @param string $sourceType
     * @param string $sourceId
     * @param string $merchantId
     *
     * @return mixed
     */
    public function getTransfersByPayments(string $sourceId, string $merchantId)
    {
        $relations = ['recipientSettlement'];

        return $this->newQuery()
                    ->where(Entity::SOURCE_ID, $sourceId)
                    ->where(Entity::TO_ID,$merchantId)
                    ->with($relations)
                    ->get();
    }

    /**
     * Query for fetching transfer by payment and transferId
     *
     * @param string $sourceType
     * @param string $sourceId
     * @param string $merchantId
     * @param string $transId
     *
     * @return mixed
     */
    public function getTransfersByPaymentsAndTransId(string $sourceId, string $merchantId, string $transId)
    {
        $relations = ['recipientSettlement'];

        return $this->newQuery()
                    ->where(Entity::ID,$transId)
                    ->where(Entity::SOURCE_ID, $sourceId)
                    ->where(Entity::TO_ID,$merchantId)
                    ->with($relations)
                    ->get();
    }

    public function getCountTransfersByRecipientSettlementId(string $recipientSettlementId)
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->where(Entity::RECIPIENT_SETTLEMENT_ID, $recipientSettlementId)
                      ->count(Entity::ID);

        return $query;
    }

    public function getIdsByRecipientSettlementId(string $settlementId, array $status = [])
    {
        $query = $this->newQuery()
                      ->select(Entity::ID)
                      ->where(Entity::RECIPIENT_SETTLEMENT_ID, $settlementId);

        if (empty($status) === false)
        {
            $query = $query->whereIn(Entity::STATUS, $status);
        }

        return $query->pluck(Entity::ID)->toArray();
    }

    public function getByMerchantId(string $merchantId, int $startDate, int $endDate, int $skip, int $chunk = 1000)
    {
        return $this->newQuery()
                    ->select(Entity::ID)
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->whereIn(Entity::SOURCE_TYPE, [Constant::PAYMENT, Constant::ORDER])
                    ->whereIn(Entity::STATUS, [Status::PROCESSED, Status::REVERSED, Status::PARTIALLY_REVERSED, Status::FAILED])
                    ->whereNull(Entity::SETTLEMENT_STATUS)
                    ->where(Entity::CREATED_AT, '>=', $startDate)
                    ->where(Entity::CREATED_AT, '<', $endDate)
                    ->skip($skip)
                    ->take($chunk)
                    ->pluck(Entity::ID)
                    ->toArray();
    }
}

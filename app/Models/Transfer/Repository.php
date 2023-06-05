<?php

namespace RZP\Models\Transfer;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Settlement;
use RZP\Constants\Timezone;
use RZP\Base\ConnectionType;
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

    protected function addQueryParamExcludedLinkedAccounts($query, $params)
    {
        $toAttribute = $this->dbColumn(Entity::TO_ID);

        $query->whereNotIn($toAttribute, $params[Constant::EXCLUDED_LINKED_ACCOUNTS]);
    }

    protected function addQueryParamIncludedLinkedAccounts($query, $params)
    {
        $toAttribute = $this->dbColumn(Entity::TO_ID);

        $query->whereIn($toAttribute, $params[Constant::INCLUDED_LINKED_ACCOUNTS]);
    }

    /**
     * Query: SELECT DISTINCT `source_id` FROM `transfers` WHERE `source_type` = $sourceType AND
     * `status` = 'pending' AND `updated_at` < (now - 3 hours) LIMIT $count
     *
     * @param string $sourceType
     * @param int $count
     *
     * @return mixed
     */
    public function fetchPendingTransfers(string $sourceType, array $includeMerchantIds, array $excludeMerchantIds, int $count, int $minutes)
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection());

        // If a list of merchantIds is given, we will fetch transfers only for those merchantIds. Else
        // fetch transfers for all merchants excluding key merchants.
        if (empty($includeMerchantIds) === false)
        {
            $query->whereIn(Entity::MERCHANT_ID, $includeMerchantIds);
        }
        else
        {
            $query->whereNotIn(Entity::MERCHANT_ID, $excludeMerchantIds);
        }

        return $query->select(Entity::SOURCE_ID)
                     ->where(Entity::SOURCE_TYPE, $sourceType)
                     ->where(Entity::STATUS, Status::PENDING)
                     ->where(Entity::UPDATED_AT, '<', Carbon::now()->subMinutes($minutes)->getTimestamp())
                     ->limit($count)
                     ->distinct()
                     ->pluck(Entity::SOURCE_ID)
                     ->toArray();
    }

    public function fetchPendingTransfersForKeyMerchants(string $sourceType, array $merchantIds, int $count, int $minutes)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->select(Entity::SOURCE_ID)
                    ->whereIn(Entity::MERCHANT_ID, $merchantIds)
                    ->where(Entity::SOURCE_TYPE, $sourceType)
                    ->where(Entity::STATUS, Status::PENDING)
                    ->where(Entity::UPDATED_AT, '<', Carbon::now()->subMinutes($minutes)->getTimestamp())
                    ->limit($count)
                    ->distinct()
                    ->pluck(Entity::SOURCE_ID)
                    ->toArray();
    }

    public function fetchPendingOrderTransfers(array $includeMerchantIds, array $excludeMerchantIds, int $count, int $minutes)
    {
        $orderId        = $this->repo->payment->dbColumn(Payment\Entity::ORDER_ID);
        $paymentStatus  = $this->repo->payment->dbColumn(Payment\Entity::STATUS);
        $merchantId     = $this->repo->transfer->dbColumn(Entity::MERCHANT_ID);
        $sourceId       = $this->repo->transfer->dbColumn(Entity::SOURCE_ID);
        $transferStatus = $this->repo->transfer->dbColumn(Entity::STATUS);
        $updatedAt      = $this->repo->transfer->dbColumn(Entity::UPDATED_AT);

        $query = $this->newQueryOnSlave();

        if ($this->isExperimentEnabledForId(self::PAYMENT_QUERIES_TIDB_MIGRATION, __FUNCTION__) === true)
        {
            $connectionType = $this->getPaymentFetchReplicaConnection();

            $query = $this->newQueryWithConnection($connectionType);
        }

        // If a list of merchantIds is given, we will fetch transfers only for those merchantIds. Else
        // fetch transfers for all merchants excluding key merchants.
        if (empty($includeMerchantIds) === false)
        {
            $query = $query->whereIn($merchantId, $includeMerchantIds);
        }
        else
        {
            $query = $query->whereNotIn($merchantId, $excludeMerchantIds);
        }

        return $query
                    ->join(Table::PAYMENT, $sourceId, '=', $orderId)
                    ->select(Entity::SOURCE_ID)
                    ->where(Entity::SOURCE_TYPE, Constant::ORDER)
                    ->where($transferStatus, Status::PENDING)
                    ->where($paymentStatus, Payment\Status::CAPTURED)
                    ->where($updatedAt, '<', Carbon::now()->subMinutes($minutes)->getTimestamp())
                    ->limit($count)
                    ->distinct()
                    ->pluck(Entity::SOURCE_ID)
                    ->toArray();
    }

    public function fetchPendingOrderTransfersForKeyMerchants(array $merchantIds, int $count, int $minutes)
    {
        $orderId        = $this->repo->payment->dbColumn(Payment\Entity::ORDER_ID);
        $paymentStatus  = $this->repo->payment->dbColumn(Payment\Entity::STATUS);
        $merchantId     = $this->repo->transfer->dbColumn(Entity::MERCHANT_ID);
        $sourceId       = $this->repo->transfer->dbColumn(Entity::SOURCE_ID);
        $transferStatus = $this->repo->transfer->dbColumn(Entity::STATUS);
        $updatedAt      = $this->repo->transfer->dbColumn(Entity::UPDATED_AT);

        $query = $this->newQueryOnSlave();

        if ($this->isExperimentEnabledForId(self::PAYMENT_QUERIES_TIDB_MIGRATION, __FUNCTION__) === true)
        {
            $connectionType = $this->getPaymentFetchReplicaConnection();

            $query = $this->newQueryWithConnection($connectionType);
        }

        return $query
                    ->join(Table::PAYMENT, $sourceId, '=', $orderId)
                    ->select(Entity::SOURCE_ID)
                    ->whereIn($merchantId, $merchantIds)
                    ->where(Entity::SOURCE_TYPE, Constant::ORDER)
                    ->where($transferStatus, Status::PENDING)
                    ->where($paymentStatus, Payment\Status::CAPTURED)
                    ->where($updatedAt, '<', Carbon::now()->subMinutes($minutes)->getTimestamp())
                    ->limit($count)
                    ->distinct()
                    ->pluck(Entity::SOURCE_ID)
                    ->toArray();
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
                        '<=',
                        Carbon::now(Timezone::IST)->subHours(3)->getTimestamp()
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

    public function saveOrFail($transfer, array $options = array())
    {
        $orderSource = $this->stripOrderSourceRelationIfApplicable($transfer);

        parent::saveOrFail($transfer, $options);

        $this->associateOrderSourceIfApplicable($transfer, $orderSource);
    }

    protected function stripOrderSourceRelationIfApplicable($transfer)
    {
        $source = $transfer->source;

        if (($source === null) or
            ($source->getEntityName() !== E::ORDER))
        {
            return;
        }

        $transfer->source()->dissociate();

        $transfer->setAttribute(Entity::SOURCE_ID, $source->getId());

        $transfer->setAttribute(Entity::SOURCE_TYPE, E::ORDER);

        return $source;
    }

    public function associateOrderSourceIfApplicable($transfer, $order)
    {
        if ($order === null)
        {
            return;
        }

        $transfer->source()->associate($order);
    }

    /**
     * Was used for data backfill activity.
     * Check updateSettlementStatusAndErrorCode() in Models\Transfer\Service.php for more.
     *
     * @param string $merchantId
     * @param int $startDate
     * @param int $endDate
     * @param int $skip
     * @param int $chunk
     * @return mixed
     */
//    public function getByMerchantId(string $merchantId, int $startDate, int $endDate, int $skip, int $chunk = 1000)
//    {
//        return $this->newQuery()
//                    ->select(Entity::ID)
//                    ->where(Entity::MERCHANT_ID, $merchantId)
//                    ->whereIn(Entity::SOURCE_TYPE, [Constant::PAYMENT, Constant::ORDER])
//                    ->whereIn(Entity::STATUS, [Status::PROCESSED, Status::REVERSED, Status::PARTIALLY_REVERSED, Status::FAILED])
//                    ->whereNull(Entity::SETTLEMENT_STATUS)
//                    ->where(Entity::CREATED_AT, '>=', $startDate)
//                    ->where(Entity::CREATED_AT, '<', $endDate)
//                    ->skip($skip)
//                    ->take($chunk)
//                    ->pluck(Entity::ID)
//                    ->toArray();
//    }
}

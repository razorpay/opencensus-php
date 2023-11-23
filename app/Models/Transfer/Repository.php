<?php

namespace RZP\Models\Transfer;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Order;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Settlement;
use RZP\Constants\Timezone;
use RZP\Constants\Entity as E;
use RZP\Models\Transaction\Entity as TxnEntity;

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

    public function fetchCreatedOrderTransfers(int $count, int $minutes)
    {
        $orderId        = $this->repo->payment->dbColumn(Payment\Entity::ORDER_ID);
        $paymentStatus  = $this->repo->payment->dbColumn(Payment\Entity::STATUS);
        $OrderStatus    = $this->repo->payment->dbColumn(Order\Entity::STATUS);
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
                    ->where(Entity::SOURCE_TYPE, Constant::ORDER)
                    ->where($transferStatus, Status::CREATED)
                    ->where($orderStatus, Order\Status::PAID)
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

    public function fetchPlatformFeeTransferDetailsForMerchant(string $merchantId, array $linkedAccounts, int $month, int $year)
    {
        $startOfMonth   = Carbon::create($year, $month);
        $endOfMonth     = Carbon::create($year, $month, $startOfMonth->daysInMonth, 23, 59, 59);

        $transferIdCol          = $this->dbColumn((Entity::ID));
        $transferToIdCol        = $this->dbColumn(Entity::TO_ID);
        $transferToTypeCol      = $this->dbColumn(Entity::TO_TYPE);
        $transferStatusCol      = $this->dbColumn(Entity::STATUS);

        $trxnEntityIdCol        = $this->repo->transaction->dbColumn(TxnEntity::ENTITY_ID);
        $trxnAmountCol          = $this->repo->transaction->dbColumn(TxnEntity::AMOUNT);
        $trxnFeeCol             = $this->repo->transaction->dbColumn(TxnEntity::FEE);
        $trxnTaxCol             = $this->repo->transaction->dbColumn(TxnEntity::TAX);
        $trxnCreatedAtCol       = $this->repo->transaction->dbColumn(TxnEntity::CREATED_AT);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->selectRaw('SUM(' . $trxnAmountCol . ') AS amount, SUM(' . $trxnTaxCol . ') AS tax, SUM(' . $trxnFeeCol . ') AS fee')
                      ->merchantId($merchantId)
                      ->join(Table::TRANSACTION, $trxnEntityIdCol, '=', $transferIdCol)
                      ->where($transferToTypeCol, '=', 'merchant')
                      ->whereIn($transferStatusCol, [Status::PROCESSED, Status::REVERSED, Status::PARTIALLY_REVERSED])
                      ->whereBetween($trxnCreatedAtCol, [$startOfMonth->timestamp, $endOfMonth->timestamp]);

        // for platform transfers, the transfer recipient should not be a linked account of the merchant
        // here, the transfer recipient is actually a linked account of partner
        if (empty($linkedAccounts) === false)
        {
            $query->whereNotIn($transferToIdCol, $linkedAccounts);
        }

        return $query->first();
    }

    /**
     * Fetch count of pending order transfers for the merchants belonging to the provided category codes
     *
     * Query:
     * select COUNT(transfers.id) AS count from `transfers`
     * inner join `payments` on `transfers`.`source_id` = `payments`.`order_id`
     * inner join `merchants` on `merchants`.`id` = `transfers`.`merchant_id`
     * where `source_type` = 'order'
     *     and `transfers`.`status` = 'pending' and `payments`.`status` = 'captured'
     *     and `transfers`.`updated_at` < ? and `merchants`.`category` in (?)
     *
     * @param $categoryCodes
     * @return int
     */
    public function fetchPendingOrderTransfersCount($categoryCodes = null): int
    {
        $orderId            = $this->repo->payment->dbColumn(Payment\Entity::ORDER_ID);
        $paymentStatus      = $this->repo->payment->dbColumn(Payment\Entity::STATUS);
        $sourceId           = $this->repo->transfer->dbColumn(Entity::SOURCE_ID);
        $merchantEntityId   = $this->repo->merchant->dbColumn(Merchant\Entity::ID);
        $merchantCategory   = $this->repo->merchant->dbColumn(Merchant\Entity::CATEGORY);
        $transferMerchantId = $this->repo->transfer->dbColumn(Entity::MERCHANT_ID);
        $transferStatus     = $this->repo->transfer->dbColumn(Entity::STATUS);
        $updatedAt          = $this->repo->transfer->dbColumn(Entity::UPDATED_AT);

        $query = $this->newQueryOnSlave();

        $query = $query
            ->join(Table::PAYMENT, $sourceId, '=', $orderId)
            ->join(Table::MERCHANT, $merchantEntityId, '=', $transferMerchantId)
            ->selectRaw('COUNT(' . 'transfers.id' . ') AS count')
            ->where(Entity::SOURCE_TYPE, Constant::ORDER)
            ->where($transferStatus, Status::PENDING)
            ->where($paymentStatus, Payment\Status::CAPTURED)
            ->where($updatedAt, '<', Carbon::now()->subDays(1)->getTimestamp());

        if ($categoryCodes !== null)
        {
            $query = $query->whereIn($merchantCategory, $categoryCodes);
        }
        else
        {
            $category3Codes = array_merge(Constant::CATEGORY_1_MCC, Constant::CATEGORY_2_MCC);
            $query = $query->whereNotIn($merchantCategory, $category3Codes);
        }

        $result =  $query->get();

        return $result[0]['count'];
    }

    /**
     * Fetch count of pending payment transfers for the merchants belonging to the provided category codes
     *
     *  Query:
     *  select COUNT(transfers.id) AS count from `transfers`
     *  inner join `merchants` on `merchants`.`id` = `transfers`.`merchant_id`
     *  where `transfers`.`source_type` = 'payment'
     *      and `transfers`.`status` = 'pending'
     *      and `transfers`.`updated_at` < ?
     *      and `merchants`.`category` in (?)
     *
     * @param $categoryCodes
     * @return int     */
    public function fetchPendingPaymentTransfersCount($categoryCodes = null): int
    {
        $merchantEntityId   = $this->repo->merchant->dbColumn(Merchant\Entity::ID);
        $merchantCategory   = $this->repo->merchant->dbColumn(Merchant\Entity::CATEGORY);
        $transferMerchantId = $this->repo->transfer->dbColumn(Entity::MERCHANT_ID);
        $sourceType         = $this->repo->transfer->dbColumn(Entity::SOURCE_TYPE);
        $status             = $this->repo->transfer->dbColumn(Entity::STATUS);
        $updatedAt          = $this->repo->transfer->dbColumn(Entity::UPDATED_AT);

        $query = $this->newQueryWithConnection($this->getSlaveConnection());

        $query = $query
            ->join(Table::MERCHANT, $merchantEntityId, '=', $transferMerchantId)
            ->selectRaw('COUNT(' . 'transfers.id' . ') AS count')
            ->where($sourceType, Constant::PAYMENT)
            ->where($status, Status::PENDING)
            ->where($updatedAt, '<', Carbon::now()->subDays(1)->getTimestamp());

        if ($categoryCodes !== null)
        {
            $query = $query->whereIn($merchantCategory, $categoryCodes);
        }
        else
        {
            $category3Codes = array_merge(Constant::CATEGORY_1_MCC, Constant::CATEGORY_2_MCC);
            $query = $query->whereNotIn($merchantCategory, $category3Codes);
        }

        $result = $query->get();

        return $result[0]['count'];
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

<?php

namespace RZP\Models\Settlement;

use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Merchant as M;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Base\ConnectionType;
use RZP\Models\Transfer\SettlementStatus;
use RZP\Modules\Acs\QueryShadowModeEvent;
use RZP\Models\Transfer\Entity as TransferEntity;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;

class Repository extends Base\Repository
{
    protected $entity = 'settlement';

    protected $signedIds = [
        Entity::BANK_ACCOUNT_ID,
        Entity::TRANSACTION_ID,
    ];

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID            => 'sometimes|alpha_num|size:14',
        Entity::BANK_ACCOUNT_ID        => 'sometimes|alpha_dash|min:14|max:17',
        Entity::BATCH_FUND_TRANSFER_ID => 'sometimes|alpha_num|max:14',
        Entity::TRANSACTION_ID         => 'sometimes|alpha_dash|min:14|max:18',
        Entity::STATUS                 => 'sometimes|in:created,processed,failed',
        Entity::UTR                    => 'sometimes|alpha_num',
    ];

    function __construct()
    {
        parent::__construct();

        $this->asvRouter = new AsvRouter();
    }

    /**
     * As a part of RSR-3104; now this settlement retry will be possible only
     * for the settlements created via API & won't work for settlement created via NSS.
     *
     * @param array $setlIds
     * @return mixed
     */
    public function getFailedSettlementsForRetry(array $setlIds)
    {
        $callingViaTidb = false;

        $merchantId = $this->repo->merchant->dbColumn(M\Entity::ID);

        $settlementId = $this->dbColumn(Entity::ID);
        $settlementMerchantId = $this->dbColumn(Entity::MERCHANT_ID);

        $cols = $this->dbColumn('*');

        if ($this->asvRouter->shouldRouteBeMigratedToTiDB(__FUNCTION__)) {
            $callingViaTidb = true;
            $query = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT));
        } else {
            $query = $this->newQuery();
        }

        $setls = $query->select($cols)
                      ->join(Table::MERCHANT, $merchantId, '=', $settlementMerchantId)
                      ->where(Entity::STATUS, '=', Status::FAILED)
                      ->whereIn($settlementId, $setlIds)
                      ->where(M\Entity::HOLD_FUNDS, '=', 0)
                      ->where(Entity::IS_NEW_SERVICE, '=', 0)
                      ->with('merchant', 'merchant.bankAccount');

        if (!$callingViaTidb and $this->asvRouter->shouldShadowCompareTiDBResults()) {
            event(new QueryShadowModeEvent(__FUNCTION__, $setls->toSql(), $query->getBindings()));
        }

        return $setls->get();
    }

    public function getSettlementWithFeesAsNullOrZero()
    {
        return $this->newQuery()
                    ->where(Entity::FEES, '=', '0')
                    ->orWhereNull(Entity::FEES)
                    ->get();
    }

    public function getSettlementWithTaxNullOrZero()
    {
        return $this->newQuery()
                    ->where(Entity::TAX, '=', '0')
                    ->orWhereNull(Entity::TAX)
                    ->get();
    }

    public function getSettlementsByBatchFundTransferId($batchFundTransferId)
    {
        return $this->newQuery()
                    ->where(Entity::BATCH_FUND_TRANSFER_ID, '=', $batchFundTransferId)
                    ->get();
    }

    public function getSettlementsBetweenTimestamp($from, $to)
    {
        return $this->newQuery()
                    ->whereBetween(Entity::CREATED_AT, [$from, $to])
                    ->get();
    }

    public function fetchSettlementSummaryBetweenTimestamp($from, $to)
    {
        return $this->newQuery()
                    ->whereBetween(Entity::CREATED_AT, [$from, $to])
                    ->groupBy(Entity::MERCHANT_ID)
                    ->selectRaw(Entity::MERCHANT_ID . ','.
                       'SUM(' . Entity::AMOUNT . ') AS sum' . ','.
                       'COUNT(*) AS count')
                    ->get();
    }

    public function getFewSettlementsWithNoCorrespondingSettlementDetails()
    {
        $setlIds = $this->db->select(
            'SELECT DISTINCT id FROM settlements
                WHERE settlements.id NOT IN
                    (SELECT DISTINCT settlements.id from settlements
                        JOIN settlement_details on settlements.id = settlement_details.settlement_id)
                LIMIT 20');

        $setlIds = json_decode(json_encode($setlIds), true);

        $setlIds2  = [];
        foreach ($setlIds as $setlId)
        {
            $setlIds2[] = $setlId['id'];
        }

        return $this->newQuery()
                    ->whereIn(Entity::ID, $setlIds2)
                    ->get();
    }

    public function updateChannel(string $settlementId, string $channel)
    {
        $values = [Entity::CHANNEL => $channel];

        $count = $this->newQuery()
                      ->where(Entity::ID, $settlementId)
                      ->where(Entity::STATUS, Status::FAILED)
                      ->update($values);

        if ($count !== 1)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed to update expected number to row',
                null,
                [
                    'settlement_id' => $settlementId,
                    'channel'       => $channel,
                ]);
        }

        return $count;
    }

    public function findBySettlementId(string $settlementId)
    {
        return $this->newQuery()
                    ->where(Entity::ID, $settlementId)
                    ->get();
    }

    public function findSettlementByUTR(string $utr)
    {
        $query = $this->newQuery()
                    ->where(Entity::UTR, $utr);

        return $query->first();
    }

    public function getSettlementsBetweenTimePeriodForMerchantIds($midList,$from,$to)
    {
        return $this->newQuery()
                    ->whereBetween(Entity::CREATED_AT, [$from, $to])
                    ->whereIn(Entity::MERCHANT_ID,$midList)
                    ->get();
    }

    public function getProcessedSettlementsForTimePeriodForMid($mid, $from, $to, $connection = null){
        try
        {
            $query = $this->newQueryOnSlave();
            if (isset($connection)) {
                $query = $this->newQueryWithConnection($connection);
            }
            return $query
                ->whereBetween(Entity::CREATED_AT, [$from, $to])
                ->where(Entity::MERCHANT_ID, $mid)
                ->where(Entity::STATUS, Status::PROCESSED)
                ->get();
        }
        catch (\Exception $e)
        {
            $this->trace->error(TraceCode::ERROR_EXCEPTION, [
                "error" => $e
            ]);
        }
    }

    public function fetchSettlementIdsWithIncorrectStatusOnTransfers(array $transferStatus = [], int $limit = 1000)
    {
        $idColumn                       = $this->dbColumn(Entity::ID);
        $statusColumn                   = $this->dbColumn(Entity::STATUS);
        $recipientSettlementIdColumn    = $this->repo->transfer->dbColumn(TransferEntity::RECIPIENT_SETTLEMENT_ID);
        $settlementStatusColumn         = $this->repo->transfer->dbColumn(TransferEntity::SETTLEMENT_STATUS);
        $transferStatusColumn           = $this->repo->transfer->dbColumn(TransferEntity::STATUS);


        $query = $this->newQueryOnSlave()
                      ->select($idColumn)
                      ->join(Table::TRANSFER, $idColumn, '=', $recipientSettlementIdColumn)
                      ->where($statusColumn, Status::PROCESSED)
                      ->where($settlementStatusColumn, '<>', SettlementStatus::SETTLED);

        if (empty($transferStatus) === false)
        {
            $query = $query->whereIn($transferStatusColumn, $transferStatus);
        }

        return $query->limit($limit)
                     ->distinct()
                     ->pluck($idColumn)
                     ->toArray();
    }
}

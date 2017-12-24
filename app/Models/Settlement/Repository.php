<?php

namespace RZP\Models\Settlement;

use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Merchant as M;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
use RZP\Models\BankAccount;

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

    public function getFailedSettlementsForRetry(array $setlIds, $channel)
    {
        $merchantId = $this->repo->merchant->dbColumn(M\Entity::ID);

        $settlementMerchantId = $this->dbColumn(Settlement\Entity::MERCHANT_ID);
        $settlementId = $this->dbColumn(Settlement\Entity::ID);

        $cols = $this->dbColumn('*');

        $setls = $this->newQuery()
                      ->select($cols)
                      ->join(Table::MERCHANT, $merchantId, '=', $settlementMerchantId)
                      ->where(Entity::STATUS, '=', Status::FAILED)
                      ->whereIn($settlementId, $setlIds)
                      ->where(M\Entity::HOLD_FUNDS, '=', 0)
                      ->where(Entity::CHANNEL, '=', $channel)
                      ->with('merchant', 'merchant.bankAccount', 'setlTransactions')
                      ->get();

        return $setls;
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
}

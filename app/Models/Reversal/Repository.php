<?php

namespace RZP\Models\Reversal;

use DB;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Error\ErrorCode;
use RZP\Models\Reversal;
use RZP\Constants\Timezone;
use RZP\Constants\Entity as E;
use RZP\Models\Payment\Refund;
use RZP\Models\Merchant\Balance;
use RZP\Models\Transaction\Type;
use RZP\Exception\LogicException;
use RZP\Models\Pricing\Calculator;
use RZP\Models\Transaction\CreditType;
use Illuminate\Database\Query\JoinClause;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Merchant\Invoice\Type as InvoiceType;
use RZP\Models\FundAccount\Validation\Entity as FavEntity;

class Repository extends Base\Repository
{
    protected $entity = 'reversal';

    protected $entityFetchParamRules = [
        Entity::ENTITY_TYPE     => 'sometimes|string|max:255',
        Entity::ENTITY_ID       => 'sometimes|string|size:14',
    ];

    protected $appFetchParamRules = [
        Entity::TRANSACTION_ID  => 'sometimes|alpha_num|size:14',
        Entity::MERCHANT_ID     => 'sometimes|alpha_num|size:14',
    ];

    /**
     * fetches reversals for a LA transfer by joining refunds
     *
     * @param string $transferId
     * @param string $merchantId
     *
     * @return array|mixed
     */
    public function fetchLaReversalsOfTransfer(string $transferId, string $merchantId)
    {
        $reversalColumns = $this->dbColumn('*');

        $reversalId = $this->repo->refund->dbColumn(Refund\Entity::REVERSAL_ID);

        $reversalsId = $this->repo->reversal->dbColumn(Reversal\Entity::ID);

        $refundNotes = $this->repo->refund->dbColumn(Refund\Entity::NOTES);

        $refundsTable = $this->repo->refund->getTableName();

        $reversalEntityType = $this->repo->reversal->dbColumn(Reversal\Entity::ENTITY_TYPE);

        $reversalEntityId = $this->repo->reversal->dbColumn(Reversal\Entity::ENTITY_ID);

        $refundMerchantId = $this->repo->refund->dbColumn(Refund\Entity::MERCHANT_ID);

        return $this->newQuery()
                    ->join($refundsTable, $reversalId, '=', $reversalsId)
                    ->select($reversalColumns, $refundNotes)
                    ->where($reversalEntityId, $transferId)
                    ->where($reversalEntityType, Reversal\Entity::TRANSFER)
                    ->where($refundMerchantId, $merchantId)
                    ->get();
    }

    public function fetchReversalsList($skip = 0, $take = 100, $entityType = Entity::TRANSFER)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_TYPE, $entityType)
                    ->orderBy(Entity::ID, 'desc')
                    ->skip($skip)
                    ->take($take)
                    ->get();
    }

    public function fetchFeesAndTaxForRefundByType(
        string $merchantId,
        int $start,
        int $end)
    {
        /*
            SELECT Sum(reversals.tax) AS tax,
                   Sum(reversals.fee) AS fee
            FROM   `reversals`
            WHERE  `reversals`.`entity_type` = ?
                   AND `reversals`.`created_at` BETWEEN ? AND ?
                   AND `reversals`.`merchant_id` = ?
            LIMIT  1
         */
        $query = $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
            ->selectRaw('SUM(' . $this->dbColumn(Entity::TAX) . ') AS tax, SUM(' . $this->dbColumn(Entity::FEE) . ') AS fee')
            ->where($this->dbColumn(Entity::ENTITY_TYPE), '=', E::REFUND)
            ->whereBetween($this->dbColumn(Entity::CREATED_AT), [$start, $end]);

        $query->merchantId($merchantId);

        return $query->first();
    }

    public function fetchFromUtr($utr, $amount, $balanceId): Base\Collection
    {
        $reversals = $this->newQuery()
                          ->where(Entity::BALANCE_ID, $balanceId)
                          ->where(Entity::UTR, $utr)
                          ->where(Entity::AMOUNT, $amount)
                          ->get();

        return $reversals;
    }

    /**
     * calculates the sum of `fee` and `tax` of all the created for a merchant for the given balance_id in the given time frame.
     *
     * select  SUM(payouts.tax) AS tax,SUM(payouts.fees) AS fee
     * from `reversals` inner join `payouts`
     * on `reversals`.`entity_id` = `payouts`.`id`
     * where `reversals`.`merchant_id` = ? and `entity_type` = payout
     * and `payouts`.`fee_type` is null
     * and `reversals`.`created_at` between ? and ?
     * and `reversals`.`balance_id` = ?
     *
     * @param $merchantId
     * @param $balanceId
     * @param $startTime
     * @param $endTime
     *
     * @return mixed
     */
    public function fetchSumOfFeesAndTaxForReversalPayoutsForGivenBalanceId($merchantId, $balanceId, $startTime, $endTime)
    {
        $balanceIDColumn            = $this->dbColumn(Entity::BALANCE_ID);
        $reversalsEntityIDColumn    = $this->dbColumn(Entity::ENTITY_ID);
        $reversalsCreatedAtColumn   = $this->repo->reversal->dbColumn(Entity::CREATED_AT);

        $payoutsTaxColumn           = $this->repo->payout->dbColumn(Entity::TAX);
        $payoutsFeeColumn           = $this->repo->payout->dbColumn(PayoutEntity::FEES);
        $payoutsIDColumn            = $this->repo->payout->dbColumn(Entity::ID);
        $payoutsFeeTypeColumn       = $this->repo->payout->dbColumn(PayoutEntity::FEE_TYPE);
        $payoutsFailedAtColumn      = $this->repo->payout->dbColumn(PayoutEntity::FAILED_AT);

        $columns = ' SUM(' . $payoutsTaxColumn . ') AS tax,
                     SUM(' . $payoutsFeeColumn . ') AS fee';

        return $this->newQueryWithConnection($this->getPaymentFetchReplicaConnection())
                    ->selectRaw($columns)
                    ->join(Table::PAYOUT, $reversalsEntityIDColumn, $payoutsIDColumn)
                    ->merchantID($merchantId)
                    ->where(Entity::ENTITY_TYPE, Type::PAYOUT)
                    ->whereNull($payoutsFeeTypeColumn)
                    ->whereNull($payoutsFailedAtColumn)
                    ->whereBetween($reversalsCreatedAtColumn, [$startTime, $endTime])
                    ->where($balanceIDColumn, $balanceId)
                    ->first();
    }

    /**
     * @param $merchantId
     * @param $count
     * @param int $skip
     * @return mixed
     */
    public function fetchreversals($merchantId, $count, $skip = 0)
    {
        return $this->newQuery()
                      ->merchantId($merchantId)
                      ->where(Entity::ENTITY_TYPE,'=','transfer')
                      ->orderBy(Entity::CREATED_AT, 'desc')
                      ->orderBy(Entity::ID, 'desc')
                      ->take($count)
                      ->skip($skip)
                      ->get();
    }

    public function fetchFeesAndIdOfReversalsForGivenBalanceIdForPeriod($merchantId, $balanceId, $from, $to)
    {
        $reversalsIdColumn          = $this->dbColumn(Entity::ID);
        $reversalsEntityIdColumn    = $this->dbColumn(Entity::ENTITY_ID);
        $balanceIdColumn            = $this->dbColumn(Entity::BALANCE_ID);
        $entityTypeColumn           = $this->dbColumn(Entity::ENTITY_TYPE);
        $reversalsCreatedAtColumn   = $this->repo->reversal->dbColumn(Entity::CREATED_AT);

        $payoutsTable               = Table::PAYOUT;
        $payoutsIdColumn            = $this->repo->payout->dbColumn(PayoutEntity::ID);
        $payoutsFeesColumn          = $this->repo->payout->dbColumn(PayoutEntity::FEES);
        $payoutsFeeTypeColumn       = $this->repo->payout->dbColumn(PayoutEntity::FEE_TYPE);
        $payoutsFailedAtColumn      = $this->repo->payout->dbColumn(PayoutEntity::FAILED_AT);

        return $this->newQuery()
                    ->select($reversalsIdColumn, $payoutsFeesColumn)
                    ->join($payoutsTable, $reversalsEntityIdColumn, '=', $payoutsIdColumn)
                    ->merchantID($merchantId)
                    ->where($entityTypeColumn, Type::PAYOUT)
                    ->whereBetween($reversalsCreatedAtColumn, [$from, $to])
                    ->whereNull($payoutsFailedAtColumn)
                    ->where($balanceIdColumn, $balanceId)
                    ->where(DB::raw('COALESCE(' . $payoutsFeeTypeColumn. ', "")'), '!=', CreditType::REWARD_FEE)
                    ->get();
    }

    public function fetchFeesForReversalIds($reversalIds, $merchantId, $balanceId)
    {
        $payoutsTable           = $this->repo->payout->getTableName();
        $payoutsIdColumn        = $this->repo->payout->dbColumn(PayoutEntity::ID);
        $payoutsFeesColumn      = $this->repo->payout->dbColumn(PayoutEntity::FEES);
        $payoutsFailedAtColumn  = $this->repo->payout->dbColumn(PayoutEntity::FAILED_AT);

        $reversalsIdColumn          = $this->dbColumn(Entity::ID);
        $reversalsEntityIdColumn    = $this->dbColumn(Entity::ENTITY_ID);
        $reversalsBalanceIdColumn   = $this->dbColumn(Entity::BALANCE_ID);

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->selectRaw(' SUM(' . $payoutsFeesColumn . ') AS fees')
                    ->join($payoutsTable, $reversalsEntityIdColumn, '=', $payoutsIdColumn)
                    ->merchantId($merchantId)
                    ->where($reversalsBalanceIdColumn, $balanceId)
                    ->whereIn($reversalsIdColumn, $reversalIds)
                    ->whereNull($payoutsFailedAtColumn)
                    ->first();
    }

    public function findReversalForPayout($payoutId)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $payoutId)
                    ->where(Entity::ENTITY_TYPE, Type::PAYOUT)
                    ->first();
    }

    public function findReversalForPayouts($payoutIds)
    {
        return $this->newQuery()
                    ->whereIn(Entity::ENTITY_ID, $payoutIds)
                    ->where(Entity::ENTITY_TYPE, Type::PAYOUT)
                    ->get();
    }

    /**
     * This will fetch all reversals for payouts and fund_account_validations
     * which are created in the last 24 hour after 05-02-2022 and where transaction_id is null.
     * @param int $days
     * @param int $limit
     * @return mixed
     */
    public function fetchReversalAndTxnIdNullBetweenTimestamp(int $days, int $limit)
    {
        $currentTime = Carbon::now(Timezone::IST)->subMinutes(15)->subDays($days);
        $currentTimeStamp = $currentTime->getTimestamp();

        $lastTimestamp = $currentTime->subDay()->getTimestamp();
        $txnIdFillingTimestamp = Carbon::createFromFormat('d-m-Y', '05-02-2022', Timezone::IST)->getTimestamp();

        if ($lastTimestamp < $txnIdFillingTimestamp)
        {
            $lastTimestamp = $txnIdFillingTimestamp;
        }

        $balanceIdColumn            = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceTypeColumn          = $this->repo->balance->dbColumn(Balance\Entity::TYPE);
        $balanceAccountTypeColumn   = $this->repo->balance->dbColumn(Balance\Entity::ACCOUNT_TYPE);

        $revTransactionIdColumn = $this->repo->reversal->dbColumn(Entity::TRANSACTION_ID);
        $revEntityType          = $this->repo->reversal->dbColumn(Entity::ENTITY_TYPE);
        $revEntityIdColumn      = $this->repo->reversal->dbColumn(Entity::ENTITY_ID);
        $revBalanceIdColumn     = $this->repo->reversal->dbColumn(Entity::BALANCE_ID);
        $revCreatedAtColumn     = $this->dbColumn(Entity::CREATED_AT);

        $revAttrs = $this->dbColumn('*');

        $payoutTxnIdColumn = $this->repo->payout->dbColumn(PayoutEntity::TRANSACTION_ID);
        $payoutIdColumn    = $this->repo->payout->dbColumn(PayoutEntity::ID);
        $favTxnIdColumn    = $this->repo->fund_account_validation->dbColumn(FavEntity::TRANSACTION_ID);
        $favIdColumn       = $this->repo->payout->dbColumn(FavEntity::ID);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                    ->join(Table::BALANCE, $balanceIdColumn, '=', $revBalanceIdColumn)
                    ->leftjoin(Table::PAYOUT, $revEntityIdColumn, '=', $payoutIdColumn)
                    ->leftjoin(Table::FUND_ACCOUNT_VALIDATION, $revEntityIdColumn, '=',$favIdColumn)
                    ->select($revAttrs)
                    ->whereIn($revEntityType, [Type::PAYOUT, Type::FUND_ACCOUNT_VALIDATION])
                    ->where($balanceTypeColumn, '=', Balance\Type::BANKING)
                    ->where($balanceAccountTypeColumn, '=', Balance\AccountType::SHARED)
                    ->whereNull($revTransactionIdColumn)
                    ->whereBetween($revCreatedAtColumn, [$lastTimestamp, $currentTimeStamp])
                    ->where(function($query) use ($favTxnIdColumn, $payoutTxnIdColumn)
                    {
                        $query->whereNotNull($payoutTxnIdColumn)
                              ->OrWhereNotNull($favTxnIdColumn);
                    })
                    ->limit($limit);

        return $query->get();
    }

    /**
     * This will fetch all reversals in created state where id is in the given list of ids
     * and where transaction_id is null.
     * @param array $ids
     * @return mixed
     */
    public function fetchReversalWhereTxnIdNullAndIdsIn(array $ids)
    {
        $balanceIdColumn            = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceTypeColumn          = $this->repo->balance->dbColumn(Balance\Entity::TYPE);
        $balanceAccountTypeColumn   = $this->repo->balance->dbColumn(Balance\Entity::ACCOUNT_TYPE);

        $revIdColumn            = $this->repo->reversal->dbColumn(Entity::ID);
        $revTransactionIdColumn = $this->repo->reversal->dbColumn(Entity::TRANSACTION_ID);
        $revEntityType          = $this->repo->reversal->dbColumn(Entity::ENTITY_TYPE);
        $revEntityIdColumn      = $this->repo->reversal->dbColumn(Entity::ENTITY_ID);
        $revBalanceIdColumn     = $this->repo->reversal->dbColumn(Entity::BALANCE_ID);

        $revAttrs = $this->dbColumn('*');

        $payoutTxnIdColumn = $this->repo->payout->dbColumn(PayoutEntity::TRANSACTION_ID);
        $payoutIdColumn    = $this->repo->payout->dbColumn(PayoutEntity::ID);
        $favTxnIdColumn    = $this->repo->fund_account_validation->dbColumn(FavEntity::TRANSACTION_ID);
        $favIdColumn       = $this->repo->payout->dbColumn(FavEntity::ID);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                    ->join(Table::BALANCE, $balanceIdColumn, '=', $revBalanceIdColumn)
                    ->leftjoin(Table::PAYOUT, $revEntityIdColumn, '=', $payoutIdColumn)
                    ->leftjoin(Table::FUND_ACCOUNT_VALIDATION, $revEntityIdColumn, '=', $favIdColumn)
                    ->select($revAttrs)
                    ->whereIn($revEntityType, [Type::PAYOUT, Type::FUND_ACCOUNT_VALIDATION])
                    ->where($balanceTypeColumn, '=', Balance\Type::BANKING)
                    ->where($balanceAccountTypeColumn, '=', Balance\AccountType::SHARED)
                    ->whereNull($revTransactionIdColumn)
                    ->whereIn($revIdColumn, $ids)
                    ->where(function($query) use ($favTxnIdColumn, $payoutTxnIdColumn)
                    {
                        $query->whereNotNull($payoutTxnIdColumn)
                              ->OrWhereNotNull($favTxnIdColumn);
                    });

        return $query->get();
    }

    public function getPayoutServiceReversalByPayoutId(string $payoutId)
    {
        $tableName = Table::REVERSAL;

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_reversals';
        }

        return \DB::connection($this->getPayoutsServiceConnection())
                  ->select("select * from $tableName where payout_id = '$payoutId'");
    }

    public function getPayoutServiceReversalById(string $id)
    {
        $tableName = Table::REVERSAL;

        if (in_array($this->app['env'], ['testing', 'testing_docker'], true) === true)
        {
            $tableName = 'ps_reversals';
        }

        return \DB::connection($this->getPayoutsServiceConnection())
                  ->select("select * from $tableName where id = '$id' limit 1");
    }

    public function findReversalByRefundId(string $refundId)
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
                      ->where(Entity::ENTITY_TYPE, E::REFUND)
                      ->where(Entity::ENTITY_ID, $refundId);

        return $query->first();
    }

}

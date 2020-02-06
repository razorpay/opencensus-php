<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\FundAccount\Type;
use RZP\Models\Merchant\Balance;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::FUND_ACCOUNT_VALIDATION;

    protected $expands = [
        Entity::FUND_ACCOUNT,
    ];

    public function getFundAccountValidationsToRetry($time, $count)
    {
        return $this->newQuery()
            ->select(Entity::ID)
            ->where(Entity::RETRY_AT, '<', $time)
            ->where(Entity::STATUS, "=" , Status::CREATED)
            ->where(Entity::FUND_ACCOUNT_TYPE, "=", Type::BANK_ACCOUNT)
            ->take($count)
            ->orderBy(Entity::RETRY_AT, 'asc')
            ->get()->pluck('id')->all();
    }

    /**
     * calculates the sum of `fee` and `tax` of all the fund_account_validations created in a given time frame
     *
     * select SUM(tax) AS tax,SUM(fees) AS fee
     * from `fund_account_validations`
     * where `fund_account_validations`.`merchant_id` = ?
     * and `fund_account_validations`.`balance_id` = ?
     * and `fund_account_validations`.`created_at` between ? and ?
     *
     * @param string $merchantId
     * @param string $balanceId
     * @param int    $startTime
     * @param int    $endTime
     *
     * @return mixed
     */
    public function fetchFeesAndTaxForFAVsForGivenBalanceId(
        string $merchantId,
        string $balanceId,
        int $startTime,
        int $endTime)
    {
        $favsBalanceIdColumn = $this->dbColumn(Entity::BALANCE_ID);
        $favsCreatedAtColumn = $this->dbColumn(Entity::CREATED_AT);

        return $this->newQuery()
                    ->selectRaw(
                        'SUM(' . Entity::TAX .') AS tax,
                         SUM(' . Entity::FEES . ') AS fee')
                    ->merchantId($merchantId)
                    ->where($favsBalanceIdColumn, $balanceId)
                    ->whereBetween($favsCreatedAtColumn, [$startTime, $endTime])
                    ->first();
    }
}

<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Constants;

class Repository extends Base\Repository
{
    protected $entity = 'fund_transfer_attempt';

    protected $signedIds = [
        Entity::BANK_ACCOUNT_ID,
    ];

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::SOURCE_TYPE            => 'sometimes|string|custom',
        Entity::SOURCE_ID              => 'sometimes|alpha_dash|min:14|max:19',
        Entity::MERCHANT_ID            => 'sometimes|alpha_num|size:14',
        Entity::STATUS                 => 'sometimes|string',
        Entity::UTR                    => 'sometimes|alpha_num',
        Entity::BATCH_FUND_TRANSFER_ID => 'sometimes|alpha_num|size:14',
        Entity::VERSION                => 'sometimes|string',
    ];

    protected function validateSourceType($attribute, $value)
    {
        return Type::validateType($value);
    }

    public function getFundTransferAttemptsByBatchIdWithRelations(
        string $batchFundTransferId,
        array $relations = [])
    {
        $query = $this->newQuery()
                      ->where(Entity::BATCH_FUND_TRANSFER_ID, '=', $batchFundTransferId);

        if (count($relations) > 0)
        {
            $query->with($relations);
        }

        return $query->get();
    }

    /**
     * Fetches created attempts that are to be populated in the payouts file.
     *
     * This does not (and should not) include attempts of type settlement.
     * Those are never in created state, but this may change in the future,
     * so source_type filter is added anyway.
     *
     * @param  int    $timestamp Upper limit on created_at, usually set to now
     * @param  array  $relations Relations required in the process
     */
    public function getCreatedAttemptsBeforeTimestamp(int $timestamp, array $relations = [])
    {
        $query = $this->newQuery()
                      ->where(Entity::STATUS, '=', Status::CREATED)
                      ->where(Entity::SOURCE_TYPE, '!=', Constants\Entity::SETTLEMENT)
                      ->where(Entity::CREATED_AT, '<=', $timestamp)
                      ->orderBy(Entity::ID);

        if (count($relations) > 0)
        {
            $query->with($relations);
        }

        return $query->get();
      }

    /**
     * Fetches all attempts pending reconciliation between given timstamps (both including)
     */
    public function getAttemptsBetweenTimestampsWithStatus(int $from, int $to, string $status)
    {
        return $this->newQuery()
                    ->whereBetween(Entity::CREATED_AT, [$from, $to])
                    ->where(Entity::STATUS, '=', $status)
                    ->get();
    }
}

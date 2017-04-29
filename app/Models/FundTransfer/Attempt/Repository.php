<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'fund_transfer_attempt';

    protected $signedIds = [
        Entity::BANK_ACCOUNT_ID,
    ];

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::SOURCE_TYPE            => 'sometimes|string|in:settlement',
        Entity::SOURCE_ID              => 'sometimes|alpha_dash|min:14|max:19',
        Entity::STATUS                 => 'sometimes|string',
        Entity::UTR                    => 'sometimes|alpha_num',
        Entity::BATCH_FUND_TRANSFER_ID => 'sometimes|alpha_num|size:14',
        Entity::VERSION                => 'sometimes|string',
    ];

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
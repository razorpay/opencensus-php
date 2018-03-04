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

    protected function addQueryParamSourceId($query, $params)
    {
        $id = $params[Entity::SOURCE_ID];

        if (strpos($id, '_') !== false)
        {
            list($sign, $id) = explode('_', $id);
        }

        $query->where(Entity::SOURCE_ID, '=', $id);
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
     * @param  int    $initiateAtTimestamp Upper limit limit on initiate_at
     * @param  array  $relations Relations required in the process
     */
    public function getCreatedAttemptsBeforeTimestamp(
        int $initiateAtTimestamp,
        string $purpose,
        $type = null,
        string $channel,
        array $relations = [])
    {
        $query = $this->newQuery()
                      ->where(Entity::STATUS, '=', Status::CREATED)
                      ->where(Entity::PURPOSE, '=', $purpose)
                      ->where(Entity::INITIATE_AT, '<=', $initiateAtTimestamp)
                      ->where(Entity::CHANNEL, '=', $channel)
                      ->orderBy(Entity::ID);

        if ($type !== null)
        {
          $query->where(Entity::SOURCE_TYPE, '=', $type);
        }

        if (count($relations) > 0)
        {
            $query->with($relations);
        }

        return $query->get();
      }

    /**
     * Fetches all attempts pending reconciliation between given timestamps (both including)
     */
    public function getAttemptsBetweenTimestampsWithStatus(
        $from = null, $to = null, string $status, string $channel)
    {
        $query = $this->newQuery()
                      ->select([Entity::ID, Entity::BATCH_FUND_TRANSFER_ID])
                      ->where(Entity::STATUS, $status)
                      ->where(Entity::CHANNEL, $channel)
                      ->whereNotNull(Entity::BANK_STATUS_CODE);

        if (($from !== null) and ($to !== null))
        {
            $query = $query->whereBetween(Entity::CREATED_AT, [$from, $to]);
        }

        return $query->get();
    }
}

<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Merchant\Entity as MerchantEntity;

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
        Entity::CHANNEL                => 'sometimes|string'
    ];

    protected function validateSourceType($attribute, $value)
    {
        Type::validateType($value);
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
     * @param int      $initiateAtTimestamp Upper limit limit on initiate_at
     * @param string   $purpose
     * @param null     $type
     * @param string   $channel
     * @param int|null $limit
     * @param array    $relations           Relations required in the process
     *
     * @return Base\PublicCollection
     */
    public function getCreatedAttemptsBeforeTimestamp(
        int $initiateAtTimestamp,
        string $purpose,
        $type = null,
        string $channel,
        int $limit = null,
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

        if ($limit !== null)
        {
            $query->limit($limit);
        }

        if (count($relations) > 0)
        {
            $query->with($relations);
        }

        return $query->get();
    }

    /**
     * Fetches all attempts pending reconciliation between given timestamps (both including)
     *
     * @param string $channel
     * @param string $status
     * @param null   $from
     * @param null   $to
     * @param int    $limit
     * @param int    $offset
     *
     * @return mixed
     */
    public function getAttemptsBetweenTimestampsWithStatus(
        string $channel, string $status, $from = null, $to = null, $limit = null, int $offset = null)
    {
        $query = $this->newQuery()
                      ->where(Entity::STATUS, $status)
                      ->where(Entity::CHANNEL, $channel)
                      ->whereNotNull(Entity::BANK_STATUS_CODE);

        if (($from !== null) and ($to !== null))
        {
            $query = $query->whereBetween(Entity::CREATED_AT, [$from, $to]);
        }

        if ($limit !== null)
        {
            $query->take($limit);
        }

        if ($offset !== null)
        {
            $query->skip($offset);
        }

        //
        // Here we are fetching the data in random order because
        // in API mode we always fetch 100 attempts for reconciliation.
        // We do this because if we pick latest records then there is
        // a chance that few transactions wont be reconciled at all.
        //
        return $query->inRandomOrder()->get();
    }

    public function getAttemptsBetweenTimestamps(string $status, string $channel, int $from = null, int $to = null)
    {
        $query = $this->newQuery()
                      ->select([Entity::ID, Entity::BATCH_FUND_TRANSFER_ID])
                      ->where(Entity::STATUS, $status)
                      ->where(Entity::CHANNEL, $channel);

        if (($from !== null) and ($to !== null))
        {
            $query = $query->whereBetween(Entity::CREATED_AT, [$from, $to]);
        }

        return $query->get();
    }

    public function getSettlementsWithNoUtr(
        string $channel,
        int $startTime,
        int $endTime,
        int $limit = 2000,
        int $offset = 0)
    {
        return $this->newQuery()
                    ->whereNull(Entity::UTR)
                    ->where(Entity::CHANNEL, $channel)
                    ->where(Entity::STATUS, Status::INITIATED)
                    ->whereBetween(Entity::INITIATE_AT, [$startTime, $endTime])
                    ->with(['merchant'])
                    ->take($limit)
                    ->skip($offset)
                    ->get();
    }

    public function getFailedAttemptsInitiatedAtBetweenTime(
        string $channel,
        int $startTime,
        int $endTime,
        int $limit = 2000,
        int $offset = 0)
    {
        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::FAILED)
                    ->where(Entity::CHANNEL, $channel)
                    ->whereBetween(Entity::INITIATE_AT, [$startTime, $endTime])
                    ->with(['merchant', 'source'])
                    ->take($limit)
                    ->skip($offset)
                    ->get();
    }
}

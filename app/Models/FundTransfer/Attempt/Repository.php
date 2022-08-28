<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Settlement\Channel;
use RZP\Models\Merchant\Entity as MerchantEntity;

class Repository extends Base\Repository
{
    const FETCH_LIMIT = 100;

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
        Entity::CHANNEL                => 'sometimes|string',
        Entity::GATEWAY_REF_NO         => 'sometimes|string',
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
     * @param $unsupportedModeList
     * @param string   $channel
     * @param int|null $limit
     * @param array    $relations Relations required in the process
     *
     * @param int      $isFTS
     * @return Base\PublicCollection
     */
    public function getCreatedAttemptsBeforeTimestamp(
        int $initiateAtTimestamp,
        string $purpose,
        $type = null,
        string $channel,
        $unsupportedModeList = [],
        int $limit = null,
        array $relations = [],
        int $isFTS = 0)
    {
        //
        // It'll fetch only those FTA's which are not available with FTS
        //
        $query = $this->newQuery()
                      ->where(Entity::STATUS, '=', Status::CREATED)
                      ->where(Entity::PURPOSE, '=', $purpose)
                      ->where(Entity::INITIATE_AT, '<=', $initiateAtTimestamp)
                      ->where(Entity::CHANNEL, '=', $channel)
                      ->where(Entity::IS_FTS, '=', $isFTS)
                      ->orderBy(Entity::ID);

        // Cron should not pick NEFT, RTGS Mode attempts for razorpayX Payout outside timing window
        // Since it affects the Other Mode Payout to get processed e.g. IMPS. UPI etc
        if ((in_array($channel, Channel::getFTASupportedPayoutChannels(), true) === true) and
            (empty($unsupportedModeList) === false))
        {
            $query->where(function($query) use ($unsupportedModeList)
            {
                $query->whereNotIn(Entity::MODE, $unsupportedModeList)->orWhereNull(Entity::MODE);
            });
        }

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
        string $channel, string $status, $from = null, $to = null, $limit = null, int $offset = null, bool $isFTS = false)
    {
        $query = $this->newQuery()
                      ->where(Entity::STATUS, $status)
                      ->where(Entity::CHANNEL, $channel)
                      ->where(Entity::IS_FTS, $isFTS)
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

    public function getAttemptsBetweenTimestamps(string $status, string $channel, int $from = null, int $to = null, bool $isFTS = false)
    {
        $query = $this->newQuery()
                      ->select([Entity::ID, Entity::BATCH_FUND_TRANSFER_ID])
                      ->where(Entity::STATUS, $status)
                      ->where(Entity::IS_FTS, $isFTS)
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
                    ->whereBetween(Entity::CREATED_AT, [$startTime, $endTime])
                    ->with(['merchant'])
                    ->take($limit)
                    ->skip($offset)
                    ->get();
    }

    public function getFailedAttemptsCreatedBetweenTime(
        string $channel,
        int $startTime,
        int $endTime,
        int $limit = 2000,
        int $offset = 0)
    {
        return $this->newQuery()
                    ->where(Entity::STATUS, '=', Status::FAILED)
                    ->where(Entity::CHANNEL, $channel)
                    ->whereBetween(Entity::CREATED_AT, [$startTime, $endTime])
                    ->with(['merchant', 'source'])
                    ->take($limit)
                    ->skip($offset)
                    ->get();
    }

    /**
     * @param string   $channel
     * @param string   $status
     * @param null     $from
     * @param null     $to
     * @param null     $limit
     * @param int|null $offset
     * @param bool     $isFTS
     * @return mixed
     */
    public function getAttemptsWithStatusBetweenTimestamps(
        string $channel, string $status = null, $from = null, $to = null, $limit = null, int $offset = null, bool $isFTS = false)
    {
        $query = $this->newQuery()
                      ->where(Entity::CHANNEL, $channel)
                      ->where(Entity::IS_FTS, $isFTS);

        if (($from !== null) and ($to !== null))
        {
            $query = $query->whereBetween(Entity::CREATED_AT, [$from, $to]);
        }

        if ($status !== null)
        {
            $query = $query->where(Entity::STATUS, $status);
        }

        if ($limit !== null)
        {
            $query->take($limit);
        }

        if ($offset !== null)
        {
            $query->skip($offset);
        }

        return $query->get();
    }

    public function getAttemptsWithIds(
        string $channel, array $ids, int $limit = null, int $offset = null, bool $isFTS = false)
    {
        $query = $this->newQuery()
                      ->where(Entity::CHANNEL, $channel)
                      ->whereIn(Entity::ID, $ids)
                      ->where(Entity::IS_FTS, $isFTS);

        if ($limit !== null)
        {
            $query->take($limit);
        }

        if ($offset !== null)
        {
            $query->skip($offset);
        }

        return $query->get();
    }

    public function getFailedSettlements(
        int $startTime,
        int $endTime,
        int $limit = 2000,
        int $offset = 0)
    {
        return $this->newQuery()
                    ->where(Entity::STATUS, Status::FAILED)
                    ->whereBetween(Entity::CREATED_AT, [$startTime, $endTime])
                    ->with(['merchant'])
                    ->take($limit)
                    ->skip($offset)
                    ->get();
    }

    public function findByIdWithStatus(string $id, string $status = null, $isFTS = false)
    {
        $query = $this->newQuery()
                      ->where(Entity::ID, $id);

        //
        // if $isFTS is null then on checks based on fts flag is done
        // if $isFTS is not null then corresponding filter will be applied
        // null case will occurs in case of reconciliation
        // recon has to run on all the attempts which are in initiated state
        // in case of transfer and status $isFTS will be false
        // so only attempts which are not sent to FTS will be picked for processing
        //
        if ($isFTS !== null)
        {
            $query = $query->where(Entity::IS_FTS, $isFTS);
        }


        if (empty($status) === false)
        {
            $query = $query->where(Entity::STATUS, $status);
        }

        return $query->first();
    }

    public function getAttemptByFTSTransferId($ftsTransferId)
    {
        return $this->newQuery()
                    ->where(Entity::FTS_TRANSFER_ID, $ftsTransferId)
                    ->first();
    }

    public function getFTSAttemptBySourceId(string $sourceId, string $sourceType, bool $isFTS = false)
    {
        return $this->newQuery()
                    ->where(Entity::SOURCE_ID, $sourceId)
                    ->where(Entity::SOURCE_TYPE, $sourceType)
                    ->where(Entity::IS_FTS, $isFTS)
                    ->first();
    }

    public function getAttemptBySourceIdAndNotFailed($sourceId, $sourceType, bool $isFTS = false)
    {
        return $this->newQuery()
                    ->where(Entity::SOURCE_ID, $sourceId)
                    ->where(Entity::SOURCE_TYPE, $sourceType)
                    ->where(Entity::STATUS, '!=' ,Status::FAILED)
                    ->where(Entity::IS_FTS, $isFTS)
                    ->get();
    }

    public function getAttemptBySourceId(string $sourceId, string $sourceType)
    {
        return $this->newQuery()
                    ->where(Entity::SOURCE_ID, $sourceId)
                    ->where(Entity::SOURCE_TYPE, $sourceType)
                    ->first();
    }


    /**
     * @param string $channel
     * @param string $status
     * @param null $size
     * @param null $id
     * @param null $from
     * @param null $to
     * @param null $limit
     * @return mixed
     */
    public function getFtsAttempts(
        string $channel,
        string $status,
        $size = null,
        $id = null,
        $from = null,
        $to = null,
        $limit=null)
    {

        $query = $this->newQuery()
                      ->where(Entity::CHANNEL, $channel)
                      ->where(Entity::STATUS, '=', $status)
                      ->where(Entity::IS_FTS, '=', 1);

        if ($size !== null)
        {
            $query->take($size);
        }

        if ($id !== null)
        {
            $query->where(Entity::ID, '=', $id);
        }

        if (($from != null) and ($to != null))
        {
            $query->whereBetween(Entity::CREATED_AT, [$from, $to])
                  ->limit($limit);
        }


        return $query->get();
    }

    public function fetchFtsAttemptUsingId(array $ids)
    {
        $query = $this->newQuery()
                      ->whereIn(Entity::ID, $ids)
                      ->where(Entity::IS_FTS, '=', 1)
                      ->limit(self::FETCH_LIMIT);

        $query->with(['source']);

        return $query->get();
    }

    public function saveOrFail($fundTransferAttempt, array $options = array())
    {
        $card = $this->stripCardRelationIfApplicable($fundTransferAttempt);

        parent::saveOrFail($fundTransferAttempt, $options);

        $this->associateCardIfApplicable($fundTransferAttempt, $card);
    }

    public function save($fundTransferAttempt, array $options = array())
    {
        $card = $this->stripCardRelationIfApplicable($fundTransferAttempt);

        parent::save($fundTransferAttempt, $options);

        $this->associateCardIfApplicable($fundTransferAttempt, $card);
    }

    public function associateCardIfApplicable($fundTransferAttempt, $card)
    {
        if ($card === null)
        {
            return;
        }

        $fundTransferAttempt->card()->associate($card);
    }

    protected function stripCardRelationIfApplicable(Entity $fundTransferAttempt)
    {
        $card = $fundTransferAttempt->card;

        if (($card == null) ||
            ($card->isExternal() === false))
        {
            return;
        }

        $fundTransferAttempt->card()->dissociate();

        $fundTransferAttempt->setCardId($card->getId());

        return $card;
    }
}

<?php

namespace RZP\Models\Merchant\Credits;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Promotion;

class Repository extends Base\Repository
{
    protected $entity = 'credits';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::CAMPAIGN                => 'sometimes|string|max:255',
        Entity::MERCHANT_ID             => 'sometimes|string',
        Entity::TYPE                    => 'sometimes|string|max:20',
    );

    // These are proxy allowed params to search on.
    protected $proxyFetchParamRules = array(
        Entity::CAMPAIGN                => 'sometimes|string|max:255',
        Entity::TYPE                    => 'sometimes|string|max:20',
    );

    /**
     * Checks if a record exists by Merchant ID and Campaign Name
     * in credits table.
     *
     * @return bool
     */
    public function creditsLogExists($campaign, Merchant\Entity $merchant, $type)
    {
        return $this->newQuery()
                    ->where(Entity::CAMPAIGN, '=', $campaign)
                    ->where(Entity::CAMPAIGN, '=', $type)
                    ->merchantId($merchant->getId())
                    ->exists();
    }

    public function validateCampaignCreditsNotAssigned($campaign, Merchant\Entity $merchant, $type)
    {
        // Check if the log already exists, API is meant to be used for creation only.
        $creditsLog = $this->newQuery()
            ->where(Entity::CAMPAIGN, '=', $campaign)
            ->where(Entity::TYPE, '=', $type)
            ->merchantId($merchant->getId())
            ->first();

        if ($creditsLog)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The campaign credits has already been assigned to merchant. ' .
                'Credits Id: ' . $creditsLog->getId());
        }
    }

    /**
     * SELECT *
     * FROM   `credits`
     * WHERE  `credits`.`merchant_id` = $merchantId
     *   AND `type` = $type
     *   AND value > used
     *   AND ( `expired_at` > $timestamp
     *       OR `expired_at` IS NULL )
     *   ORDER  BY -`expired_at` DESC
     */
    public function getCreditsSortedByExpiry(int $timestamp, string $merchantId, string $type)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where(Entity::TYPE, '=', $type)
                    ->whereRaw(Entity::VALUE . '>' . Entity::USED)
                    ->where(function ($query) use ($timestamp)
                        {
                            $query->where(Entity::EXPIRED_AT, '>', $timestamp)
                                  ->orWhereNull(Entity::EXPIRED_AT);
                        }
                    )
                    // This is done because we want to keep the null EXPIRED at the bottom
                    ->orderBy(\DB::raw('-`expired_at`'), 'desc')
                    ->get();
    }

    public function getCreditsSortedByExpiryWithBalance(int $timestamp, string $merchantId, string $type, string $balanceId)
    {
        return $this->newQuery()
            ->merchantId($merchantId)
            ->where(Entity::TYPE, '=', $type)
            ->where(Entity::BALANCE_ID, $balanceId)
            ->whereRaw(Entity::VALUE . '>' . Entity::USED)
            ->where(function ($query) use ($timestamp)
            {
                $query->where(Entity::EXPIRED_AT, '>', $timestamp)
                    ->orWhereNull(Entity::EXPIRED_AT);
            }
            )
            // This is done because we want to keep the null EXPIRED at the bottom
            ->orderBy(\DB::raw('-`expired_at`'), 'desc')
            ->get();
    }

    /**
     * Returns Credit entities
     *
     * @param array $creditIds
     * @return mixed
     */
    public function getCreditEntities(array $creditIds)
    {
        return $this->newQuery()
                    ->whereIn(Entity::ID, $creditIds)
                    ->get();
    }

    public function findCreditsToExpire(string $merchantId, string $promotionId, int $timestamp)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where(Entity::PROMOTION_ID, '=', $promotionId)
                    ->where(Entity::EXPIRED_AT, '<' , $timestamp)
                    ->whereRaw(Entity::VALUE . '>' . Entity::USED)
                    ->first();
    }

    public function getMerchantCreditsOfType(string $merchantId, string $type): int
    {
        if ($type === Type::REFUND)
        {
            $query = $this->newQuery()
                ->selectRaw('SUM(value - used) as sum')
                ->merchantId($merchantId)
                ->where(function ($query)
                {
                    $query->where(Entity::EXPIRED_AT, '>', time())
                        ->orWhereNull(Entity::EXPIRED_AT);
                }
                )
                ->where(Entity::TYPE, '=', $type)
                ->first();
        }
        else
        {
            $query = $this->newQuery()
                ->selectRaw('SUM(value - used) as sum')
                ->merchantId($merchantId)
                ->where(Entity::VALUE, '>', 0)
                ->where(function ($query)
                {
                    $query->where(Entity::EXPIRED_AT, '>', time())
                        ->orWhereNull(Entity::EXPIRED_AT);
                }
                )
                ->where(Entity::TYPE, '=', $type)
                ->first();
        }

        return $query->getAttribute('sum');
    }

    /**
     * Returns the sum of unused, non-expired credits for a merchant, for each credit type
     *
     * Sample return array:
     * [
     *  'amount' => 1000
     *  'fee'    => 550
     * ]
     *
     * @param string $merchantId
     *
     * @return array
     */
    public function getTypeAggregatedMerchantCredits(string $merchantId): array
    {
        $query = $this->newQuery()
                      ->selectRaw(
                          Entity::TYPE . ', ' .
                          'SUM(' . Entity::VALUE . ' - ' . Entity::USED . ') AS sum')
                      ->where(Entity::VALUE, '>', 0)
                      ->merchantId($merchantId)
                      ->where(function ($query)
                          {
                              $query->where(Entity::EXPIRED_AT, '>', time())
                                    ->orWhereNull(Entity::EXPIRED_AT);
                          })
                      ->groupBy(Entity::TYPE)
                      ->get();

        $data = [];

        foreach ($query as $record)
        {
            $data[$record[Entity::TYPE]] = $record['sum'];
        }

        return $data;
    }

    public function fetchByIdempotencyKey(string $idempotencyKey, string $batchId, Merchant\Entity $merchant)
    {
        return $this->newQuery()
                    ->where(Entity::IDEMPOTENCY_KEY, '=', $idempotencyKey)
                    ->where(Entity::BATCH_ID, $batchId)
                    ->where(Entity::MERCHANT_ID, $merchant->getId())
                    ->first();
    }

    public function findExistingCreditsForMerchantAndPromotion(Promotion\Entity $promotion, Merchant\Entity $merchant)
    {
        return $this->newQuery()
                    ->where(Entity::PROMOTION_ID, $promotion->getId())
                    ->where(Entity::MERCHANT_ID, $merchant->getId())
                    ->first();
    }

    public function getCreditLockForUpdate($credit)
    {
        assertTrue ($this->isTransactionActive());

        return Entity::lockForUpdate()->newQuery()
                                      ->where(Entity::ID, $credit->getId())
                                      ->firstOrFail();
    }
}

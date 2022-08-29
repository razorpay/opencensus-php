<?php

namespace RZP\Models\Merchant\Credits;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Promotion;
use RZP\Constants\Product;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;

class Repository extends Base\Repository
{
    protected $entity = 'credits';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::CAMPAIGN                => 'sometimes|string|max:255',
        Entity::MERCHANT_ID             => 'sometimes|string',
        Entity::TYPE                    => 'sometimes|string|max:20',
        Entity::FETCH_EXPIRED           => 'sometimes|boolean',
        Entity::IS_PROMOTION            => 'sometimes|boolean',
    );

    // These are proxy allowed params to search on.
    protected $proxyFetchParamRules = array(
        Entity::CAMPAIGN                => 'sometimes|string|max:255',
        Entity::TYPE                    => 'sometimes|string|max:20',
        Entity::FETCH_EXPIRED           => 'sometimes|boolean',
        Entity::IS_PROMOTION            => 'sometimes|boolean',
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
    public function getCreditsSortedByExpiry(int $timestamp, Merchant\Entity $merchant, string $type)
    {
        assertTrue($this->isTransactionActive());

        $merchantsCredits = $this->newQuery()
            ->merchantId($merchant->getId())
            ->get();

        $creditsFiltered = $merchantsCredits->filter(function ($item) use ($type) {
            return (
                ($item->getUnusedCredits() > 0) and
                (($item->getExpiredAt() === null) or ($item->getExpiredAt() > time())) and
                ($item->getType() === $type)
            );
        });

        $creditIds = $creditsFiltered->getStringAttributesByKey('id');

        $creditIds = array_keys($creditIds);

        if (count($creditIds) < 1)
        {
            return new PublicCollection;
        }

        return Entity::lockForUpdate()->newQuery()
            ->whereIn(Entity::ID, $creditIds)
            ->orderBy(\DB::raw('-`expired_at`'), 'desc')
            ->get();
    }

    public function getCreditsSortedByExpiryForProduct(int $timestamp, string $merchantId, string $type, string $product)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where(Entity::TYPE, '=', $type)
                    ->whereRaw(Entity::VALUE . '>' . Entity::USED)
                    ->where(Entity::PRODUCT, $product)
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

    public function getCreditEntitiesLockForUpdate(array $creditIds)
    {
        return Entity::lockForUpdate()->newQuery()
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

        if ($query->getAttribute('sum') !== null)
        {
            return $query->getAttribute('sum');
        }
        return 0;
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
     * @param Merchant\Entity $merchant
     *
     * @return array
     */
    public function getTypeAggregatedNonRefundMerchantCredits(Merchant\Entity $merchant): array
    {
        assertTrue($this->isTransactionActive());

        $merchantsCredits = $this->newQuery()
            ->merchantId($merchant->getId())
            ->get();

        // filtering out refund credits as it is not required here
        // helps avoid possible deadlock because of opposite lock orders on credits in refunds flow
        // slack ref thread: https://razorpay.slack.com/archives/CNXC0JHQF/p1641983505105000
        $creditsFiltered = $merchantsCredits->filter(function ($item) {
            return ($item->getUnusedCredits() > 0) and (($item->getExpiredAt() == null) or
                    ($item->getExpiredAt() > time())) and ($item->getType() !== Type::REFUND);
        });

        $creditIds = $creditsFiltered->getStringAttributesByKey('id');

        $creditIds = array_keys($creditIds);

        $data = [];

        if (count($creditIds) > 0)
        {
            $credits = Entity::lockForUpdate()->newQuery()
                ->whereIn(Entity::ID, $creditIds)
                ->get();

            foreach ($credits as $credit)
            {
                if (isset($data[$credit->getType()]) === false)
                {
                    $data[$credit->getType()] = 0;
                }

                $data[$credit->getType()] += $credit->getUnusedCredits();
            }
        }

        return $data;
    }

    /**
     * Returns the sum of unused, non-expired credits for a merchant, for each credit type
     * This function is explicitly only called by payment flow
     * Sample return array:
     * [
     *  'amount' => 1000
     *  'fee'    => 550
     * ]
     *
     * @param Merchant\Entity $merchant
     *
     * @return array
     */
    public function getTypeAggregatedMerchantCreditsForPayment(Merchant\Entity $merchant): array
    {
        assertTrue($this->isTransactionActive());

        $merchantsCredits = $this->newQuery()
            ->merchantId($merchant->getId())
            ->get();

       //filtering only amount and fee credit, since these are two credits used in payment flow
        $creditsFiltered = $merchantsCredits->filter(function ($item) {
            return ($item->getUnusedCredits() > 0) and (($item->getExpiredAt() == null) or
                    ($item->getExpiredAt() > time())) and (($item->getType() == Type::AMOUNT)
                or ($item->getType() == Type::FEE));
        });

        $creditIds = $creditsFiltered->getStringAttributesByKey('id');

        $creditIds = array_keys($creditIds);

        $data = [];

        if (count($creditIds) > 0)
        {
            $credits = Entity::lockForUpdate()->newQuery()
                ->whereIn(Entity::ID, $creditIds)
                ->get();

            foreach ($credits as $credit)
            {
                if (isset($data[$credit->getType()]) === false)
                {
                    $data[$credit->getType()] = 0;
                }

                $data[$credit->getType()] += $credit->getUnusedCredits();
            }
        }

        return $data;
    }

    /** @param Merchant\Entity $merchant
     *
     * @return array
     */
    public function getMerchantCreditsForRefund(Merchant\Entity $merchant): int
    {
        assertTrue($this->isTransactionActive());

        $merchantsCredits = $this->newQuery()
            ->merchantId($merchant->getId())
            ->get();

        //filtering only refund credits
        $creditsFiltered = $merchantsCredits->filter(function ($item) {
            return ($item->getUnusedCredits() > 0) and (($item->getExpiredAt() == null) or
                    ($item->getExpiredAt() > time())) and ($item->getType() == Type::REFUND);
        });

        $creditIds = $creditsFiltered->getStringAttributesByKey('id');

        $creditIds = array_keys($creditIds);

        $data = [];

        if (count($creditIds) > 0)
        {
            $credits = Entity::lockForUpdate()->newQuery()
                ->whereIn(Entity::ID, $creditIds)
                ->get();

            foreach ($credits as $credit)
            {
                if (isset($data[$credit->getType()]) === false)
                {
                    $data[$credit->getType()] = 0;
                }

                $data[$credit->getType()] += $credit->getUnusedCredits();
            }
        }

        return $data[Type::REFUND] ?? 0;
    }

    public function getTypeAggregatedMerchantCreditsForProduct(string $merchantId, string $product): array
    {
        $results =  $this->newQuery()
                        ->selectRaw(
                            Entity::TYPE . ', ' .
                            'SUM(' . Entity::VALUE . ' - ' . Entity::USED . ') AS sum')
                        ->where(Entity::VALUE, '>', 0)
                        ->where(Entity::PRODUCT, $product)
                        ->merchantId($merchantId)
                        ->where(function ($query)
                        {
                            $query->where(Entity::EXPIRED_AT, '>', time())
                                ->orWhereNull(Entity::EXPIRED_AT);
                        })
                        ->groupBy(Entity::TYPE)
                        ->get();

        $data = [];

        foreach ($results as $record)
        {
            $data[$record[Entity::TYPE]] = $record['sum'];
        }

        return $data;
    }

    public function getTypeAggregatedMerchantCredits(string $merchantId): array
    {
        $results =  $this->newQuery()
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

        foreach ($results as $record)
        {
            $data[$record[Entity::TYPE]] = $record['sum'];
        }

        return $data;
    }

    public function getTypeAggregatedMerchantCreditsLockForUpdate(string $merchantId): array
    {
        assertTrue($this->isTransactionActive());

        $merchantsCredits = $this->newQuery()
            ->merchantId($merchantId)
            ->get();

        $creditsFiltered = $merchantsCredits->filter(function ($item) {
            return ($item->getUnusedCredits() > 0) and (($item->getExpiredAt() == null) or
                    ($item->getExpiredAt() > time()));
        });

        $creditIds = $creditsFiltered->getStringAttributesByKey('id');

        $creditIds = array_keys($creditIds);

        $data = [];

        if (count($creditIds) > 0)
        {
            $credits = Entity::lockForUpdate()->newQuery()
                ->whereIn(Entity::ID, $creditIds)
                ->get();

            foreach ($credits as $credit)
            {
                if (isset($data[$credit->getType()]) === false)
                {
                    $data[$credit->getType()] = 0;
                }

                $data[$credit->getType()] += $credit->getUnusedCredits();
            }
        }

        return $data;
    }
    public function getTypeAggregatedMerchantCreditsForProductForDashboard(string $merchantId, string $product): array
    {
        $results =  $this->newQuery()
                        ->selectRaw(
                            Entity::TYPE . ', ' .
                            'SUM(' . Entity::VALUE . ' - ' . Entity::USED . ') AS sum')
                        ->where(Entity::PRODUCT, $product)
                        ->merchantId($merchantId)
                        ->where(function ($query)
                        {
                            $query->where(Entity::EXPIRED_AT, '>', time())
                                ->orWhereNull(Entity::EXPIRED_AT);
                        })
                        ->groupBy(Entity::TYPE)
                        ->get();

        $data = [];

        foreach ($results as $record)
        {
            $data[] = [
                Entity::PRODUCT                                 => $product,
                Entity::MERCHANT_ID                             => $merchantId,
                Merchant\Credits\Balance\Entity::BALANCE        => (int) $record['sum'],
                Entity::TYPE                                    => $record[Entity::TYPE],
                // ToDo refactor this. Expire_at should be picked from the query
                Entity::EXPIRED_AT                              => null
            ];
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

    public function getCreditsForMerchant(string $merchantId, string $product = null, string $type = null)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::PRODUCT, $product)
                    ->where(Entity::TYPE, $type)
                    ->get();
    }

    protected function addQueryParamFetchExpired($query, $params)
    {
        // by default, fetch expired credits
        $fetchExpired = (bool) ($params[Entity::FETCH_EXPIRED] ?? false);

        if ($fetchExpired === true)
        {
            return;
        }

        $timestamp = Carbon::now(Timezone::IST)->timestamp;

        $query->where(function ($query) use ($timestamp)
        {
            $query->where(Entity::EXPIRED_AT, '>', $timestamp)
                  ->orWhereNull(Entity::EXPIRED_AT);
        });
    }

    protected function addQueryParamIsPromotion($query, $params)
    {
        $isPromotion = (bool) ($params[Entity::IS_PROMOTION] ?? false);

        if ($isPromotion === true)
        {
            $query->whereNotNull(Entity::PROMOTION_ID);
        }
        else
        {
            $query->whereNull(Entity::PROMOTION_ID);
        }
    }

    public function findByCampaignId($campaign, $merchantId)
    {
        return $this->newQuery()
            ->where(Entity::CAMPAIGN, '=', $campaign)
            ->merchantId($merchantId)
            ->exists();
    }

    public function getUnexpiredCreditIdsForMerchantOfType(string $merchantId, string $type)
    {
        return $this->newQuery()
            ->select(Entity::ID)
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::TYPE, '=', $type)
            ->where(Entity::VALUE, '>', 0)
            ->where(Entity::EXPIRED_AT, '>' ,Carbon::Now()->getTimestamp())
            ->pluck(Entity::ID)
            ->toArray();
    }
}

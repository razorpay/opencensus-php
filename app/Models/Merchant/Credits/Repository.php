<?php

namespace RZP\Models\Merchant\Credits;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Credits;

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

    public function getCreditsSortedWithExpiry(int $timestamp, string $merchantId, string $type)
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

    public function findCreditsToExpire(string $merchantId, string $promotionId, int $timestamp)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where(Entity::PROMOTION_ID, '=', $promotionId)
                    ->where(Entity::EXPIRED_AT, '<' , $timestamp)
                    ->whereRaw(Entity::VALUE . '>' . Entity::USED)
                    ->first();
    }

    public function getMerchantCredits(string $merchantId, string $type)
    {
        $query = $this->newQuery()
                    ->selectRaw('sum(value - used) as sum')
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(function ($query)
                        {
                            $query->where(Entity::EXPIRED_AT, '>', time())
                              ->orWhereNull(Entity::EXPIRED_AT);
                        }
                    )
                    ->where(Entity::TYPE, '=', $type)
                    ->first();

        return $query->getAttribute('sum');
    }
}

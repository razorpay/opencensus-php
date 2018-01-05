<?php

namespace RZP\Models\Offer;

use DB;
use Carbon\Carbon;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'offer';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID         => 'sometimes|alpha_num',
        Entity::PAYMENT_METHOD      => 'sometimes|alpha',
        Entity::PAYMENT_METHOD_TYPE => 'sometimes|alpha',
        Entity::PAYMENT_NETWORK     => 'sometimes|alpha',
        Entity::ISSUER              => 'sometimes|alpha',
    ];

    /**
     * set of attributes required to uniquely define an offer
     */
    const OFFER_FETCH_ATTRIBUTES = [
        Entity::PAYMENT_METHOD,
        Entity::PAYMENT_METHOD_TYPE,
        Entity::PAYMENT_NETWORK,
        Entity::ISSUER,
        Entity::PERCENT_RATE,
        Entity::MIN_AMOUNT,
        Entity::MAX_CASHBACK,
        Entity::FLAT_CASHBACK,
    ];

    public function fetchExistingOffers(Entity $newOffer, string $merchantId)
    {
        $query = $this->buildQuery($newOffer, $merchantId);

        $query->where(Entity::ACTIVE, '=', true)
              ->where(Entity::STARTS_AT, '<=', $newOffer->getAttribute(Entity::ENDS_AT))
              ->where(Entity::ENDS_AT, '>=', $newOffer->getAttribute(Entity::STARTS_AT));

        return $query->get();
    }

    public function fetchOffersForCheckout(array $merchantIds)
    {
        $now = Carbon::now()->getTimestamp();

        return $this->newQuery()
                    ->whereIn(Entity::MERCHANT_ID, $merchantIds)
                    ->where(Entity::ACTIVE, '=', true)
                    ->where(Entity::CHECKOUT_DISPLAY, '=', true)
                    ->where(Entity::STARTS_AT, '<=', $now)
                    ->where(Entity::ENDS_AT, '>=', $now)
                    ->get();
    }

    public function fetchActiveExpiredOffers()
    {
        $now = Carbon::now()->getTimestamp();

        return $this->newQuery()
                    ->where(Entity::ACTIVE, '=', true)
                    ->where(Entity::ENDS_AT, '<', $now)
                    ->get();
    }

    /**
     * Build a query based upon the attribute set in the new offer entity,
     * to check whether an offer exists with the same condition.
     *
     * @param  Entity $newOffer
     * @param  string $merchantId
     *
     * @return $query
     */
    protected function buildQuery(Entity $newOffer, string $merchantId)
    {
        $query = $this->newQuery()
                      ->where(Entity::MERCHANT_ID, '=', $merchantId);

        foreach (self::OFFER_FETCH_ATTRIBUTES as $attribute)
        {
            if ($newOffer->getAttribute($attribute) !== null)
            {
                $query->where($attribute, '=', $newOffer->getAttribute($attribute));
            }
        }

        return $query;
    }
}

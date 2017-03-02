<?php

namespace RZP\Models\Offer;

use DB;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\Merchant\Account;

class Repository extends Base\Repository
{
    protected $entity = 'offer';

    protected $appFetchParamRules = [
        Entity::PAYMENT_METHOD            => 'sometimes|alpha',
        Entity::PAYMENT_METHOD_TYPE       => 'sometimes|alpha',
        Entity::PAYMENT_NETWORK           => 'sometimes|alpha',
        Entity::ISSUER                    => 'sometimes|alpha',
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

    public function fetchForOrder(Order\Entity $order)
    {
        if ($order->hasRelation('offer'))
        {
            return $order->offer;
        }

        $offerId = $order->getOfferId();

        $offer = $this->findOrFail($offerId);

        $order->offer()->associate($offer);

        return $offer;
    }

    public function fetchExistingOffers(Entity $newOffer, string $merchantId)
    {
        $query = $this->buildQuery($newOffer, $merchantId);

        $query->where(Entity::ACTIVE, '=', true)
              ->where(Entity::STARTS_AT, '<=', $newOffer->getAttribute(Entity::ENDS_AT))
              ->where(Entity::ENDS_AT, '>=', $newOffer->getAttribute(Entity::STARTS_AT));

        return $query->get();
    }

    public function fetchSharedOffers()
    {
        $now = Carbon::now('Asia/Kolkata')->timestamp;

        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', Account::SHARED_ACCOUNT)
                    ->where(Entity::ACTIVE, '=', true)
                    ->where(Entity::STARTS_AT, '<=', $now)
                    ->where(Entity::ENDS_AT, '>=', $now)
                    ->get();
    }

    public function fetchActiveExpiredOffers()
    {
        $now = Carbon::now('Asia/Kolkata')->timestamp;

        return $this->newQuery()
                    ->where(Entity::ACTIVE, '=', true)
                    ->where(Entity::ENDS_AT, '<', $now)
                    ->get();
    }

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

<?php

namespace RZP\Models\Offer;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Builder;
use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Offer\SubscriptionOffer\Entity as SubscriptionOfferEntity;
use RZP\Models\Order\ProductType;

class Repository extends Base\Repository
{
    protected $entity = 'offer';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID         => 'sometimes|alpha_num',
        Entity::PAYMENT_METHOD      => 'sometimes|string',
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
        Entity::INTERNATIONAL,
        Entity::EMI_SUBVENTION,
        Entity::IINS,
    ];

    /**
     * Fetches all active offers for a given merchant.
     *
     * @param string $merchantId
     *
     * @return Builder[]|Base\PublicCollection
     */
    public function fetchAllActiveNonSubscriptionOffers(string $merchantId): Base\PublicCollection
    {
        $now = Carbon::now()->getTimestamp();

        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::ACTIVE, '=', true)
            ->where(Entity::STARTS_AT, '<=', $now)
            ->where(Entity::ENDS_AT, '>=', $now)
            ->where(function (Builder $query) {
                $query->where(Entity::PRODUCT_TYPE, '!=', ProductType::SUBSCRIPTION)
                    ->orWhereNull(Entity::PRODUCT_TYPE);
            })
            ->get();
    }

    public function fetchExistingOffers(Entity $newOffer, string $merchantId)
    {
        $query = $this->buildQuery($newOffer, $merchantId);

        $query->where(Entity::ACTIVE, '=', true)
              ->where(Entity::STARTS_AT, '<=', $newOffer->getAttribute(Entity::ENDS_AT))
              ->where(Entity::ENDS_AT, '>=', $newOffer->getAttribute(Entity::STARTS_AT))
              ->where(Entity::PRODUCT_TYPE, '=', $newOffer->getAttribute(Entity::PRODUCT_TYPE));

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

    public function fetchOffersSubscription($paymentMethods = null, $merchantId, $offerId = null): Base\PublicCollection
    {
        $now = Carbon::now()->getTimestamp();

        $offerIdCol = $this->dbColumn(Entity::ID);

        $subOfferIdCol = $this->repo->subscription_offers_master->dbColumn(SubscriptionOfferEntity::OFFER_ID);
        $subApplOnCol = $this->repo->subscription_offers_master->dbColumn(SubscriptionOfferEntity::APPLICABLE_ON);
        $subRedempTypeCol = $this->repo->subscription_offers_master->dbColumn(SubscriptionOfferEntity::REDEMPTION_TYPE);
        $subCycleCol = $this->repo->subscription_offers_master->dbColumn(SubscriptionOfferEntity::NO_OF_CYCLES);

        $offerQuery = $this->newQuery()
                            ->select($this->dbColumn('*'),
                                $subOfferIdCol, $subApplOnCol, $subRedempTypeCol, $subCycleCol)
                            ->join(Table::SUBSCRIPTION_OFFERS_MASTER, $offerIdCol, '=', $subOfferIdCol)
                            ->where(Entity::MERCHANT_ID, $merchantId)
                            ->where(Entity::ACTIVE, '=', true)
                            ->where(Entity::STARTS_AT, '<=', $now)
                            ->where(Entity::ENDS_AT, '>=', $now)
                            ->where(Entity::PRODUCT_TYPE, '=', 'subscription');

        if ($paymentMethods !== null)
        {
            $offerQuery->whereIn(Entity::PAYMENT_METHOD, $paymentMethods);
        }

        if ($offerId !== null)
        {
            $offerQuery->where($offerIdCol , '=', $offerId);
        }

        return $offerQuery->get();
    }

    public function fetchSubscriptionOfferById(string $offerId, bool $fetchActive = true, bool $fetchExpired = false)
    {
        $now = Carbon::now()->getTimestamp();

        $offerIdCol = $this->dbColumn(Entity::ID);

        $subOfferIdCol = $this->repo->subscription_offers_master->dbColumn(SubscriptionOfferEntity::OFFER_ID);
        $subApplOnCol = $this->repo->subscription_offers_master->dbColumn(SubscriptionOfferEntity::APPLICABLE_ON);
        $subRedempTypeCol = $this->repo->subscription_offers_master->dbColumn(SubscriptionOfferEntity::REDEMPTION_TYPE);
        $subCycleCol = $this->repo->subscription_offers_master->dbColumn(SubscriptionOfferEntity::NO_OF_CYCLES);

        $offerQuery = $this->newQuery()
                           ->select($this->dbColumn('*'), $this->repo->subscription_offers_master->dbColumn('*'))
                           ->select($this->dbColumn('*'), $subOfferIdCol, $subApplOnCol, $subRedempTypeCol, $subCycleCol)
                           ->join(Table::SUBSCRIPTION_OFFERS_MASTER, $offerIdCol, '=', $subOfferIdCol)
                           ->where(Entity::STARTS_AT, '<=', $now)
                           ->where(Entity::PRODUCT_TYPE, '=', 'subscription')
                           ->where($offerIdCol , '=', $offerId);

        if ($fetchActive === true)
        {
            $offerQuery->where(Entity::ACTIVE, '=', true);
        }

        if ($fetchExpired === false)
        {
            $offerQuery->where(Entity::ENDS_AT, '>=', $now);
        }

        return $offerQuery->first();
    }

    public function fetchActiveExpiredOffers()
    {
        $now = Carbon::now()->getTimestamp();

        return $this->newQuery()
                    ->where(Entity::ACTIVE, '=', true)
                    ->where(Entity::ENDS_AT, '<', $now)
                    ->get();
    }

    public function fetchAllDefaultOffersForMerchant(string $merchantId)
    {
        return $this->newQuery()
            ->where(Entity::DEFAULT_OFFER, '=', true)
            ->where(Entity::ACTIVE, '=', true)
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
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
                if ($attribute === Entity::IINS)
                {
                    $query->where($attribute, '=', json_encode($newOffer->getAttribute($attribute)));
                }
                else
                {
                    $query->where($attribute, '=', $newOffer->getAttribute($attribute));
                }
            }
        }

        return $query;
    }
}

<?php

namespace RZP\Models\Offer;

use Carbon\Carbon;
use DB;

use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Account;

class Repository extends Base\Repository
{
    const OFFER_ID    = 'offer_id';
    const MERCHANT_ID = 'merchant_id';

    protected $entity = 'offer';

    protected $entityFetchParamRules = [
        Entity::PAYMENT_METHOD            => 'sometimes|alpha',
        Entity::PAYMENT_METHOD_TYPE       => 'sometimes|alpha',
        Entity::PAYMENT_NETWORK           => 'sometimes|alpha',
        Entity::ISSUER                    => 'sometimes|alpha',
    ];

    /**
     * set of attributes required to uniquely define an offer
     */
    protected $offerFetchAttributes = [
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

    public function fetchSharedOffers()
    {
        $now = Carbon::now('Asia/Kolkata')->timestamp;

        return $this->newQuery()->where(Entity::MERCHANT_ID, '=', Account::SHARED_ACCOUNT)
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
                    ->where(Entity::ENDS_AT, '<=', $now)
                    ->get();
    }

    protected function buildQuery(Entity $newOffer, string $merchantId)
    {
        $query = $this->newQuery()->where(Entity::MERCHANT_ID, '=', $merchantId);

        foreach ($this->offerFetchAttributes as $attribute)
        {
            if ($newOffer->getAttribute($attribute) !== null)
            {
                $query->where($attribute, '=', $newOffer->getAttribute($attribute));
            }
        }

        return $query;
    }
}

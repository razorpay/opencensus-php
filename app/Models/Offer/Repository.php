<?php

namespace RZP\Models\Offer;

use DB;

use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Merchant;
use Carbon\Carbon;

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
        Entity::PERCENT_RATE              => 'sometimes|integer|min:0|max:10000',
        Entity::MAX_CASHBACK              => 'sometimes|integer|min:0',
        Entity::FLAT_CASHBACK             => 'sometimes|integer|min:0',
        Entity::MIN_AMOUNT                => 'sometimes|integer|min:0',
        Entity::PAYMENT_COUNT             => 'sometimes|integer|min:1',
        Entity::PROCESSING_TIME           => 'sometimes|integer',
        Entity::STARTS_AT                 => 'sometimes|integer',
        Entity::ENDS_AT                   => 'sometimes|integer',
        Entity::ADDITIONAL_DETAILS        => 'sometimes|string|max:40',
        Entity::CUSTOM_LONG_DISPLAY_TEXT  => 'sometimes|string|max:200',
        Entity::CUSTOM_SHORT_DISPLAY_TEXT => 'sometimes|string|max:50'
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
        Entity::FLAT_CASHBACK
    ];

    public function fetchExistingOffers(array $input, string $merchantId)
    {
        $offerFetchParams = [];

        foreach ($this->offerFetchAttributes as $attribute)
        {
            if (isset($input[$attribute]) === true)
            {
                $offerFetchParams[$attribute] = $input[$attribute];
            }
        }

        return $this->fetch($offerFetchParams, $merchantId);
    }

    public function fetchActiveOfferByMerchantAndMethod(Merchant\Entity $merchant, string $method)
    {
        $now = Carbon::now('Asia/Kolkata')->timestamp;

        $offers = $this->fetchMerchantOffersQuery($merchant)
                        ->where(Entity::PAYMENT_METHOD, '=', $method)
                        ->where(Entity::STARTS_AT, '<=', $now)
                        ->where(Entity::ENDS_AT, '>', $now)
                        ->where(Entity::ACTIVE, '=', 1)
                        ->get();

        return $offers;
    }
}

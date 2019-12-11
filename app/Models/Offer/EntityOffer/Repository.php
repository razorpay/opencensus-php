<?php

namespace RZP\Models\Offer\EntityOffer;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::ENTITY_OFFER;

    protected $appFetchParamRules = [
        Entity::ENTITY_TYPE => 'filled|string|in:payment,order',
        Entity::OFFER_ID    => 'filled|string|min:14|max:20',
    ];

    protected $signedIds = [
        Entity::OFFER_ID,
    ];

    //
    // Default order defined in RepositoryFetch is created_at, id
    // Overriding here because pivot table does not have an id col.
    //
    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::CREATED_AT, 'desc');
    }

    public function getOfferIdLinkedWithPayment($paymentIds)
    {
        return $this->newQuery()
                    ->whereIn(Entity::ENTITY_ID, $paymentIds)
                    ->where(Entity::ENTITY_TYPE, '=', 'payment')
                    ->get();
    }
}

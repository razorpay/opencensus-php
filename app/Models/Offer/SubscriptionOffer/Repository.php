<?php

namespace RZP\Models\Offer\SubscriptionOffer;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::SUBSCRIPTION_OFFERS_MASTER;

    protected $appFetchParamRules = [
        Entity::OFFER_ID               => 'sometimes',
        Entity::APPLICABLE_ON          => 'sometimes',
        Entity::REDEMPTION_TYPE        => 'sometimes',
        Entity::NO_OF_CYCLES           => 'sometimes',
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
}

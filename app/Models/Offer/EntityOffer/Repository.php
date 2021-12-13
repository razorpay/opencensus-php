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

    public function findByEntityIdAndType($entityId, $entityOfferType)
    {
        $query = $this->newQuery()
                      ->where(Entity::ENTITY_ID, '=', $entityId)
                      ->where(Entity::ENTITY_OFFER_TYPE, '=', $entityOfferType);

        return $query->get();
    }

    public function findByEntityIdAndOfferIdAndType($entityId, $offer_id)
    {
        $query = $this->newQuery()
            ->where(Entity::ENTITY_ID, '=', $entityId)
            ->where(Entity::OFFER_ID,  '=', $offer_id)
            ->where(Entity::ENTITY_OFFER_TYPE, '=', 'reward');

        return $query->first();
    }
}

<?php

namespace RZP\Models\Settlement\Destination;

use RZP\Models\Base;
use RZP\Constants\Entity as EntityConstant;

class Repository extends Base\Repository
{
    protected $entity = EntityConstant::SETTLEMENT_DESTINATION;

    protected $adminFetchParamRules = [
        Entity::DESTINATION_ID   => 'sometimes|string|size:14',
        Entity::DESTINATION_TYPE => 'sometimes|string|max:255',
        Entity::SETTLEMENT_ID    => 'sometimes|string|size:14',
    ];

    /**
     * fetches the active destination entry for given settlement ID
     *
     * @param string $settlementId
     * @return Base\PublicCollection
     */
    public function fetchActiveDestination(string $settlementId)
    {
        return $this->newQuery()
                    ->where(Entity::SETTLEMENT_ID, $settlementId)
                    ->whereNull(Entity::DELETED_AT)
                    ->first();
    }
}

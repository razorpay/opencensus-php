<?php

namespace RZP\Models\Settlement\Destination;

use RZP\Models\Base;
use RZP\Constants\Entity as EntityConstant;

class Repository extends Base\Repository
{
    protected $entity = EntityConstant::SETTLEMENT_DESTINATION;

    public function fetchActiveDestination(string $settlementId): Base\PublicCollection
    {
        return $this->newQuery()
                    ->where(Entity::SETTLEMENT_ID, $settlementId)
                    ->whereNull(Entity::DELETED_AT)
                    ->get();
    }
}

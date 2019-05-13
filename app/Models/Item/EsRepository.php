<?php

namespace RZP\Models\Item;

use RZP\Models\Base;

class EsRepository extends Base\EsRepository
{
    protected $indexedFields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::ACTIVE,
        Entity::TYPE,
        Entity::NAME,
        Entity::DESCRIPTION,
        Entity::CREATED_AT,
    ];

    protected $queryFields = [
        Entity::NAME,
        Entity::DESCRIPTION,
    ];

    public function buildQueryForActive(array & $query, bool $value)
    {
        $this->addTermFilter($query, Entity::ACTIVE, $value);
    }

    public function buildQueryForType(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::TYPE, $value);
    }
}

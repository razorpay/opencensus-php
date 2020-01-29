<?php

namespace RZP\Models\Payment;

use RZP\Models\Base;
use RZP\Constants\Es;

class EsRepository extends Base\EsRepository
{
    protected $indexedFields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::NOTES,
        Entity::RECURRING,
        Entity::CREATED_AT,
    ];

    public function buildQueryForRecurring(array & $query, string $value)
    {
        $queryValue = (($value === '1') or ($value === true)) ? true : false;

        $this->addTermFilter($query, Entity::RECURRING, $queryValue);
    }
}

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
        Entity::AMOUNT_TRANSFERRED,
        Entity::VA_TRANSACTION_ID,
        Entity::STATUS,
    ];

    public function buildQueryForRecurring(array & $query, string $value)
    {
        $queryValue = (($value === '1') or ($value === true)) ? true : false;

        $this->addTermFilter($query, Entity::RECURRING, $queryValue);
    }

    public function buildQueryForTransferred(array & $query, $value)
    {
        if ($value !== '1')
        {
            return;
        }

        $filter = [Es::RANGE => [Entity::AMOUNT_TRANSFERRED => [Es::GT => 0]]];
        $this->addFilter($query, $filter);
    }
}

<?php

namespace RZP\Models\Invoice;

use RZP\Models\Base;
use RZP\Constants\Es;

class EsRepository extends Base\EsRepository
{
    protected $indexedFields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::RECEIPT,
        Entity::CUSTOMER_NAME,
        Entity::CUSTOMER_CONTACT,
        Entity::CUSTOMER_EMAIL,
        Entity::DESCRIPTION,
        Entity::STATUS,
        Entity::TYPE,
        Entity::TERMS,
        Entity::NOTES,
        Entity::USER_ID,
        Entity::CREATED_AT,
        Entity::ENTITY_TYPE,
    ];

    protected $queryFields = [
        Entity::RECEIPT,
        Entity::CUSTOMER_NAME,
        Entity::CUSTOMER_CONTACT,
        Entity::CUSTOMER_EMAIL,
        Entity::DESCRIPTION,
        Entity::TERMS,
        Entity::NOTES . '.*',
    ];

    protected $esFetchParams = [
        self::QUERY,
        Entity::NOTES,
        Entity::TERMS,
        Entity::RECEIPT,
        Entity::CUSTOMER_NAME,
        Entity::CUSTOMER_CONTACT,
        Entity::CUSTOMER_EMAIL,
    ];

    protected $commonFetchParams = [
        Entity::STATUS,
        Entity::TYPE,
        Entity::TYPES,
        Entity::MERCHANT_ID,
        Entity::USER_ID,
        Entity::ENTITY_TYPE,
    ];

    public function buildQueryForType(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::TYPE, $value);
    }

    public function buildQueryForTypes(array & $query, array $value)
    {
        $filter = [Es::TERMS => [Entity::TYPE => $value]];

        $this->addFilter($query, $filter);
    }

    public function buildQueryForStatus(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::STATUS, $value);
    }

    public function buildQueryForUserId(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::USER_ID, $value);
    }

    public function buildQueryForEntityType(array & $query, $value)
    {
        if ($value === null)
        {
            $this->addNullFilterForField($query, Entity::ENTITY_TYPE);
        }
        else
        {
            $this->addTermFilter($query, Entity::ENTITY_TYPE, $value);
        }
    }
}

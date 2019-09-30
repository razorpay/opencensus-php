<?php

namespace RZP\Models\Invoice;

use RZP\Models\Base;
use RZP\Constants\Es;
use RZP\Models\Currency\Currency;

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
        Entity::CURRENCY,
        Entity::TYPE,
        Entity::TERMS,
        Entity::NOTES,
        Entity::USER_ID,
        Entity::CREATED_AT,
        Entity::ENTITY_TYPE,
        Entity::SUBSCRIPTION_ID,
    ];

    protected $queryFields = [
        Entity::RECEIPT,
        Entity::CUSTOMER_NAME,
        Entity::CUSTOMER_CONTACT,
        Entity::CUSTOMER_EMAIL,
        Entity::DESCRIPTION,
        Entity::TERMS,
        Entity::NOTES . '.value',
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
        Entity::STATUSES,
        Entity::INTERNATIONAL,
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

    public function buildQueryForStatuses(array & $query, array $value)
    {
        $filter = [Es::TERMS => [Entity::STATUS => $value]];

        $this->addFilter($query, $filter);
    }

    /**
     * Adds a negative term filter and filter for INR when international attribtue is sent for es.
     *
     * @param array  $query
     * @param string $value
     */
    public function buildQueryForInternational(array & $query, string $value)
    {
        if ($value === '1')
        {
            $this->addNegativeTermFilter($query, Entity::CURRENCY, Currency::INR);
        }
        else
        {
            $this->addTermFilter($query, Entity::CURRENCY, Currency::INR);
        }
    }

    public function buildQueryForUserId(array & $query, string $value)
    {
        $this->addTermFilter($query, Entity::USER_ID, $value);
    }

    public function buildQueryForSubscriptions(array & $query, $value)
    {
        if ($value === '1')
        {
            $this->addNotNullFilterForField($query, Entity::SUBSCRIPTION_ID);
        }
        else
        {
            $this->addNullFilterForField($query, Entity::SUBSCRIPTION_ID);
        }
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

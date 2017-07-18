<?php

namespace RZP\Models\Invoice;

use RZP\Models\Base;

class EsRepository extends Base\EsRepository
{
    protected $fields = [
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
        Entity::CREATED_AT,
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
        Entity::MERCHANT_ID,
    ];

    public function buildQueryForType(array & $query, string $value)
    {
        $filter = [
            'term' => [
                'type' => [
                    'value' => $value,
                ],
            ],
        ];

        $this->addFilter($query, $filter);
    }

    public function buildQueryForStatus(array & $query, string $value)
    {
        $filter = [
            'term' => [
                'status' => [
                    'value' => $value,
                ],
            ],
        ];

        $this->addFilter($query, $filter);
    }
}

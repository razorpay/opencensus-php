<?php

namespace RZP\Models\Invoice;

use RZP\Models\Base\Es;

class EsRepository extends Es\Repository
{
    protected $fields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::RECEIPT,
        Entity::CUSTOMER_NAME,
        Entity::CUSTOMER_CONTACT,
        Entity::CUSTOMER_EMAIL,
        Entity::DESCRIPTION,
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
}

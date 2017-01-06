<?php

namespace RZP\Models\Invoice;

use RZP\Models\Base;
use RZP\Constants\Table;

class EsRepository extends Base\EsRepository
{
    protected static $table = Table::INVOICE;

    protected $fields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::DESCRIPTION,
        Entity::NOTES,
    ];

    protected $hideFields = [
        Entity::CUSTOMER_DETAILS,
        Entity::LINE_ITEMS,
        Entity::PUBLIC_ID,
        ENTITY::PAYMENT_ID,
    ];

    protected $searchFields = [
        Entity::DESCRIPTION,
        Entity::NOTES . '.*',
    ];

    protected $fieldsMappings = [
        Entity::NOTES => [
            'type' => 'object',
        ],
        Entity::MERCHANT_ID => [
            'type' => 'keyword',
        ],
    ];
}

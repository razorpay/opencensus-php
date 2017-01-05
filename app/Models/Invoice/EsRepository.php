<?php

namespace RZP\Models\Invoice;

use RZP\Models\Base;
use RZP\Constants\Table;

class EsRepository extends Base\EsRepository
{
    protected static $table = Table::INVOICE;

    //
    // These fields will be queried to db and will be indexed.
    //

    protected $fields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::RECEIPT,
        Entity::DESCRIPTION,
        Entity::NOTES,
    ];

    //
    // These fields will be searched on q
    //
    protected $searchFields = [
        Entity::RECEIPT,
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

    // protected $searchParamRules = [
    //     self::QUERY         => 'sometimes|string|min:1|max:100',

    //     Entity::MERCHANT_ID => 'sometimes|string|min:1|max:19'
    //     Entity::RECEIPT     => 'sometimes|string|min:1|max:40',
    //     Entity::NOTES       => 'sometimes|string|min:1|max:40',
    // ];

    public function fetchForIndex(array $ids = null, int $skip = 0, int $take = 100)
    {
        $invoices = parent::fetchForIndex($ids, $skip, $take);

        return $invoices->makeHidden(
            [
                Entity::CUSTOMER_DETAILS,
                Entity::LINE_ITEMS,
                Entity::PUBLIC_ID,
                Entity::PAYMENT_ID,
            ]);
    }
}

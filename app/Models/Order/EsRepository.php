<?php

namespace RZP\Models\Order;

use RZP\Models\Base;
use RZP\Constants\Table;

class EsRepository extends Base\EsRepository
{
    protected static $table = Table::ORDER;

    protected $fields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::NOTES,
    ];

    protected $queryFields = [
        Entity::NOTES . '*',
    ];

    public function setFieldMappings()
    {
        $this->fieldMappings = [
            Entity::NOTES => [
                'type' => 'object',
            ],
        ];
    }
}

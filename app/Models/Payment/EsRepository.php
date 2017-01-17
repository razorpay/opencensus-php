<?php

namespace RZP\Models\Payment;

use RZP\Models\Base;
use RZP\Constants\Table;

class EsRepository extends Base\EsRepository
{
    protected static $table = Table::PAYMENT;

    protected $fields = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::NOTES,
    ];

    protected $queryFields = [
        Entity::NOTES . '.*',
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

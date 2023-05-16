<?php

namespace RZP\Models\OrderOutbox;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ORDER_ID        => 'required|string|size:14',
        Entity::MERCHANT_ID     => 'required|string|size:14',
        Entity::EVENT_NAME      => 'required|string|max:255',
        Entity::PAYLOAD         => 'required|string|max:5000',
    ];

    protected static $editRules = [
        Entity::IS_DELETED      => 'boolean',
        Entity::RETRY_COUNT     => 'int',
        Entity::DELETED_AT      => 'int',
    ];

}

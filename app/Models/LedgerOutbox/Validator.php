<?php

namespace RZP\Models\LedgerOutbox;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::PAYLOAD_NAME            => 'required|string|max:255',
        Entity::PAYLOAD_SERIALIZED      => 'required|string|max:5000',
        Entity::ENTITY_TYPE             => 'required|string|max:50',
        Entity::ENTITY_ID               => 'required|string|max:14',
    ];

    protected static $editRules = [
        Entity::IS_DELETED      => 'boolean',
        Entity::RETRY_COUNT     => 'int',
        Entity::DELETED_AT      => 'int',
    ];

}

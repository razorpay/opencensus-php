<?php


namespace RZP\Models\Payment\Config;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME           => 'required|string|max:255',
        Entity::CONFIG         => 'required|array',
        Entity::IS_DEFAULT     => 'required|boolean',
        Entity::TYPE           => 'required|string|in:checkout',
    ];

    protected static $editRules = [
        Entity::TYPE           => 'required|string|in:checkout',
        Entity::IS_DEFAULT     => 'required_if:type,checkout|boolean',
        Entity::ID             => 'required_if:type,checkout|string',
    ];
}

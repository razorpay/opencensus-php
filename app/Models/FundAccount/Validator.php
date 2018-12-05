<?php

namespace RZP\Models\FundAccount;

use RZP\Base;

/**
 * Class Validator
 *
 * @package RZP\Models\FundAccount
 */
class Validator extends Base\Validator
{
    const BEFORE_CREATE = 'before_create';

    protected static $createRules = [
        Entity::CONTACT_ID   => 'required|public_id',
        Entity::ACCOUNT_TYPE => 'required|string|custom',
        Entity::DETAILS      => 'required|associative_array',
    ];

    protected static $beforeCreateRules = [
        Entity::CONTACT_ID   => 'required|public_id',
    ];

    protected static $editRules = [
        Entity::ACTIVE => 'filled|boolean',
    ];

    public function validateAccountType($attribute, $value)
    {
        Type::validateType($value);
    }
}

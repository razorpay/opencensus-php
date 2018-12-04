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
    protected static $createRules = [
        //
    ];

    protected static $editRules = [
        Entity::ACTIVE => 'filled|boolean',
    ];

    protected static $createFundAccountRules = [
        Entity::DETAILS      => 'required|associative_array',
        Entity::CONTACT_ID   => 'required|public_id',
        Entity::ACCOUNT_TYPE => 'required|string|custom',
    ];

    public function validateAccountType($attribute, $value)
    {
        Type::validateType($value);
    }
}

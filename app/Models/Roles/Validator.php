<?php

namespace RZP\Models\Roles;

use RZP\Base;

class Validator extends Base\Validator
{
    //
    // This is required for build. Currently, build does not
    // accept ruleName as a parameter. Hence, this list needs
    // to contain the master attributes. We run a different
    // validation for the actual operation.
    //
    protected static $createRules = [
        Entity::NAME                 => 'required|string|max:100',
        Entity::TYPE                 => 'required|in:standard,custom',
        Entity::DESCRIPTION          => 'sometimes|string|max:255',
        Entity::MERCHANT_ID          => 'sometimes|unsigned_id',
        Entity::CREATED_BY           => 'required|string',
        Entity::UPDATED_BY           => 'sometimes|string',
        Entity::ORG_ID               => 'required|string'
    ];

    protected static $viewRules = [
        Entity::NAME                 => 'sometimes|string|max:100',
        Entity::TYPE                 => 'sometimes|in:standard,custom',
        Entity::ID                   => 'sometimes|public_id|size:19',
        Entity::MERCHANT_ID          => 'sometimes|unsigned_id',
    ];

    protected static $editRules = [
        Entity::NAME                 => 'required|string|max:100',
        Entity::DESCRIPTION          => 'sometimes|string|max:255',
        Entity::UPDATED_BY           => 'required|string',
    ];

    public static function validateArrayEqual($array1, $array2) :bool
    {
        sort($array1);

        sort($array2);

        return $array1 === $array2;

    }
}

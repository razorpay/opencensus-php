<?php

namespace RZP\Models\RoleAccessPolicyMap;

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
        Entity::ROLE_ID                 => 'required|string',
        Entity::AUTHZ_ROLES             => 'required|array',
        Entity::ACCESS_POLICY_IDS       => 'required|array',
    ];

    protected static $editRules = [
        Entity::ROLE_ID                 => 'required|string',
        Entity::AUTHZ_ROLES             => 'required|array',
        Entity::ACCESS_POLICY_IDS       => 'required|array',
    ];
}

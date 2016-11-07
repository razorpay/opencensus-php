<?php

namespace RZP\Models\Admin\Admin;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        // One problem with this uniqueness if what if an admin
        // wants to belong to 2 org or is moved from 1 to another
        Entity::EMAIL               => 'required|email|max:250|unique:admins',
        Entity::NAME                => 'required|alpha_space|between:3,100',
        Entity::USERNAME            => 'alpha_dash|between:3,50',
        Entity::PASSWORD            => 'between:6,50',
        Entity::REMEMBER_TOKEN      => 'string|max:250',
        Entity::OAUTH_ACCESS_TOKEN  => 'string|max:250',
        Entity::OAUTH_PROVIDER_ID   => 'string|max:250',
        Entity::ORG_ID              => 'required'
    ];

    protected static $editRules = [
        Entity::EMAIL               => 'required|email|max:250|unique:admins',
        Entity::NAME                => 'required|alpha_space|between:3,100',
        Entity::USERNAME            => 'alpha_dash|between:3,50',
        Entity::PASSWORD            => 'between:6,50',
        Entity::REMEMBER_TOKEN      => 'string|max:250',
        Entity::OAUTH_ACCESS_TOKEN  => 'string|max:250',
        Entity::OAUTH_PROVIDER_ID   => 'string|max:250',
        Entity::ORG_ID              => 'required'
    ];
}

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
        Entity::USERNAME            => 'sometimes|alpha_dash|between:3,50',
        Entity::PASSWORD            => 'sometimes|string|between:6,50',
        Entity::REMEMBER_TOKEN      => 'sometimes|string|max:250',
        Entity::OAUTH_ACCESS_TOKEN  => 'sometimes|string|max:250',
        Entity::OAUTH_PROVIDER_ID   => 'sometimes|string|max:250',
 //       Entity::ORG_ID              => 'required|string',
        Entity::BRANCH_CODE         => 'sometimes|string',
        Entity::DEPARTMENT_CODE     => 'sometimes|string',
        Entity::SUPERVISOR_CODE     => 'sometimes|string',
        Entity::LOCATION_CODE       => 'sometimes|string',
        Entity::EMPLOYEE_CODE       => 'required|string',
        'roles'                     => 'sometimes|array',
        'merchants'                 => 'sometimes|array',
    ];

    protected static $editRules = [
        Entity::NAME                => 'sometimes|alpha_space|between:3,100',
        Entity::USERNAME            => 'sometimes|alpha_dash|between:3,50',
        Entity::PASSWORD            => 'sometimes|string|between:6,50',
        Entity::REMEMBER_TOKEN      => 'sometimes|string|max:250',
        Entity::OAUTH_ACCESS_TOKEN  => 'sometimes|string|max:250',
        Entity::OAUTH_PROVIDER_ID   => 'sometimes|string|max:250',
        Entity::ORG_ID              => 'sometimes|string',
        Entity::BRANCH_CODE         => 'sometimes|string',
        Entity::DEPARTMENT_CODE     => 'sometimes|string',
        Entity::SUPERVISOR_CODE     => 'sometimes|string',
        Entity::LOCATION_CODE       => 'sometimes|string',
        Entity::EMPLOYEE_CODE       => 'sometimes|string',
    ];

    protected static $loginRules = [
        Entity::USERNAME            => 'required|email|max:250',
        Entity::PASSWORD            => 'required|between:6,50'
    ];

    public function validateCredentials(array $input)
    {
        $this->validateInput('login', $input);
    }
}

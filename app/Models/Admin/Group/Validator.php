<?php

namespace RZP\Models\Admin\Group;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME        => 'required|string|max:100',
        Entity::DESCRIPTION => 'required|string|max:250',
        'sub_groups'        => 'sometimes|array',
        'admins'            => 'sometimes|array',
        'merchants'         => 'sometimes|array',
        'roles'             => 'sometimes|array',
        'parents'           => 'sometimes|array',
    ];

    protected static $editRules = [
        Entity::NAME        => 'sometimes|string|max:100',
        Entity::DESCRIPTION => 'sometimes|string|max:250',
        'sub_groups'        => 'sometimes|array',
        'admins'            => 'sometimes|array',
        'merchants'         => 'sometimes|array',
        'roles'             => 'sometimes|array',
        'parents'           => 'sometimes|array',
    ];

    public function validateCreateInput(string $orgId, array $input)
    {
        $this->validateName($orgId, $input[Entity::NAME]);
    }

    public function validateName(string $orgId, string $name)
    {
        $grpExists = (new Repository)->hasGroupByName($orgId, $name);

        if ($grpExists == true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The group with the name already exists');
        }
    }
}

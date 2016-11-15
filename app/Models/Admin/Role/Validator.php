<?php

namespace RZP\Models\Admin\Role;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME            => 'required|string|max:250',
        Entity::DESCRIPTION     => 'sometimes|string|max:255',
        'permissions'           => 'sometimes|array',
    ];

    protected static $editRules = [
        Entity::NAME            => 'required|string|max:250',
        Entity::DESCRIPTION     => 'sometimes|string|max:255',
        'permissions'           => 'sometimes|array',
    ];

    public function validateCreateInput(string $orgId, array $input)
    {
        $this->validateName($orgId, $input[Entity::NAME]);
    }

    public function validateName(string $orgId, string $name)
    {
        $roleExists = (new Repository)->hasRoleByName($orgId, $name);

        if($roleExists === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The role with the name already exists');
        }
    }
}

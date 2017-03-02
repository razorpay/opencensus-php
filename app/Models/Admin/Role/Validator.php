<?php

namespace RZP\Models\Admin\Role;

use RZP\Models\Admin\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME            => 'required|string|max:255',
        Entity::DESCRIPTION     => 'sometimes|string|max:255',
        Entity::PERMISSIONS     => 'sometimes',
    ];

    protected static $editRules = [
        Entity::NAME            => 'required|string|max:255',
        Entity::DESCRIPTION     => 'sometimes|string|max:255',
        Entity::PERMISSIONS     => 'sometimes',
    ];

    public $isOrgSpecificValidationSupported = false;

    public function validateRoleIsNotSuperAdmin()
    {
        $role = $this->entity;

        if ($role->isSuperAdminRole() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUPERADMIN_ROLE_NOT_EDITABLE);
        }
    }
}

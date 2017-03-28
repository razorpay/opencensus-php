<?php

namespace RZP\Models\Admin\Role;

use App;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME            => 'required|string|max:255',
        Entity::DESCRIPTION     => 'sometimes|string|max:255',
        Entity::PERMISSIONS     => 'sometimes|array',
    ];

    protected static $editRules = [
        Entity::NAME            => 'sometimes|string|max:255',
        Entity::DESCRIPTION     => 'sometimes|string|max:255',
        Entity::PERMISSIONS     => 'sometimes|array',
    ];

    public function validateRoleIsNotSuperAdmin($admin = null)
    {
        $role = $this->entity;

        if ((isset($admin) === true) and
            ($admin->org->isCrossOrgAccessEnabled() === true) and
            ($admin->isSuperAdmin() === true))
        {
            return;
        }


        if ($role->isSuperAdminRole() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUPERADMIN_ROLE_NOT_EDITABLE);
        }
    }
}

<?php

namespace RZP\Models\Admin\Role;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Base;
use RZP\Constants\Product;
use RZP\Models\Admin\Permission;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME            => 'required|string|max:255',
        Entity::DESCRIPTION     => 'required|string|max:255',
        Entity::PERMISSIONS     => 'sometimes|array|custom',
        Entity::PRODUCT         => 'sometimes|string|custom',
    ];

    protected static $editRules = [
        Entity::NAME            => 'sometimes|string|max:255',
        Entity::DESCRIPTION     => 'sometimes|string|max:255',
        Entity::PERMISSIONS     => 'sometimes|array|custom',
        Entity::PRODUCT         => 'sometimes|string|custom',
    ];

    public $isOrgSpecificValidationSupported = false;

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

    public function validateProduct(string $attribute, string $product)
    {
        Product::validate($product);
    }

    public function validatePermissions(string $attr, array $permissions)
    {
        $role = $this->entity;

        $org = $role->org;

        Permission\Entity::verifyIdAndSilentlyStripSignMultiple($permissions);

        $orgPermissions = $org->permissions()->get(['id']);

        $orgPermissionIds = [];

        foreach ($orgPermissions as $permission)
        {
            $orgPermissionIds[] = $permission['id'];
        }

        $diffPerms = array_diff($permissions, $orgPermissionIds);

        if (empty($diffPerms) === false)
        {
            $data = [
                'role_id'   => $role->getId(),
                'org_id'    => $org->getId(),
                'diffPerms' => $diffPerms,
                'orgPerms'  => $orgPermissionIds,
            ];

            throw new Exception\BadRequestValidationFailureException(
                'Few permissions are not allowed for the organization', null,
                $data);
        }
    }
}

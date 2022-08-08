<?php

namespace RZP\Http;

use RZP\Models\User\BankingRole;
use RZP\Models\Admin\Permission\Name as Permission;

class LmsUserRolePermissionsMap
{
    private static $lmsRolePermissions;

    private static function init()
    {
        $lmsRolePermissions = [
            BankingRole::OWNER => [
                Permission::RBL_BANK_MID_OFFICE_VIEW_LEAD,
                Permission::RBL_BANK_MID_OFFICE_EDIT_LEAD,
                Permission::RBL_BANK_MID_OFFICE_MANAGE_LEAD
            ],

            BankingRole::BANK_MID_OFFICE_POC => [
                Permission::RBL_BANK_MID_OFFICE_VIEW_LEAD,
                Permission::RBL_BANK_MID_OFFICE_EDIT_LEAD,
            ],

            BankingRole::BANK_MID_OFFICE_MANAGER => [
                Permission::RBL_BANK_MID_OFFICE_VIEW_LEAD,
                Permission::RBL_BANK_MID_OFFICE_EDIT_LEAD,
                Permission::RBL_BANK_MID_OFFICE_MANAGE_LEAD
            ]
        ];

        self::$lmsRolePermissions = $lmsRolePermissions;
    }

    private static function getLmsRolePermissionMap()
    {
        if (empty(self::$lmsRolePermissions) === true)
        {
            self::init();
        }

        return self::$lmsRolePermissions;
    }

    public static function isValidRolePermission(string $role, string $permission) : bool
    {
        $rolePermissions = self::getLmsRolePermissions($role);

        if (in_array($permission, $rolePermissions, true))
        {
            return true;
        }

        return false;
    }

    public static function getLmsRolePermissions(string $role)
    {
        return self::getLmsRolePermissionMap()[$role] ?? [];
    }

    public static function isInvalidLmsRolePermission(string $role, string $permission) : bool
    {
        if (self::isValidRolePermission($role, $permission))
        {
            return false;
        }

        return true;
    }
}

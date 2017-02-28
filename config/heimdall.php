<?php

use RZP\Models\Admin\Permission\Name as Permission;

return [
    'permissions' => [
        Permission::VIEW_ALL_MERCHANTS,
        Permission::VIEW_MERCHANT,
        Permission::CREATE_MERCHANT_LOCK,
        Permission::CREATE_MERCHANT_UNLOCK,
        Permission::EDIT_MERCHANT,
        Permission::EDIT_ACTIVATE_MERCHANT,
        Permission::EDIT_MERCHANT_ENABLE_LIVE,
        Permission::EDIT_MERCHANT_DISABLE_LIVE,
        Permission::EDIT_MERCHANT_ARCHIVE,
        Permission::EDIT_MERCHANT_UNARCHIVE,
        Permission::VIEW_ACTIVATION_FORM,
        Permission::EDIT_MERCHANT_CONFIRM,
        Permission::EDIT_MERCHANT_LOCK_ACTIVATION,
        Permission::EDIT_MERCHANT_UNLOCK_ACTIVATION,
        Permission::EDIT_MERCHANT_HOLD_FUNDS,
        Permission::EDIT_MERCHANT_RELEASE_FUNDS,
        Permission::VIEW_MERCHANT_BALANCE_TEST,
        Permission::VIEW_MERCHANT_BALANCE_LIVE,
        Permission::VIEW_ALL_ROLE,
        Permission::VIEW_ROLE,
        Permission::CREATE_ROLE,
        Permission::EDIT_ROLE,
        Permission::DELETE_ROLE,
        Permission::VIEW_ALL_GROUP,
        Permission::VIEW_GROUP,
        Permission::CREATE_GROUP,
        Permission::EDIT_GROUP,
        Permission::DELETE_GROUP,
        Permission::GROUP_GET_ALLOWED_GROUPS,
        Permission::VIEW_ALL_ADMIN,
        Permission::VIEW_ADMIN,
        Permission::CREATE_ADMIN,
        Permission::EDIT_ADMIN,
        Permission::DELETE_ADMIN,
        Permission::VIEW_ALL_PERMISSION,
        Permission::VIEW_AUDITLOG
    ],

    'default_role_name' => 'SuperAdmin',
    'default_role_desc' => 'superadmin with all possible permissions',
];

<?php

return [
    'org_create' 						=> 'orgs',
    'org_get_multiple' 					=> 'orgs',
    'org_get' 							=> 'orgs/{id}',
    'org_edit'                          => 'orgs/{id}',
    'org_delete'                        => 'orgs/{id}',

    // Roles
    'role_get_multiple'                 => 'orgs/{id}/roles',
    'role_get'                          => 'orgs/{id}/roles/{roleId}',
    'role_create'                       => 'orgs/{id}/roles',
    'role_delete'                       => 'orgs/{id}/roles/{roleId}',
    'role_edit'                         => 'orgs/{id}/roles/{roleId}',

    // Groups
    'group_get_multiple'                => 'orgs/{id}/groups',
    'group_create'                      => 'orgs/{id}/groups',
    'group_get'                         => 'orgs/{id}/groups/{groupId}',
    'group_admins_create'               => 'orgs/{id}/groups/{groupId}/admins',
    'group_delete'                      => 'orgs/{id}/groups/{groupId}',
    'edit_group'                        => 'orgs/{id}/groups/{groupId}',

    // Admins
    'admin_get'                         => 'orgs/{id}/admins/{adminId}',
    'admin_get_multiple'                => 'orgs/{id}/admins',
    'admin_edit'                        => 'orgs/{id}/admins/{adminId}',
    'admin_delete'                      => 'orgs/{id}/admins/{adminId}',
    'admin_create'                      => 'orgs/{id}/admins',
    'admin_get_app_auth'                => 'orgs/{id}/current_admin',

    // Permissions
    'permission_get_multiple'           => 'permissions',
    'permission_create'                 => 'permissions',

    'merchant_attach_admin'             => 'merchants/{id}/admins',
];

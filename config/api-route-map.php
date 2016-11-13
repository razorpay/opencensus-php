<?php

return [
    'org_create' 						=> 'orgs',
    'org_get_multiple' 					=> 'orgs',
    'org_get' 							=> 'orgs/{id}',
    'org_edit' 							=> 'orgs/{id}',

    // Roles
    'role_get_multiple'                 => 'orgs/{id}/roles',
    'role_create'                       => 'orgs/{id}/roles',

    // Groups
    'group_get_multiple'                => 'orgs/{id}/groups',
    'group_create'                      => 'orgs/{id}/groups',
    'group_get'                         => 'orgs/{id}/groups/{groupId}',
    'group_admins_create'               => 'orgs/{id}/groups/{groupId}/admins',

    // Admins
    'admin_get_multiple'                => 'orgs/{id}/admins',
    'admin_edit'                        => 'orgs/{id}/admins/{adminId}',
    'admin_delete'                      => 'orgs/{id}/admins/{adminId}',
    'admin_create'                      => 'orgs/{id}/admins',
    'admin_get_app_auth'                => 'orgs/{id}/current_admin',

    // Permissions
    'permission_get_multiple'           => 'permissions',
    'permission_create'                 => 'permissions'
];

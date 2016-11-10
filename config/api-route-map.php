<?php

return [

    'add_permission'            		=> 'permissions',

    'org_create' 						=> 'orgs',
    'org_get_multiple' 					=> 'orgs',
    'org_get' 							=> 'orgs/{id}',
    'org_edit' 							=> 'orgs/{id}',

    // Roles
    'role_get_multiple'                 => 'orgs/{id}/roles',
    'role_create'                       => 'orgs/{id}/roles',

    // Groups
    'group_get_multiple'                => 'orgs/{id}/groups',
    'group_create'                      => 'orgs/{id}/groups'
];

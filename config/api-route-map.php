<?php

return [
    // auth
    'admin' => [
        'org_create'                        => 'orgs',
        'org_get_multiple'                  => 'orgs',
        'org_get'                           => 'orgs/{id}',
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
        'group_get_allowed_groups'          => 'orgs/{id}/groups/{groupId}/allowed_groups',

        // Admins
        'admin_get'                         => 'orgs/{id}/admins/{adminId}',
        'admin_get_multiple'                => 'orgs/{id}/admins',
        'admin_edit'                        => 'orgs/{id}/admins/{adminId}',
        'admin_delete'                      => 'orgs/{id}/admins/{adminId}',
        'admin_create'                      => 'orgs/{id}/admins',
        'admin_get_app_auth'                => 'orgs/{id}/current_admin',

        // AuditLog
        'auditlog_search'                    => 'orgs/{id}/auditlog/search',

        // Permissions
        'permission_get_multiple'           => 'permissions',
        'permission_create'                 => 'permissions',

        'merchant_attach_admin'             => 'merchants/{id}/admins',
    ],

    // auth
    'proxy' => [
        // Payments
        'payment_fetch_multiple'            => [
            'url'       => 'payments',
            'routeName' => 'get_payments'
        ],
        'payment_fetch_by_id'               => [
            'url'       => 'payments/{id}',
            'routeName' => 'payment_get_single'
        ],

        // Payment Details
        'payment_fetch_card_details'        => [
            'url'       => 'payments/{id}/card',
            'routeName' => 'card_get_single'
        ],
        'payment_fetch_refunds'             => [
            'url'       => 'payments/{id}/refunds',
            'routeName' => 'payment_get_refunds'
        ],
        'payment_capture'                   => [
            'url'       => 'payments/{id}/capture',
            'routeName' => 'post_capture'
        ],
        'payment_refund'                    => [
            'url'       => 'payments/{id}/refund',
            'routeName' => 'post_refund'
        ],
    ],
];

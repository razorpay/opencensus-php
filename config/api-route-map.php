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
        'permission_get_by_type'            => 'permissions/get/{type}',
        'permission_get_multiple'           => 'orgs/{id}/permissions',
        'permission_create'                 => 'permissions',
        'permission_get'                    => 'permissions/{id}',
        'permission_edit'                   => 'permissions/{id}',
        'permission_delete'                 => 'permissions/{id}',

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

        // Refunds
        'refund_fetch_multiple'             => [
            'url'       => 'refunds',
            'routeName' => 'refunds_fetch_multiple'
        ],
        'refund_fetch_by_id'                => [
            'url'       => 'refunds/{id}',
            'routeName' => 'refunds_fetch_single'
        ],

        // Orders
        'order_fetch'                       => [
            'url'       => 'orders',
            'routeName' => 'get_orders'
        ],
        'order_fetch_by_id'                 => [
            'url'       => 'orders/{id}',
            'routeName' => 'get_order'
        ],
        'order_payments'                    => [
            'url'       => 'orders/{id}/payments',
            'routeName' => 'get_order_payments'
        ],

        // Settlements
        'setl_fetch_multiple'               => [
            'url'       => 'settlements',
            'routeName' => 'settlements_fetch_all'
        ],
        'setl_fetch_by_id'                  => [
            'url'       => 'settlements/{id}',
            'routeName' => 'settlements_fetch_one'
        ],
        'setl_get_details'                  => [
            'url'       => 'settlements/{id}/details',
            'routeName' => 'settlements_get_detail'
        ],

        // Credits
        'credits_fetch_multiple'            => 'credits',

        // Balance
        'balance_fetch'                     => [
            'url'       => 'balance',
            'routeName' => 'balance_get'
        ],

        // Webhooks
        'webhook_fetch_multiple'            => [
            'url'       => 'webhooks',
            'routeName' => 'get_webhooks'
        ],
        'webhook_create'                    => [
            'url'       => 'webhooks',
            'routeName' => 'post_webhooks'
        ],
        'webhook_edit'                      => [
            'url'       => 'webhooks/{id}',
            'routeName' => 'edit_webhooks'
        ],

        // Config
        'merchant_fetch_config'             => [
            'url'       => 'account/config',
            'routeName' => 'get_config'
        ],
        'merchant_edit_config'              => [
            'url'       => 'account/config',
            'routeName' => 'put_config'
        ],
        'merchant_edit_config_logo'         => [
            'url'       => 'account/config/logo',
            'routeName' => 'post_config_logo'
        ],

        // Features
        'merchant_get_features'             => 'merchants/{id}/features',
        'merchant_update_features'          => 'merchants/{id}/features',

        // Invoices
        'invoice_fetch_multiple'            => [
            'url'       => 'invoices',
            'routeName' => 'invoice_fetch_all'
        ],
        'invoice_fetch'                     => [
            'url'       => 'invoices/{id}',
            'routeName' => 'invoice_fetch_single'
        ],
        'invoice_create'                    => [
            'url'       => 'invoices',
            'routeName' => 'invoice_create'
        ],
        'invoice_update'                    => [
            'url'       => 'invoices/{id}',
            'routeName' => 'invoice_edit'
        ],
        'invoice_delete'                    => [
            'url'       => 'invoices/{id}',
            'routeName' => 'invoice_delete'
        ],
        'invoice_issue'                     => [
            'url'       => 'invoices/{id}/issue',
            'routeName' => 'invoice_issue'
        ],
        'invoice_cancel'                    => [
            'url'       => 'invoices/{id}/cancel',
            'routeName' => 'invoice_cancel'
        ],

        // Customers
        'customer_fetch_multiple'           => [
            'url'       => 'customers',
            'routeName' => 'customer_fetch_all'
        ],
        'customer_create'                   => [
            'url'       => 'customers',
            'routeName' => 'customer_create'
        ],
        'customer_update'                   => [
            'url'       => 'customers/{id}',
            'routeName' => 'customer_edit'
        ],
        'customer_delete'                   => [
            'url'       => 'customers/{id}',
            'routeName' => 'customer_delete'
        ],

        // Items
        'item_fetch_multiple'               => [
            'url'       => 'items',
            'routeName' => 'item_fetch_all'
        ],
        'item_create'                       => [
            'url'       => 'items',
            'routeName' => 'item_create'
        ],
        'item_update'                       => [
            'url'       => 'items/{id}',
            'routeName' => 'item_edit'
        ],
        'item_delete'                       => [
            'url'       => 'items/{id}',
            'routeName' => 'item_delete'
        ],
    ],

    // auth
    'internal' => [
        // Keys
        'merchant_fetch_keys'               => [
            'url'       => 'merchants/{id}/keys',
            'routeName' => 'get_keys'
        ],
        'merchant_create_key'               => [
            'url'       => 'merchants/{id}/keys',
            'routeName' => 'keys_setup'
        ],
        'merchant_replace_key'              => [
            'url'       => 'merchants/{merchantId}/keys/{keyId}',
            'routeName' => 'post_keys'
        ],

        // Admin Routes
        // Pricing
        'pricing_get_merchant_plans'        => 'pricing/merchants',
        'pricing_get_plan'                  => 'pricing/{id}',
        'pricing_add_plan_rule'             => 'pricing/{id}/rule',
        'pricing_create_plan'               => 'pricing',
        'pricing_delete_plan_rule'          => 'pricing/{planId}/rule/{ruleId}',
        'pricing_supported_networks'        => 'pricing/networks',

        // EMI
        'emi_plan_add'                      => 'emi',

        // IIN
        'iin_add'                           => 'iins',
    ],
];

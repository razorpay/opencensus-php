<?php

// We use {orgId} when frontend is not supposed to pass it
// and resolve (on the backend) automagically.

return [
    // auth
    'admin' => [
        'org_create'                        => 'orgs',
        'org_get_multiple'                  => 'orgs',
        // this should not be {orgId}
        'org_get'                           => 'orgs/{id}',
        'org_edit'                          => 'orgs/{id}',
        'org_delete'                        => 'orgs/{id}',

        // Roles
        'role_get_multiple'                 => 'orgs/{orgId}/roles',
        'role_get'                          => 'orgs/{orgId}/roles/{roleId}',
        'role_create'                       => 'orgs/{orgId}/roles',
        'role_delete'                       => 'orgs/{orgId}/roles/{roleId}',
        'role_edit'                         => 'orgs/{orgId}/roles/{roleId}',

        // Groups
        'group_get_multiple'                => 'orgs/{orgId}/groups',
        'group_create'                      => 'orgs/{orgId}/groups',
        'group_get'                         => 'orgs/{orgId}/groups/{groupId}',
        'group_admins_create'               => 'orgs/{orgId}/groups/{groupId}/admins',
        'group_delete'                      => 'orgs/{orgId}/groups/{groupId}',
        'edit_group'                        => 'orgs/{orgId}/groups/{groupId}',
        'group_get_allowed_groups'          => 'orgs/{orgId}/groups/{groupId}/allowed_groups',

        // Admins
        'admin_get'                         => 'orgs/{orgId}/admins/{adminId}',
        'admin_get_multiple'                => 'orgs/{orgId}/admins',
        'admin_edit'                        => 'orgs/{orgId}/admins/{adminId}',
        'admin_delete'                      => 'orgs/{orgId}/admins/{adminId}',
        'admin_create'                      => 'orgs/{orgId}/admins',
        'admin_get_app_auth'                => 'orgs/{orgId}/current_admin',

        // AuditLog
        'auditlog_search'                    => 'orgs/{orgId}/auditlog/search',

        // Permissions
        'permission_get_by_type'            => 'permissions/get/{type}',
        'permission_get_roles'              => 'permissions/{id}/roles',
        'permission_get_multiple'           => 'orgs/{orgId}/permissions',
        'permission_create'                 => 'permissions',
        'permission_get'                    => 'permissions/{id}',
        'permission_edit'                   => 'permissions/{id}',
        'permission_delete'                 => 'permissions/{id}',

        'merchant_attach_admin'             => 'merchants/{id}/admins',

        // Org Field Maps
        'org_fieldmap_get_multiple'         => 'orgs/{orgId}/field-map',
        'org_fieldmap_get'                  => 'orgs/{orgId}/field-map/{id}',
        'org_fieldmap_create'               => 'orgs/{orgId}/field-map',
        'org_fieldmap_edit'                 => 'orgs/{orgId}/field-map/{id}',
        'org_fieldmap_delete'               => 'orgs/{orgId}/field-map/{id}',
        'org_fieldmap_get_by_entity'        => 'orgs/{orgId}/field-map/entity/{entity}',

        'admin_lead_create'                 => 'orgs/{orgId}/admin-lead',
        'admin_lead_get_multiple'           => 'orgs/{orgId}/admin-lead',
        'admin_lead_put'                    => 'orgs/{orgId}/admin-lead/{id}',

        // Workflows

        'workflow_get_multiple'             => 'orgs/{orgId}/workflows',
        'workflow_create'                   => 'workflows',
        'workflow_get'                      => 'workflows/{id}',
        'workflow_update'                   => 'workflows/{id}',
        'workflow_delete'                   => 'workflows/{id}',
        'action_diff_get'                   => 'w-actions/{id}/diff',
        'action_comment_create'             => 'w-actions/{id}/comments',
        'action_comment_fetch'              => 'w-actions/{id}/comments',
        'workflow_action_details'           => 'w-actions/{id}/details',
        'workflow_action_update'            => 'w-actions/{id}',
        'workflow_get_actions_by_maker'     => 'w-manager/get-actions-by-maker',
        'workflow_get_actions_for_checker'  => 'w-manager/get-actions-for-checker',
        'action_checker_create'             => 'w-actions/{id}/checkers',
        'action_request_execute'            => 'w-actions/{id}/execute',
        'workflow_action_close'             => 'w-actions/close/{id}',

        // Admin Actions
        // Create Schedule
        'schedule_create'                   => 'schedules',
        'schedule_assign'                   => 'merchants/{id}/schedules',
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

        // Refunds
        'refund_fetch_multiple'             => [
            'url'       => 'refunds',
            'routeName' => 'get_refunds'
        ],
        'refund_fetch'                      => [
            'url'       => 'refunds',
            'routeName' => 'get_refunds'
        ],
        'refund_fetch_by_id'                => [
            'url'       => 'refunds/{id}',
            'routeName' => 'get_refund'
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
    'admin_proxy' => [
        // Credits
        'credits_fetch_multiple'            => 'credits',

        // Balance
        'balance_fetch'                     => [
            'url'       => 'balance',
            'routeName' => 'balance_get'
        ],

        // Add Adjustment
        'adj_add'                           => 'adjustments',
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

        // Admin Actions
        // Add EMI Plan
        'emi_plan_add'                      => 'emi',
        // Add IIN Rule
        'iin_add'                           => 'iins',
        // Verify Payment
        'payment_verify'                    => 'payments/{id}/verify',
        // Authorize Failed Payment
        'payment_authorize_failed'          => 'payments/{id}/authorize_failed',
        // Generate Refunds Excel (Netbanking)
        'refund_netbanking_generate_excel'  => 'refunds/netbanking/excel',
        // Trigger Dummy Error
        'dummy_critical_error'              => 'trigger/error',

        'admin_lead_verify'                 => 'admin-lead/verify/{token}',
        'merchant_admin_lead_put'           => 'orgs/{orgId}/admin-lead-merchant/{id}',

        // Get Org details by hostname (for heimdall specifics)
        'org_get_by_hostname'               => 'orgs/hostname/{hostname}',
    ],

    // auth
    'admin_internal' => [
        // Credits
        'credits_create'                    => 'merchants/{id}/credits_log',
        'credits_delete'                    => 'merchants/{mid}/credits/{id}',

        'merchant_put_payment_methods'      => 'merchants/{mid}/methods',

        'feature_get_multiple'              => 'features/{entityId}',

        'merchant_activation_update'        => 'merchant/activation/{id}/update',

        'merchant_assign_pricing'           => 'merchants/{id}/pricing',

        // Banks
        'merchant_get_banks'                => 'merchants/{id}/banks',
        'merchant_set_banks'                => 'merchants/{id}/banks',

        'merchant_fetch_bank_account'       => 'merchants/{id}/bank_account',
        'merchant_add_bank_account'         => 'merchants/{id}/bank_account',

        'merchant_edit'                     => 'merchants/{id}',

        // Entities
        'admin_fetch_entity_by_id'          => 'admin/{type}/{id}',

        'merchant_action'                   => 'merchants/{id}/action',
        'merchant_live_enable'              => 'merchants/{id}/live/enable',
        'merchant_live_disable'             => 'merchants/{id}/live/disable',
    ],
];

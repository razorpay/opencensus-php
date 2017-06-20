<?php

use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Models\Admin\Permission\Category as PermissionCategory;

/*
    If you add any new feature on API that needs to be exposed
    on dashboard and hidden behind permission then add an
    appropriate permission name for it in Permission/Name.php
    and **here** as well.

    Also if you think that the permission you're adding/removing
    must be added/removed to other orgs as well then add that
    to the `assignablePermissions` list (below) as well.

    This config is primarily used for seeding when setting up
    API locally (so that dashboard works appropriately).
*/

return [

    'default_role_name' => 'SuperAdmin',

    'default_role_desc' => 'superadmin with all possible permissions',

    'permissions' => [
        PermissionCategory::MERCHANT => [
            Permission::VIEW_ALL_MERCHANTS    => [
                'description' => 'View all merchants in merchant lists',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT         => [
                'description' => 'View a particular merchant details',
                'assignable' => true,
                'workflow' => false
            ]
        ],

        PermissionCategory::MERCHANT_DETAIL => [
            Permission::VIEW_MERCHANT_BALANCE => [
                'description' => 'View merchant balance in merchant details',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_FEATURES => [
                'description' => '',
                'assignable' => false,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_BANKS => [
                'description' => '',
                'assignable' => false,
                'workflow' => false
            ],
            Permission::VIEW_NETWORKS => [
                'description' => '',
                'assignable' => false,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_BANK_ACCOUNTS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_LOGIN => [
                'description' => '',
                'assignable' => false,
                'workflow' => false
            ],
            Permission::VIEW_ACTIVITY => [
                'description' => '',
                'assignable' => false,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_PRICING_RULES => [
                'description' => '',
                'assignable' => false,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_HDFC_EXCEL => [
                'description' => '',
                'assignable' => false,
                'workflow' => false
            ],
            Permission::VIEW_BENEFICIARY_FILE => [
                'description' => '',
                'assignable' => false,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_SCREENSHOT => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_ALL_MERCHANT_AGGREGATIONS => [
                'description' => '',
                'assignable' => false,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_AGGREGATIONS => [
                'description' => '',
                'assignable' => false,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_TAGS => [
                'description' => '',
                'assignable' => false,
                'workflow' => false
            ],
            Permission::SET_PRICING_RULES => [
                'description' => '',
                'assignable' => true,
                'workflow' => true
            ],
            Permission::DELETE_EMI_PLAN => [
                'description' => '',
                'assignable' => false,
                'workflow' => false
            ],
            Permission::CREATE_EMI_PLAN => [
                'description' => '',
                'assignable' => false,
                'workflow' => false
            ],
            Permission::CREATE_MERCHANT_LOCK => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::CREATE_MERCHANT_UNLOCK => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_TAGS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_FEATURES => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_FEATURES => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_BANK_DETAIL => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_IIN_RULE => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_ACTIVATE_MERCHANT => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_ENABLE_LIVE => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_DISABLE_LIVE => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_ARCHIVE => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_UNARCHIVE => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_SUSPEND => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_UNSUSPEND => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_METHODS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_ENABLE_INTERNATIONAL => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_DISABLE_INTERNATIONAL => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_TERMINAL => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_PRICING => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_COMMENTS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_COMPANY_INFO => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_CREDITS_LOG => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::ADD_MERCHANT_CREDITS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::DELETE_MERCHANT_CREDITS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_SCREENSHOT => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_PAYMENT_VERIFY => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_VERIFY_PAYMENTS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_AUTHORIZED_FAILED_PAYMENT => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_REFUND_PAYMENTS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_AUTHORIZED_REFUND_PAYMENT => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_PAYMENT_REFUND => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_PAYMENT_CAPTURE => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_CONFIRM => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::CREATE_BENEFICIARY_FILE => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::CREATE_NETBANKING_REFUND => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::CREATE_SETTLEMENT_INITIATE => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::DELETE_TERMINAL => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_TERMINAL => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::CREATE_SETTLEMENTS_RECONCILE => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::CREATE_RECONCILIATE => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_ACTIVATION_FORM => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_LOCK_ACTIVATION => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_UNLOCK_ACTIVATION => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_HOLD_FUNDS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_RELEASE_FUNDS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_ENABLE_RECEIPT => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_DISABLE_RECEIPT => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_BULK_MERCHANT_HOLD_FUNDS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::ASSIGN_MERCHANT_TERMINAL => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::ASSIGN_MERCHANT_BANKS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::ADD_MERCHANT_ADJUSTMENT => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_EMAIL => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::MERCHANT_AUTOFILL_FORM => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_MARK_REFERRED => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_AS_ENTITY => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_REFERRER => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_BALANCE_TEST => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_BALANCE_LIVE => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::ADD_RECONCILIATION_FILE => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::ADD_SETTLEMENT_RECONCILIATION => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::SEND_NEWSLETTER => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::TRIGGER_DUMMY_ERROR => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::MAKE_API_CALL => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::SCHEDULE_CREATE => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::SCHEDULE_FETCH => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::SCHEDULE_FETCH_MULTIPLE => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::SCHEDULE_DELETE => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::SCHEDULE_UPDATE => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::SCHEDULE_ASSIGN => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::SCHEDULE_MIGRATION => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_ACTIONS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_STATS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::DELETE_MERCHANT_FEATURES => [
                'description' => 'Delete a merchant feature',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_REPORT   => [
                'description' => 'View Merchant Reports',
                'assignable' => true,
                'workflow' => false
            ],
        ],

        PermissionCategory::PRICING => [
            Permission::VIEW_PRICING_LIST => [
                'description' => 'view pricinglist',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::CREATE_PRICING_PLAN => [
                'description' => 'create pricing plan',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::DELETE_PRICING_PLAN_RULES => [
                'description' => 'delete pricing plan rules',
                'assignable' => true,
                'workflow' => false
            ],
        ],

        PermissionCategory::ENTITY => [
            Permission::VIEW_ALL_ENTITY => [
                'description' => 'view all entity',
                'assignable' => true,
                'workflow' => false
            ],
        ],

        // UAM

        // ORG
        PermissionCategory::ORG => [
            Permission::VIEW_ALL_ORG  => [
                'description' => 'view all org',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_ORG      => [
                'description' => 'view all org detail',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::CREATE_ORG    => [
                'description' => 'create org',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_ORG      => [
                'description' => 'Edit org',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::DELETE_ORG    => [
                'description' => 'delete org',
                'assignable' => true,
                'workflow' => false
            ],
        ],

        // Workflow
        PermissionCategory::WORKFLOW => [
            Permission::VIEW_WORKFLOW      => [
                'description' => 'View Workflows',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_WORKFLOW      => [
                'description' => 'edit workflow',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::DELETE_WORKFLOW    => [
                'description' => 'delete workflow',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_ALL_WORKFLOW  => [
                'description' => 'view all workflow',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::CREATE_WORKFLOW    => [
                'description' => 'create workflow',
                'assignable' => true,
                'workflow' => false
            ],
        ],

        // Roles
        PermissionCategory::ROLE => [
            Permission::VIEW_ALL_ROLE => [
                'description' => 'view_all_role',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_ROLE     => [
                'description' => 'view_role',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::CREATE_ROLE   => [
                'description' => 'create_role',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_ROLE     => [
                'description' => 'edit_role',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::DELETE_ROLE   => [
                'description' => 'delete_role',
                'assignable' => true,
                'workflow' => false
            ],
        ],

        // Groups
        PermissionCategory::GROUP => [
            Permission::VIEW_ALL_GROUP    => [
                'description' => 'view_all_group',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_GROUP        => [
                'description' => 'view_group',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::CREATE_GROUP      => [
                'description' => 'create_group',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_GROUP        => [
                'description' => 'create_group',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::DELETE_GROUP      => [
                'description' => 'delete_group',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::GROUP_GET_ALLOWED_GROUPS => [
                'description' => 'group get allowed groups',
                'assignable' => true,
                'workflow' => false
            ],
        ],

        // Admin
        PermissionCategory::ADMIN => [
            Permission::VIEW_ALL_ADMIN    => [
                'description' => 'view_all_admin',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_ADMIN        => [
                'description' => 'view_admin',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::CREATE_ADMIN      => [
                'description' => 'create_admin',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_ADMIN        => [
                'description' => 'create workflow',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::DELETE_ADMIN      => [
                'description' => 'create workflow',
                'assignable' => true,
                'workflow' => false
            ],
        ],

        // Permissions
        PermissionCategory::PERMISSION => [
            Permission::VIEW_ALL_PERMISSION => [
                'description' => 'view_all_permission',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::GET_PERMISSION      => [
                'description' => 'get_permission',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::DELETE_PERMISSION   => [
                'description' => 'delete_permission',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::CREATE_PERMISSION   => [
                'description' => 'create_permission',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_PERMISSION     => [
                'description' => 'edit_permission',
                'assignable' => true,
                'workflow' => false
            ],
        ],

        PermissionCategory::AUDIT_LOG => [
            Permission::VIEW_AUDITLOG     => [
                'description' => 'view_auditlog',
                'assignable' => true,
                'workflow' => false
            ],
        ],

        // Invitations
        PermissionCategory::INVITATION => [
            Permission::CREATE_MERCHANT_INVITE      => [
                'description' => 'create_merchant_invite',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_MERCHANT_INVITE        => [
                'description' => 'edit_merchant_invite',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_INVITE        => [
                'description' => 'view_merchant_invite',
                'assignable' => true,
                'workflow' => false
            ],
        ],

        PermissionCategory::GATEWAY_RULE => [
            Permission::CREATE_GATEWAY_RULE => [
                'description' => 'create_gateway_rule',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::EDIT_GATEWAY_RULE   => [
                'description' => 'edit_gateway_rule',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::DELETE_GATEWAY_RULE => [
                'description' => 'delete_gateway_rule',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_GATEWAY_RULE   => [
                'description' => 'view_gateway_rule',
                'assignable' => true,
                'workflow' => false
            ],
        ],
    ],
    'workflows' => [
        'mock'  => env('HEIMDALL_WORKFLOWS_MOCK', false),
    ],
];

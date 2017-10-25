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
        PermissionCategory::GENERAL => [
            Permission::VIEW_HOMEPAGE          => 'View Dashboard Home',
        ],

        PermissionCategory::MERCHANT => [
            Permission::VIEW_ALL_MERCHANTS    => [
                'description' => 'View all merchants in merchant lists',
                'assignable'  => true,
            ],
            Permission::VIEW_MERCHANT         => [
                'description' => 'View a particular merchant details',
                'assignable'  => true,
            ],
            Permission::MANAGE_ONBOARDING_SUBMISSIONS => [
                'description' => 'View and update product onboarding submissions and the activation statuses',
                'assignable'  => true,
            ]
        ],

        PermissionCategory::MERCHANT_DETAIL => [
            Permission::VIEW_MERCHANT_BALANCE => [
                'description' => 'View merchant balance in merchant details',
                'assignable'  => true,
            ],
            Permission::VIEW_MERCHANT_FEATURES => '',
            Permission::VIEW_MERCHANT_BANKS => '',
            Permission::VIEW_NETWORKS => '',
            Permission::VIEW_MERCHANT_BANK_ACCOUNTS => [
                'assignable' => true,
            ],
            Permission::VIEW_MERCHANT_LOGIN => '',
            Permission::VIEW_ACTIVITY => '',
            Permission::VIEW_MERCHANT_HDFC_EXCEL => '',
            Permission::VIEW_BENEFICIARY_FILE => '',
            Permission::VIEW_MERCHANT_SCREENSHOT => [
                'assignable' => true,
            ],
            Permission::VIEW_ALL_MERCHANT_AGGREGATIONS => '',
            Permission::VIEW_MERCHANT_AGGREGATIONS => '',
            Permission::VIEW_MERCHANT_TAGS => '',
            Permission::SET_PRICING_RULES => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::DELETE_EMI_PLAN => '',
            Permission::CREATE_EMI_PLAN => '',
            Permission::CREATE_MERCHANT_LOCK => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::CREATE_MERCHANT_UNLOCK => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_TAGS => '',
            Permission::EDIT_MERCHANT_FEATURES => [
                'assignable' => true,
            ],
            Permission::EDIT_MERCHANT_FEATURES => '',
            Permission::EDIT_MERCHANT_BANK_DETAIL => '',
            Permission::EDIT_IIN_RULE => '',
            Permission::EDIT_ACTIVATE_MERCHANT => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_ENABLE_LIVE => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_DISABLE_LIVE => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_ARCHIVE => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_UNARCHIVE => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_SUSPEND => [
                'assignable' => true,
            ],
            Permission::EDIT_MERCHANT_UNSUSPEND => [
                'assignable' => true,
            ],
            Permission::EDIT_MERCHANT_METHODS => '',
            Permission::EDIT_MERCHANT_ENABLE_INTERNATIONAL => '',
            Permission::EDIT_MERCHANT_DISABLE_INTERNATIONAL => '',
            Permission::EDIT_MERCHANT_TERMINAL => '',
            Permission::EDIT_MERCHANT_PRICING => '',
            Permission::EDIT_MERCHANT_COMMENTS => '',
            Permission::VIEW_MERCHANT_COMPANY_INFO => [
                'assignable' => true,
            ],
            Permission::VIEW_MERCHANT_CREDITS_LOG => '',
            Permission::ADD_MERCHANT_CREDITS => [
                'assignable' => true,
            ],
            Permission::DELETE_MERCHANT_CREDITS => '',
            Permission::EDIT_MERCHANT_SCREENSHOT => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::VIEW_PAYMENT_VERIFY => '',
            Permission::EDIT_VERIFY_PAYMENTS => '',
            Permission::EDIT_AUTHORIZED_FAILED_PAYMENT => '',
            Permission::VIEW_REFUND_PAYMENTS => '',
            Permission::EDIT_AUTHORIZED_REFUND_PAYMENT => '',
            Permission::RETRY_REFUND_FAILED => '',
            Permission::EDIT_PAYMENT_REFUND => '',
            Permission::EDIT_PAYMENT_CAPTURE => '',
            Permission::EDIT_MERCHANT_CONFIRM => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::CREATE_BENEFICIARY_FILE => '',
            Permission::CREATE_NETBANKING_REFUND => '',
            Permission::CREATE_EMI_FILES => '',
            Permission::CREATE_SETTLEMENT_INITIATE => '',
            Permission::DELETE_TERMINAL => '',
            Permission::EDIT_TERMINAL => '',
            Permission::CREATE_SETTLEMENTS_RECONCILE => '',
            Permission::CREATE_RECONCILIATE => '',
            Permission::VIEW_ACTIVATION_FORM => [
                'assignable' => true,
            ],
            Permission::EDIT_MERCHANT_LOCK_ACTIVATION => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_UNLOCK_ACTIVATION => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_HOLD_FUNDS => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_RELEASE_FUNDS => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_ENABLE_RECEIPT => '',
            Permission::EDIT_MERCHANT_DISABLE_RECEIPT => '',
            Permission::EDIT_BULK_MERCHANT_HOLD_FUNDS => '',
            Permission::ASSIGN_MERCHANT_TERMINAL => '',
            Permission::ASSIGN_MERCHANT_BANKS => '',
            Permission::ADD_MERCHANT_ADJUSTMENT => '',
            Permission::EDIT_MERCHANT_EMAIL => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::MERCHANT_AUTOFILL_FORM => '',
            Permission::EDIT_MERCHANT_MARK_REFERRED => '',
            Permission::VIEW_AS_ENTITY => '',
            Permission::VIEW_MERCHANT_REFERRER => '',
            Permission::VIEW_MERCHANT_BALANCE_TEST => [
                'assignable' => true,
            ],
            Permission::VIEW_MERCHANT_BALANCE_LIVE => [
                'assignable' => true,
            ],
            Permission::ADD_RECONCILIATION_FILE => '',
            Permission::ADD_SETTLEMENT_RECONCILIATION => '',
            Permission::RETRY_SETTLEMENT => '',
            Permission::EDIT_MERCHANT_INVOICE_GSTIN => '',
            Permission::SEND_NEWSLETTER => '',
            Permission::TRIGGER_DUMMY_ERROR => '',
            Permission::MAKE_API_CALL => '',
            Permission::SCHEDULE_CREATE => '',
            Permission::SCHEDULE_FETCH => '',
            Permission::SCHEDULE_FETCH_MULTIPLE => '',
            Permission::SCHEDULE_DELETE => '',
            Permission::SCHEDULE_UPDATE => '',
            Permission::SCHEDULE_ASSIGN => '',
            Permission::SCHEDULE_MIGRATION => '',
            Permission::VIEW_ACTIONS => '',
            Permission::VIEW_MERCHANT_STATS => '',
            Permission::DELETE_MERCHANT_FEATURES => [
                'description' => 'Delete a merchant feature',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::VIEW_MERCHANT_REPORT   => [
                'description' => 'View Merchant Reports',
            ],
            Permission::CREATE_MERCHANT_OFFER  => [
                'description' => 'Create offer for a merchant',
            ],
            Permission::EDIT_MERCHANT_OFFER => [
                'description' => 'Edit offer for a merchant',
            ],
            Permission::ASSIGN_MERCHANT_HANDLE => 'Assign merchant handle',
            Permission::VIEW_MERCHANT_PRICING  => 'View Mercant Pricing Plan',
        ],

        PermissionCategory::DISPUTE => [
            Permission::CREATE_DISPUTE        => [
                'description' => 'Create Dispute Permission',
                'assignable'  => true,
                'workflow'    => true,
            ],
            Permission::EDIT_DISPUTE          => [
                'description' => 'Edit Dispute Permission',
                'assignable'  => true,
                'workflow'    => true,
            ],
            Permission::CREATE_DISPUTE_REASON => [
                'description' => 'Create Dispute Reason Permission',
                'assignable'  => true,
            ],
        ],

        PermissionCategory::PRICING => [
            Permission::VIEW_PRICING_LIST => [
                'description' => 'view pricinglist',
            ],
            Permission::CREATE_PRICING_PLAN => [
                'description' => 'create pricing plan',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::DELETE_PRICING_PLAN_RULES => [
                'description' => 'delete pricing plan rules',
                'assignable'  => true,
                'workflow'    => true
            ],
        ],

        PermissionCategory::ENTITY => [
            Permission::VIEW_ALL_ENTITY => [
                'description' => 'view all entity',
            ],
        ],

        // UAM

        // ORG
        PermissionCategory::ORG => [
            Permission::VIEW_ALL_ORG  => [
                'description' => 'view all org',
            ],
            Permission::VIEW_ORG      => [
                'description' => 'view all org detail',
            ],
            Permission::CREATE_ORG    => [
                'description' => 'create org',
            ],
            Permission::EDIT_ORG      => [
                'description' => 'Edit org',
            ],
            Permission::DELETE_ORG    => [
                'description' => 'delete org',
            ],
        ],

        // Workflow
        PermissionCategory::WORKFLOW => [
            Permission::VIEW_WORKFLOW      => [
                'description' => 'View Workflows',
                'assignable'  => true,
            ],
            Permission::EDIT_WORKFLOW      => [
                'description' => 'edit workflow',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::DELETE_WORKFLOW    => [
                'description' => 'delete workflow',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::VIEW_ALL_WORKFLOW  => [
                'description' => 'view all workflow',
                'assignable'  => true,
            ],
            Permission::CREATE_WORKFLOW    => [
                'description' => 'create workflow',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::VIEW_WORKFLOW_REQUESTS => [
                'description' => 'View Workflow requests',
                'assignable'  => true,
            ],
        ],

        // Roles
        PermissionCategory::ROLE => [
            Permission::VIEW_ALL_ROLE => [
                'description' => 'view_all_role',
                'assignable'  => true,
            ],
            Permission::VIEW_ROLE     => [
                'description' => 'view_role',
                'assignable'  => true,
            ],
            Permission::CREATE_ROLE   => [
                'description' => 'create_role',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::EDIT_ROLE     => [
                'description' => 'edit_role',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::DELETE_ROLE   => [
                'description' => 'delete_role',
                'assignable'  => true,
                'workflow'    => true
            ],
        ],

        // Groups
        PermissionCategory::GROUP => [
            Permission::VIEW_ALL_GROUP    => [
                'description' => 'view_all_group',
                'assignable'  => true,
            ],
            Permission::VIEW_GROUP        => [
                'description' => 'view_group',
                'assignable'  => true,
            ],
            Permission::CREATE_GROUP      => [
                'description' => 'create_group',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::EDIT_GROUP        => [
                'description' => 'create_group',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::DELETE_GROUP      => [
                'description' => 'delete_group',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::GROUP_GET_ALLOWED_GROUPS => [
                'description' => 'group get allowed groups',
                'assignable'  => true,
            ],
        ],

        // Admin
        PermissionCategory::ADMIN => [
            Permission::VIEW_ALL_ADMIN    => [
                'description' => 'view_all_admin',
                'assignable'  => true,
            ],
            Permission::VIEW_ADMIN        => [
                'description' => 'view_admin',
                'assignable'  => true,
            ],
            Permission::CREATE_ADMIN      => [
                'description' => 'create_admin',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::EDIT_ADMIN        => [
                'description' => 'edit admin',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::DELETE_ADMIN      => [
                'description' => 'delete admin',
                'assignable'  => true,
                'workflow'    => true
            ],
        ],

        // Permissions
        PermissionCategory::PERMISSION => [
            Permission::VIEW_ALL_PERMISSION => [
                'description' => 'view_all_permission',
                'assignable'  => true,
            ],
            Permission::GET_PERMISSION      => [
                'description' => 'get_permission',
                'assignable'  => true,
            ],
            Permission::DELETE_PERMISSION   => [
                'description' => 'delete_permission',
                'workflow'    => true
            ],
            Permission::CREATE_PERMISSION   => [
                'description' => 'create_permission',
                'workflow'    => true
            ],
            Permission::EDIT_PERMISSION     => [
                'description' => 'edit_permission',
                'workflow'    => true
            ],
        ],

        PermissionCategory::AUDIT_LOG => [
            Permission::VIEW_AUDITLOG     => [
                'description' => 'view_auditlog',
                'assignable'  => true,
            ],
        ],

        // Invitations
        PermissionCategory::INVITATION => [
            Permission::CREATE_MERCHANT_INVITE      => [
                'description' => 'create_merchant_invite',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::EDIT_MERCHANT_INVITE        => [
                'description' => 'edit_merchant_invite',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::VIEW_MERCHANT_INVITE        => [
                'description' => 'view_merchant_invite',
                'assignable'  => true,
            ],
        ],

        PermissionCategory::GATEWAY_RULE => [
            Permission::CREATE_GATEWAY_RULE => [
                'description' => 'create_gateway_rule',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::EDIT_GATEWAY_RULE   => [
                'description' => 'edit_gateway_rule',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::DELETE_GATEWAY_RULE => [
                'description' => 'delete_gateway_rule',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::VIEW_GATEWAY_RULE   => [
                'description' => 'view_gateway_rule',
                'assignable'  => true,
                'workflow'    => true
            ],
        ],

        // RZP White label wallet config
        PermissionCategory::WALLET_CONFIG => [
            Permission::CREATE_WALLET_CONFIG => [
                'description' => 'Create Wallet Config',
                'assignable'  => true
            ],
            Permission::EDIT_WALLET_CONFIG   => [
                'description' => 'Edit Wallet Config',
                'assignable'  => true
            ],
            Permission::VIEW_WALLET_CONFIG   => [
                'description' => 'View Wallet Config',
                'assignable'  => true
            ],
        ],
    ],

    'workflows' => [
        'mock'  => env('HEIMDALL_WORKFLOWS_MOCK', false),
    ],
];

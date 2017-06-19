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
            Permission::VIEW_MERCHANT_BALANCE => 'View merchant balance in merchant details',
            Permission::VIEW_MERCHANT_FEATURES => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_BANKS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_NETWORKS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_BANK_ACCOUNTS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_LOGIN => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_ACTIVITY => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_PRICING_RULES => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_HDFC_EXCEL => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_BENEFICIARY_FILE => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_SCREENSHOT => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_ALL_MERCHANT_AGGREGATIONS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_AGGREGATIONS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::VIEW_MERCHANT_TAGS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::SET_PRICING_RULES => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::DELETE_EMI_PLAN => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],
            Permission::CREATE_EMI_PLAN => [
                'description' => '',
                'assignable' => true,
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
            Permission::DELETE_MERCHANT_FEATURES => 'Delete a merchant feature',
            Permission::VIEW_MERCHANT_REPORT   => 'View Merchant Reports',
        ],

        PermissionCategory::PRICING => [
            Permission::VIEW_PRICING_LIST => 'View Pricing Plans',
            Permission::CREATE_PRICING_PLAN => 'Create Pricing Plan',
            Permission::DELETE_PRICING_PLAN_RULES => 'Delete Pricing Plan Rule',
        ],

        PermissionCategory::ENTITY => [
            Permission::VIEW_ALL_ENTITY => 'View all entities data'
        ],

        // UAM

        // ORG
        PermissionCategory::ORG => [
            Permission::VIEW_ALL_ORG  => 'View all organizations',
            Permission::VIEW_ORG      => 'View organization detail',
            Permission::CREATE_ORG    => 'Create organization',
            Permission::EDIT_ORG      => 'Edit organization',
            Permission::DELETE_ORG    => 'Delete organization',
        ],

        // Workflow
        PermissionCategory::WORKFLOW => [
            Permission::VIEW_WORKFLOW      => 'View Workflows',
            Permission::EDIT_WORKFLOW      => 'Edit Workflows',
            Permission::DELETE_WORKFLOW    => 'Delete Workflows',
            Permission::VIEW_ALL_WORKFLOW  => 'View all workflows',
            Permission::CREATE_WORKFLOW    => 'Create a workflow',
        ],

        // Roles
        PermissionCategory::ROLE => [
            Permission::VIEW_ALL_ROLE => 'View all roles',
            Permission::VIEW_ROLE     => 'View role detail',
            Permission::CREATE_ROLE   => 'Create role',
            Permission::EDIT_ROLE     => 'Edit role',
            Permission::DELETE_ROLE   => 'Delete role',
        ],

        // Groups
        PermissionCategory::GROUP => [
            Permission::VIEW_ALL_GROUP    => 'View all groups',
            Permission::VIEW_GROUP        => 'View group detail',
            Permission::CREATE_GROUP      => 'Create group',
            Permission::EDIT_GROUP        => 'Edit group',
            Permission::DELETE_GROUP      => 'Delete group',
            Permission::GROUP_GET_ALLOWED_GROUPS => 'Get allowed groups',
        ],

        // Admin
        PermissionCategory::ADMIN => [
            Permission::VIEW_ALL_ADMIN    => 'View all admins',
            Permission::VIEW_ADMIN        => 'View admin detail',
            Permission::CREATE_ADMIN      => 'Create admin',
            Permission::EDIT_ADMIN        => 'Edit admin',
            Permission::DELETE_ADMIN      => 'Delete admin',
        ],

        // Permissions
        PermissionCategory::PERMISSION => [
            Permission::VIEW_ALL_PERMISSION => 'View all permissions',
            Permission::GET_PERMISSION      => 'get_permission',
            Permission::DELETE_PERMISSION   => 'Delete permission',
            Permission::CREATE_PERMISSION   => 'Create permission',
            Permission::EDIT_PERMISSION     => 'Edit permission',
        ],

        PermissionCategory::AUDIT_LOG => [
            Permission::VIEW_AUDITLOG     => 'View auditlog for activities',
        ],

        // Invitations
        PermissionCategory::INVITATION => [
            Permission::CREATE_MERCHANT_INVITE      => 'Create Merchant Invite',
            Permission::EDIT_MERCHANT_INVITE        => 'Edit Merchant Invite',
            Permission::VIEW_MERCHANT_INVITE        => 'View Merchant Invite',
        ],

        PermissionCategory::GATEWAY_RULE => [
            Permission::CREATE_GATEWAY_RULE => 'Create gateway rule for merchant',
            Permission::EDIT_GATEWAY_RULE   => 'Update gateway rule for merchant',
            Permission::DELETE_GATEWAY_RULE => 'Remove gateway rule for merchant',
            Permission::VIEW_GATEWAY_RULE   => 'View all gateway rules'
        ],
    ],

    // trimmed down assignable permissions which an HDFC manager would have
    // This array must be a **strict** subset of the one above

    'assignable_permissions' => [
        PermissionCategory::MERCHANT => [
            Permission::VIEW_ALL_MERCHANTS    => 'View all merchants in merchant lists',
            Permission::VIEW_MERCHANT         => 'View a particular merchant details',
        ],

        PermissionCategory::MERCHANT_DETAIL => [
            Permission::VIEW_MERCHANT_BALANCE => 'View merchant balance in merchant details',
            Permission::VIEW_MERCHANT_BANK_ACCOUNTS => [
                'description' => '',
                'assignable' => true,
                'workflow' => false
            ],

            Permission::VIEW_MERCHANT_SCREENSHOT => '',
            Permission::DELETE_MERCHANT_FEATURES => 'Delete a merchant feature',

            Permission::SET_PRICING_RULES => '',

            Permission::CREATE_MERCHANT_LOCK              => '',
            Permission::CREATE_MERCHANT_UNLOCK            => '',
            Permission::EDIT_MERCHANT                     => '',
            Permission::EDIT_ACTIVATE_MERCHANT            => '',
            Permission::EDIT_MERCHANT_ENABLE_LIVE         => '',
            Permission::EDIT_MERCHANT_DISABLE_LIVE        => '',
            Permission::EDIT_MERCHANT_ARCHIVE             => '',
            Permission::EDIT_MERCHANT_UNARCHIVE           => '',
            Permission::EDIT_MERCHANT_SUSPEND             => '',
            Permission::EDIT_MERCHANT_UNSUSPEND           => '',

            Permission::VIEW_MERCHANT_COMPANY_INFO => '',
            Permission::EDIT_MERCHANT_SCREENSHOT => '',

            Permission::VIEW_ACTIVATION_FORM              => '',
            Permission::EDIT_MERCHANT_CONFIRM             => '',
            Permission::EDIT_MERCHANT_LOCK_ACTIVATION     => '',
            Permission::EDIT_MERCHANT_UNLOCK_ACTIVATION   => '',
            Permission::EDIT_MERCHANT_HOLD_FUNDS          => '',
            Permission::EDIT_BULK_MERCHANT_HOLD_FUNDS     => '',
            Permission::EDIT_MERCHANT_RELEASE_FUNDS       => '',

            Permission::VIEW_MERCHANT_BALANCE_TEST        => '',
            Permission::VIEW_MERCHANT_BALANCE_LIVE        => '',

            Permission::EDIT_MERCHANT_EMAIL => '',

            Permission::CREATE_MERCHANT_INVITE => '',
            Permission::EDIT_MERCHANT_INVITE   => '',
            Permission::VIEW_MERCHANT_INVITE => '',
        ],

        PermissionCategory::PRICING => [
            Permission::CREATE_PRICING_PLAN => 'Create Pricing Plan',
            Permission::DELETE_PRICING_PLAN_RULES => 'Delete Pricing Plan Rule',
        ],

        // UAM

        // Roles
        PermissionCategory::ROLE => [
            Permission::VIEW_ALL_ROLE => 'View all roles',
            Permission::VIEW_ROLE     => 'View role detail',
            Permission::CREATE_ROLE   => 'Create role',
            Permission::EDIT_ROLE     => 'Edit role',
            Permission::DELETE_ROLE   => 'Delete role',
        ],

        // Groups
        PermissionCategory::GROUP => [
            Permission::VIEW_ALL_GROUP    => 'View all groups',
            Permission::VIEW_GROUP        => 'View group detail',
            Permission::CREATE_GROUP      => 'Create group',
            Permission::EDIT_GROUP        => 'Edit group',
            Permission::DELETE_GROUP      => 'Delete group',
            Permission::GROUP_GET_ALLOWED_GROUPS => 'Get allowed groups',
        ],

        // Admin
        PermissionCategory::ADMIN => [
            Permission::VIEW_ALL_ADMIN    => 'View all admins',
            Permission::VIEW_ADMIN        => 'View admin detail',
            Permission::CREATE_ADMIN      => 'Create admin',
            Permission::EDIT_ADMIN        => 'Edit admin',
            Permission::DELETE_ADMIN      => 'Delete admin',
        ],

        // Permissions
        PermissionCategory::PERMISSION => [
            Permission::VIEW_ALL_PERMISSION => 'View all permissions',
            Permission::GET_PERMISSION      => 'Get Permission',
        ],

        PermissionCategory::AUDIT_LOG => [
            Permission::VIEW_AUDITLOG   => 'View auditlog for activities',
        ],

        // Invitations
        PermissionCategory::INVITATION => [
            Permission::CREATE_MERCHANT_INVITE      => 'Create Merchant Invite',
            Permission::EDIT_MERCHANT_INVITE        => 'Edit Merchant Invite',
            Permission::VIEW_MERCHANT_INVITE        => 'View Merchant Invite',
        ],

        PermissionCategory::WORKFLOW => [
            Permission::VIEW_WORKFLOW      => 'View Workflows',
            Permission::EDIT_WORKFLOW      => 'Edit Workflows',
            Permission::DELETE_WORKFLOW    => 'Delete Workflows',
            Permission::VIEW_ALL_WORKFLOW  => 'View all workflows',
            Permission::CREATE_WORKFLOW    => 'Create a workflow',
        ],

        PermissionCategory::GATEWAY_RULE => [
            Permission::CREATE_GATEWAY_RULE => 'Create gateway rule for merchant',
            Permission::EDIT_GATEWAY_RULE   => 'Update gateway rule for merchant',
            Permission::DELETE_GATEWAY_RULE => 'Remove gateway rule for merchant',
            Permission::VIEW_GATEWAY_RULE   => 'View all gateway rules'
        ],
    ],

    'enable_workflow_permissions' => [
        PermissionCategory::MERCHANT_DETAIL => [
            Permission::DELETE_MERCHANT_FEATURES        => 'Delete a merchant feature',

            Permission::SET_PRICING_RULES               => '',

            Permission::CREATE_MERCHANT_LOCK            => '',
            Permission::CREATE_MERCHANT_UNLOCK          => '',
            Permission::EDIT_MERCHANT                   => '',
            Permission::EDIT_ACTIVATE_MERCHANT          => '',
            Permission::EDIT_MERCHANT_ENABLE_LIVE       => '',
            Permission::EDIT_MERCHANT_DISABLE_LIVE      => '',
            Permission::EDIT_MERCHANT_ARCHIVE           => '',
            Permission::EDIT_MERCHANT_UNARCHIVE         => '',
            Permission::EDIT_MERCHANT_SCREENSHOT        => '',

            Permission::EDIT_MERCHANT_CONFIRM           => '',
            Permission::EDIT_MERCHANT_LOCK_ACTIVATION   => '',
            Permission::EDIT_MERCHANT_UNLOCK_ACTIVATION => '',
            Permission::EDIT_MERCHANT_HOLD_FUNDS        => '',
            Permission::EDIT_MERCHANT_RELEASE_FUNDS     => '',

            Permission::EDIT_MERCHANT_EMAIL             => '',

            Permission::CREATE_MERCHANT_INVITE          => '',
            Permission::EDIT_MERCHANT_INVITE            => '',

            // Workflow can be created on add merchant credits
            Permission::ADD_MERCHANT_CREDITS            => '',
        ],

        PermissionCategory::PRICING => [
            Permission::CREATE_PRICING_PLAN => 'Create Pricing Plan',
            Permission::DELETE_PRICING_PLAN_RULES => 'Delete Pricing Plan Rule',
        ],

        // UAM

        // Roles
        PermissionCategory::ROLE => [
            Permission::CREATE_ROLE   => 'Create role',
            Permission::EDIT_ROLE     => 'Edit role',
            Permission::DELETE_ROLE   => 'Delete role',
        ],

        // Groups
        PermissionCategory::GROUP => [
            Permission::CREATE_GROUP      => 'Create group',
            Permission::EDIT_GROUP        => 'Edit group',
            Permission::DELETE_GROUP      => 'Delete group',
        ],

        // Admin
        PermissionCategory::ADMIN => [
            Permission::CREATE_ADMIN      => 'Create admin',
            Permission::EDIT_ADMIN        => 'Edit admin',
            Permission::DELETE_ADMIN      => 'Delete admin',
        ],

        // Invitations
        PermissionCategory::INVITATION => [
            Permission::CREATE_MERCHANT_INVITE      => 'Create Merchant Invite',
            Permission::EDIT_MERCHANT_INVITE        => 'Edit Merchant Invite',
        ],

        PermissionCategory::WORKFLOW => [
            Permission::EDIT_WORKFLOW      => 'Edit Workflows',
            Permission::DELETE_WORKFLOW    => 'Delete Workflows',
            Permission::CREATE_WORKFLOW    => 'Create a workflow',
        ],

        PermissionCategory::GATEWAY_RULE => [
            Permission::CREATE_GATEWAY_RULE => 'Create gateway rule for merchant',
            Permission::EDIT_GATEWAY_RULE   => 'Update gateway rule for merchant',
            Permission::DELETE_GATEWAY_RULE => 'Remove gateway rule for merchant',
            Permission::VIEW_GATEWAY_RULE   => 'View all gateway rules'
        ],
    ],

    'workflows' => [
        'mock'  => env('HEIMDALL_WORKFLOWS_MOCK', false),
    ],
];

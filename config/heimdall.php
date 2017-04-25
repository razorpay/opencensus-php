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
            Permission::VIEW_ALL_MERCHANTS    => 'View all merchants in merchant lists',
            Permission::VIEW_MERCHANT         => 'View a particular merchant details',
        ],

        PermissionCategory::MERCHANT_DETAIL => [
            Permission::VIEW_MERCHANT_BALANCE => 'View merchant balance in merchant details',
            Permission::VIEW_MERCHANT_FEATURES => '',
            Permission::VIEW_MERCHANT_BANKS => '',
            Permission::VIEW_NETWORKS => '',
            Permission::VIEW_MERCHANT_BANK_ACCOUNTS => '',
            Permission::VIEW_MERCHANT_LOGIN => '',
            Permission::VIEW_ACTIVITY => '',
            Permission::VIEW_PRICING_LIST => '',
            Permission::VIEW_MERCHANT_PRICING_RULES => '',
            Permission::VIEW_MERCHANT_HDFC_EXCEL => '',
            Permission::VIEW_BENEFICIARY_FILE => '',
            Permission::VIEW_MERCHANT_SCREENSHOT => '',
            Permission::VIEW_ALL_MERCHANT_AGGREGATIONS => '',
            Permission::VIEW_MERCHANT_AGGREGATIONS => '',
            Permission::VIEW_MERCHANT_TAGS => '',
            Permission::CREATE_PRICING_PLAN => '',
            Permission::SET_PRICING_RULES => '',
            Permission::DELETE_PRICING_PLAN_RULES => '',
            Permission::DELETE_EMI_PLAN => '',
            Permission::CREATE_EMI_PLAN => '',
            Permission::CREATE_MERCHANT_LOCK => '',
            Permission::CREATE_MERCHANT_UNLOCK => '',
            Permission::EDIT_MERCHANT => '',
            Permission::EDIT_MERCHANT_TAGS => '',
            Permission::EDIT_MERCHANT_FEATURES => '',
            Permission::EDIT_MERCHANT_FEATURES => '',
            Permission::EDIT_MERCHANT_BANKS => '',
            Permission::EDIT_IIN_RULE => '',
            Permission::CREATE_MERCHANT_ADJUSTMENTS => '',
            Permission::EDIT_ACTIVATE_MERCHANT => '',
            Permission::EDIT_MERCHANT_ENABLE_LIVE => '',
            Permission::EDIT_MERCHANT_DISABLE_LIVE => '',
            Permission::EDIT_MERCHANT_ARCHIVE => '',
            Permission::EDIT_MERCHANT_UNARCHIVE => '',
            Permission::EDIT_MERCHANT_METHODS => '',
            Permission::EDIT_MERCHANT_INTERNATIONAL => '',
            Permission::EDIT_MERCHANT_TERMINAL => '',
            Permission::EDIT_MERCHANT_PRICING => '',
            Permission::VIEW_MERCHANT_COMPANY_INFO => '',
            Permission::VIEW_MERCHANT_CREDITS_LOG => '',
            Permission::ADD_MERCHANT_CREDITS => '',
            Permission::DELETE_MERCHANT_CREDITS => '',
            Permission::EDIT_MERCHANT_SCREENSHOT => '',
            Permission::VIEW_PAYMENT_VERIFY => '',
            Permission::EDIT_VERIFY_PAYMENTS => '',
            Permission::EDIT_AUTHORIZED_FAILED_PAYMENT => '',
            Permission::VIEW_REFUND_PAYMENTS => '',
            Permission::EDIT_AUTHORIZED_REFUND_PAYMENT => '',
            Permission::EDIT_PAYMENT_REFUND => '',
            Permission::EDIT_PAYMENT_CAPTURE => '',
            Permission::EDIT_MERCHANT_CONFIRM => '',
            Permission::CREATE_BENEFICIARY_FILE => '',
            Permission::CREATE_NETBANKING_REFUND => '',
            Permission::CREATE_SETTLEMENT_INITIATE => '',
            Permission::DELETE_TERMINAL => '',
            Permission::EDIT_TERMINAL => '',
            Permission::CREATE_SETTLEMENTS_RECONCILE => '',
            Permission::CREATE_RECONCILIATE => '',
            Permission::VIEW_ACTIVATION_FORM => '',
            Permission::EDIT_MERCHANT_LOCK_ACTIVATION => '',
            Permission::EDIT_MERCHANT_UNLOCK_ACTIVATION => '',
            Permission::EDIT_MERCHANT_HOLD_FUNDS => '',
            Permission::EDIT_MERCHANT_RELEASE_FUNDS => '',
            Permission::EDIT_MERCHANT_ENABLE_RECEIPT => '',
            Permission::EDIT_MERCHANT_DISABLE_RECEIPT => '',
            Permission::ASSIGN_MERCHANT_TERMINAL => '',
            Permission::ASSIGN_MERCHANT_BANKS => '',
            Permission::ADD_MERCHANT_ADJUSTMENT => '',
            Permission::EDIT_MERCHANT_EMAIL => '',
            Permission::MERCHANT_AUTOFILL_FORM => '',
            Permission::EDIT_MERCHANT_MARK_REFERRED => '',
            Permission::VIEW_AS_ENTITY => '',
            Permission::VIEW_MERCHANT_REFERRER => '',
            Permission::VIEW_MERCHANT_BALANCE_TEST => '',
            Permission::VIEW_MERCHANT_BALANCE_LIVE => '',
            Permission::ADD_RECONCILIATION_FILE => '',
            Permission::ADD_SETTLEMENT_RECONCILIATION => '',
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
            Permission::DELETE_MERCHANT_FEATURES => 'Delete a merchant feature',
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
            Permission::VIEW_MERCHANT_BANK_ACCOUNTS => '',

            Permission::VIEW_MERCHANT_SCREENSHOT => '',
            Permission::DELETE_MERCHANT_FEATURES => 'Delete a merchant feature',

            Permission::CREATE_PRICING_PLAN => '',
            Permission::SET_PRICING_RULES => '',
            Permission::DELETE_PRICING_PLAN_RULES => '',

            Permission::CREATE_MERCHANT_LOCK              => '',
            Permission::CREATE_MERCHANT_UNLOCK            => '',
            Permission::EDIT_MERCHANT                     => '',
            Permission::EDIT_ACTIVATE_MERCHANT            => '',
            Permission::EDIT_MERCHANT_ENABLE_LIVE         => '',
            Permission::EDIT_MERCHANT_DISABLE_LIVE        => '',
            Permission::EDIT_MERCHANT_ARCHIVE             => '',
            Permission::EDIT_MERCHANT_UNARCHIVE           => '',

            Permission::VIEW_MERCHANT_COMPANY_INFO => '',
            Permission::EDIT_MERCHANT_SCREENSHOT => '',

            Permission::VIEW_ACTIVATION_FORM              => '',
            Permission::EDIT_MERCHANT_CONFIRM             => '',
            Permission::EDIT_MERCHANT_LOCK_ACTIVATION     => '',
            Permission::EDIT_MERCHANT_UNLOCK_ACTIVATION   => '',
            Permission::EDIT_MERCHANT_HOLD_FUNDS          => '',
            Permission::EDIT_MERCHANT_RELEASE_FUNDS       => '',

            Permission::VIEW_MERCHANT_BALANCE_TEST        => '',
            Permission::VIEW_MERCHANT_BALANCE_LIVE        => '',

            Permission::EDIT_MERCHANT_EMAIL => '',

            Permission::CREATE_MERCHANT_INVITE => '',
            Permission::EDIT_MERCHANT_INVITE   => '',
            Permission::VIEW_MERCHANT_INVITE => '',
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
    ],

    'enable_workflow_permissions' => [
        PermissionCategory::MERCHANT_DETAIL => [
            Permission::DELETE_MERCHANT_FEATURES        => 'Delete a merchant feature',

            Permission::CREATE_PRICING_PLAN             => '',
            Permission::SET_PRICING_RULES               => '',
            Permission::DELETE_PRICING_PLAN_RULES       => '',

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
            Permission::GROUP_GET_ALLOWED_GROUPS => 'Get allowed groups',
        ],

        // Admin
        PermissionCategory::ADMIN => [
            Permission::CREATE_ADMIN      => 'Create admin',
            Permission::EDIT_ADMIN        => 'Edit admin',
            Permission::DELETE_ADMIN      => 'Delete admin',
        ],

        // Permissions
        PermissionCategory::PERMISSION => [
            Permission::GET_PERMISSION      => 'Get Permission',
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
    ],

    'workflows' => [
        'mock'  => env('HEIMDALL_WORKFLOWS_MOCK', false),
    ],
];

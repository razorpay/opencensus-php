<?php

// We use {orgId} when frontend is not supposed to pass it
// and resolve (on the backend) automagically.

return [
    // auth
    'proxy' => [
        // Feature onboarding responses with questions
        'onboarding_features_fetch_details' => 'onboarding/features',
        'onboarding_features_update_status' => 'onboarding/features/{feature}/status',

        // Credits
        'credits_fetch_multiple'            => 'credits',

        // Balance
        'balance_fetch'                     => [
            'url'       => 'balance',
            'routeName' => 'balance_get'
        ],

        // Edit Merchant config
        'merchant_edit_config'              => [
            'url'       => 'account/config',
            'routeName' => 'put_config'
        ],

        // Merchant Analytics Stats
        'merchant_analytics'                => 'merchant/analytics',

        // Refund Payment
        'payment_refund'                    => 'payments/{id}/refund',
        // Capture Payment
        'payment_capture'                   => 'payments/{id}/capture',
        // View Payment Refunds
        'payment_fetch_refunds'             => 'payments/{id}/refunds',
        // Offer create / update
        'offer_create'                      => 'offers',
        'offer_update'                      => 'offers/{id}',
        'invitation_fetch'                  => 'invitations',
        'reports_refund_irctc'              => 'reports/refund/irctc',
        'dispute_edit'                      => 'disputes/{id}',

        // Get Merchant Users
        'merchant_fetch_users'              => 'merchants/users',
    ],

    // auth
    'internal' => [
        // Admin Routes
        // Pricing
        'pricing_get_merchant_plans'        => 'pricing/merchants',
        'pricing_get_plan'                  => 'pricing/{id}',
        'pricing_add_plan_rule'             => 'pricing/{id}/rule',
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
        // Generate Refunds Excel
        'refund_generate_excel'             => 'refunds/excel',
        // Generate Emi Excel
        'emi_generate_excel'                => 'emi/generate/excel',
        // Trigger Dummy Error
        'dummy_critical_error'              => 'trigger/error',

        'refund_verify_failed'              => 'refunds/{id}/retry',

        // Tags
        'merchant_get_tags'                 => 'merchants/{id}/tags',
        'merchant_tag_add'                  => 'merchants/{id}/tags',
        'merchant_tag_delete'               => 'merchants/{id}/tags/{tagName}',

        // Edit Merchant Email
        'merchant_edit_email'               => 'merchants/{id}/email',

        // Accept/Reject Invitation
        'invitation_action'                 => 'invitations/{id}/{action}',

        // Make test payment for Virtual Account
        'bank_transfer_process'             => 'ecollect/validate',

        'admin_authentication'              => 'admin/authenticate',
        'admin_oauth_authenticate'          => 'admin/oauth_login',
        'admin_edit_app_auth'               => 'admin-app-auth/{id}',
        'admin_get_app_auth'                => 'current_admin',

        'user_merchant_upgrade'             => 'users/upgrade-merchant',

        // gateway files
        'gateway_file_create'               => 'gateway/files',

        // edit the bank account of the payers for bank transfer
        'bank_transfers_edit_payer_account' => 'bank_transfers/{id}/payer_bank_account',

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

        'merchant_action'                   => 'merchants/{id}/action',
        'merchant_live_enable'              => 'merchants/{id}/live/enable',
        'merchant_live_disable'             => 'merchants/{id}/live/disable',

        // Entities
        'admin_fetch_entity_by_id'          => 'admin/{type}/{id}',
        'admin_fetch_terminal_by_id'        => 'admin/terminal/{id}',
        'admin_fetch_entity_multiple'       => 'admin/{type}',

        // Toggle Terminal
        'terminal_toggle'                   => 'terminals/{id}/toggle',
        // Delete Terminal
        'terminal_delete'                   => 'terminals/{id}',
        // Edit Terminal
        'terminal_edit'                     => 'terminals/{id}',
        // Terminal Change Primary Merchant
        'terminal_reassign_merchant'        => 'terminals/{id}/reassign',
        // Terminal Assign Sub Merchants
        'terminal_add_merchant'             => 'terminals/{id}/merchants/{mid}',
        // Terminal Remove Sub Merchant
        'terminal_remove_merchant'          => 'terminals/{id}/merchants/{mid}',

        // Delete EMI Plan
        'emi_plan_delete'                   => 'emi/{id}',

        // Edit IIN
        'iin_edit'                          => 'iins/{id}',

        // Gateway Rules
        'gateway_create_rule'               => 'gateway/rules',
        'gateway_update_rule'               => 'gateway/rules/{id}',
        'gateway_delete_rule'               => 'gateway/rules/{id}',
        // Payment Dispute
        'payment_disputes'                  => 'payments/{id}/disputes',
        // Batch actions
        'batch_process_by_id'               => 'batches/{id}/process',

        'org_create'                        => 'orgs',
        'org_get_multiple'                  => 'orgs',
        // this should not be {orgId}
        'org_get'                           => 'orgs/{id}',
        'org_edit'                          => 'orgs/{id}',
        'org_delete'                        => 'orgs/{id}',

        // Roles
        'role_get_multiple'                 => 'roles',
        'role_get'                          => 'roles/{roleId}',
        'role_create'                       => 'roles',
        'role_delete'                       => 'roles/{roleId}',
        'role_edit'                         => 'roles/{roleId}',

        // Groups
        'group_get_multiple'                => 'groups',
        'group_create'                      => 'groups',
        'group_get'                         => 'groups/{groupId}',
        'group_delete'                      => 'groups/{groupId}',
        'edit_group'                        => 'groups/{groupId}',
        'group_get_allowed_groups'          => 'groups/{groupId}/allowed_groups',

        // Admins
        'admin_get'                         => 'admin/{adminId}/fetch',
        'admin_get_multiple'                => 'admins',
        'admin_edit'                        => 'admin/{adminId}',
        'admin_delete'                      => 'admin/{adminId}',
        'admin_create'                      => 'admins',
        'admin_logout'                      => 'admin/logout',

        // AuditLog
        'auditlog_search'                   => 'auditlog/search',

        // Permissions
        'permission_get_by_type'            => 'permissions/get/{type}',
        'permission_get_roles'              => 'permissions/{id}/roles',
        'permission_get_multiple'           => 'permissions-multiple',
        'permission_create'                 => 'permissions',
        'permission_get'                    => 'permissions/{id}',
        'permission_edit'                   => 'permissions/{id}',
        'permission_delete'                 => 'permissions/{id}',

        'merchant_attach_admin'             => 'merchants/{id}/admins',

        // STRICTLY NEED X-ADMIN-TOKEN

        // Org Field Maps
        'org_fieldmap_get_multiple'         => 'orgs/{orgId}/field-map',
        'org_fieldmap_get'                  => 'orgs/{orgId}/field-map/{id}',
        'org_fieldmap_create'               => 'orgs/{orgId}/field-map',
        'org_fieldmap_edit'                 => 'orgs/{orgId}/field-map/{id}',
        'org_fieldmap_delete'               => 'orgs/{orgId}/field-map/{id}',
        'org_fieldmap_get_by_entity'        => 'orgs/{orgId}/field-map/entity/{entity}',

        'admin_lead_create'                 => 'admin-lead',
        'admin_lead_get_multiple'           => 'admin-lead-multiple',
        'admin_lead_put'                    => 'admin-lead/{id}',

        // Admin Change Password
        'admin_change_password'             => 'admin/change_password',

        // Get Admin File
        'admin_get_file'                    => 'files/{fileId}/signed-url',

        // Workflows
        'workflow_get_multiple'             => 'workflows',
        'workflow_create'                   => 'workflows',
        'workflow_get'                      => 'workflows/{id}',
        'workflow_update'                   => 'workflows/{id}',
        'workflow_delete'                   => 'workflows/{id}',
        'action_diff_get'                   => 'w-actions/{id}/diff',
        'action_comment_create'             => 'w-actions/{id}/comments',
        'action_comment_fetch'              => 'w-actions/{id}/comments',
        'workflow_action_details'           => 'w-actions/{id}/details',
        'workflow_action_update'            => 'w-actions/{id}',
        'action_checker_create'             => 'w-actions/{id}/checkers',
        'action_request_execute'            => 'w-actions/{id}/execute',
        'workflow_action_close'             => 'w-actions/close/{id}',
        'workflow_action_get_multiple'      => 'w-actions',

        // Admin Actions
        // Create Schedule
        'schedule_create'                   => 'schedules',
        'schedule_assign'                   => 'merchants/{id}/schedules',
        'setl_fetch_schedule'               => 'settlements/schedules',

        // Add Adjustment
        'adj_add'                           => 'adjustments',

        // Feature Delete
        'feature_delete'                    => 'features/{entityId}/{featureName}',

        // Fetch Merchants from ES
        'admin_fetch_merchants_new'         => 'admins/merchants',

        'admin_fetch_all_entities'          => 'admin/entities/all',

        // Admin Payment Actions
        // Refund Authorized Payment
        'payment_authorize_refund'          => 'payments/{id}/authorize_refund',
        'pricing_create_plan'               => 'pricing',
        'merchant_get_pricing'              => 'merchants/{id}/pricing',
        'merchant_get_terminals'            => 'merchants/{id}/terminals',
        'merchant_details_fetch'            => 'merchants/details',

        // Retry Settlements
        'setl_retry'                        => 'settlements/retry',

        // Reconcile Settlements
        'setl_reconcile'                    => 'settlements/reconcile/{channel}',

        // Merchant Batches
        'merchant_batches'                  => 'merchant/{id}/batches',

        // Update GSTIN for given invoice
        'merchant_invoice_update_gstin'     => 'merchants/{id}/invoice/gstin',

        'merchant_activation_files'         => 'merchant/activation/{id}/files',
        'merchant_activation_archive'       => 'merchant/activation/{id}/archive',
        'merchant_activation_status'        => 'merchant/activation/{id}/activation_status',
        'merchant_get_rejection_reasons'    => 'merchant/activation/rejection_reasons',
        'merchant_activation_status_change_log' => 'merchant/activation/{id}/status_change_log',

        // Add new dispute reason
        'dispute_reason_create'             => 'disputes/reasons',

        // Product Submissions
        'onboarding_features_fetch_submissions' => 'onboarding/features/submissions',
        'onboarding_features_get_submissions' => 'onboarding/features/submissions/fetch',

        'onboarding_features_update'        => 'onboarding/features/{feature}/update',

        // Confirm User
        'user_confirm_by_data'              => 'users/confirm_user_by_data',
    ],
];

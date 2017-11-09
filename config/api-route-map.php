<?php

// We use {orgId} when frontend is not supposed to pass it
// and resolve (on the backend) automagically.

return [
    // NoAuth
    // For routes which are being hit without authentication, entry should be in
    // both guest and also the respective auth
    'guest' => [
        // Confirm User
        'user_confirm_by_data',
        // Invitation fetch by token
        'invitation_fetch_by_token',
        // User Forgot Password
        'user_reset_password_create',
        // User Forgot Password Reset Password using Token
        'user_reset_password_token',
    ],

    // auth
    'admin' => [
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

        // Merchant Batches
        'merchant_batches'                  => 'merchant/{id}/batches',

        // Update GSTIN for given invoice
        'merchant_invoice_update_gstin'     => 'merchants/{id}/invoice/gstin',

        'merchant_activation_files'         => 'merchant/activation/{id}/files',

        // Add new dispute reason
        'dispute_reason_create'             => 'disputes/reasons',

        // Product Submissions
        'onboarding_features_fetch_submissions' => 'onboarding/features/submissions',

        'onboarding_features_update'        => 'onboarding/features/{feature}/update',
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
        'payment_bank_transfer_fetch'       => [
            'url'       => 'payments/{id}/bank_transfer',
            'routeName' => 'payment_bank_transfer_fetch'
        ],
        'payment_fetch_refunds'             => [
            'url'       => 'payments/{id}/refunds',
            'routeName' => 'payment_get_refunds'
        ],
        'payment_fetch_transfers'             => [
            'url'       => 'payments/{id}/transfers',
            'routeName' => 'payment_get_transfers'
        ],
        'payment_capture'                   => [
            'url'       => 'payments/{id}/capture',
            'routeName' => 'post_capture'
        ],
        'payment_refund'                    => [
            'url'       => 'payments/{id}/refund',
            'routeName' => 'post_refund'
        ],
        'payment_transfer'                  => [
            'url'       => 'payments/{id}/transfers',
            'routeName' => 'post_transfer'
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
        'merchant_edit_config_logo'         => [
            'url'       => 'account/config/logo',
            'routeName' => 'post_config_logo'
        ],

        // Bank Account Fetch
        'bank_account_fetch'                => [
            'url'       => 'account/bank_account',
            'routeName' => 'bank_account_fetch'
        ],

        // Features
        'merchant_get_features'             => 'merchants/{id}/features',
        'merchant_update_features'          => 'merchants/{id}/features',

        // Referrals
        'merchant_fetch_referrals'          => [
            'url'       => 'referrals',
            'routeName' => 'referred_merchants_list'
        ],

        // Activation
        'merchant_activation_save'          => [
            'url'       => 'merchant/activation',
            'routeName' => 'post_activation_save_step'
        ],
        'merchant_activation_upload_file'   => [
            'url'       => 'merchant/activation/upload',
            'routeName' => 'post_activation_save_file'
        ],
        'merchant_activation_details'       => [
            'url'       => 'merchant/activation',
            'routeName' => 'get_activation_details'
        ],

        // Batches [Used for Refunds, Payment Links]
        'batch_fetch_multiple'              => [
            'url'       => 'batches',
            'routeName' => 'batch_fetch_multiple'
        ],
        'batch_fetch_by_id'                 => [
            'url'       => 'batches/{id}',
            'routeName' => 'batch_fetch_single'
        ],
        'batch_download_file'               => [
            'url'       => 'batches/{id}/download',
            'routeName' => 'batch_download'
        ],
        'batch_create'                      => [
            'url'       => 'batches',
            'routeName' => 'batch_upload'
        ],

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
        'invoice_issue_by_batch'            => [
            'url'       => 'invoices/batch/{batchId}/issue',
            'routeName' => 'invoice_issue_by_batch',
        ],
        'invoice_batches_issuable'          => [
            'url'       => 'invoices/batches/issuable',
            'routeName' => 'invoice_batches_issuable',
        ],

        // Customers
        'customer_fetch_multiple'           => [
            'url'       => 'customers',
            'routeName' => 'customer_read'
        ],
        'customer_fetch_by_id'           => [
            'url'       => 'customers/{id}',
            'routeName' => 'customer_read'
        ],
        'customer_create'                   => [
            'url'       => 'customers',
            'routeName' => 'customer_write'
        ],
        'customer_update'                   => [
            'url'       => 'customers/{id}',
            'routeName' => 'customer_write'
        ],
        'customer_delete'                   => [
            'url'       => 'customers/{id}',
            'routeName' => 'customer_write'
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

        // Transfers
        'transfer_fetch_multiple'           => [
            'url'       => 'transfers',
            'routeName' => 'marketplace_read'
        ],
        'transfer_fetch'           => [
            'url'       => 'transfers/{id}',
            'routeName' => 'marketplace_read'
        ],

        'transfer_reversal'           => [
            'url'       => 'transfers/{id}/reversals',
            'routeName' => 'marketplace_read'
        ],

        'transfer_edit'                     => [
            'url'       => 'transfers/{id}',
            'routeName' => 'marketplace_edit'
        ],

        // Reversals
        'reversal_fetch_multiple'           => [
            'url'       => 'reversals',
            'routeName' => 'marketplace_read'
        ],
        'reversal_fetch'           => [
            'url'       => 'reversals/{id}',
            'routeName' => 'marketplace_read'
        ],

        // OAuth routes
        'oauth_application_create'   => [
            'url'       => 'oauth/applications',
            'routeName' => 'oauth_read'
        ],
        'oauth_application_fetch_multiple'   => [
            'url'       => 'oauth/applications',
            'routeName' => 'oauth_read'
        ],
        'oauth_application_fetch'   => [
            'url'       => 'oauth/applications/{id}',
            'routeName' => 'oauth_read'
        ],
        'oauth_application_delete'  => [
            'url'       => 'oauth/applications/{id}',
            'routeName' => 'oauth_read'
        ],
        'oauth_application_update'  => [
            'url'       => 'oauth/applications/{id}',
            'routeName' => 'oauth_read'
        ],
        'oauth_token_fetch_multiple'  => [
            'url'       => 'oauth/tokens/',
            'routeName' => 'oauth_read'
        ],
        'oauth_token_revoke'  => [
            'url'       => 'oauth/tokens/{id}/revoke',
            'routeName' => 'oauth_read'
        ],

        // GST
        'merchant_gst_fetch'    =>  [
            'url'         => 'merchant/gst',
            'routeName'   => 'merchant_gst_fetch'
        ],

        'merchant_gst_edit'     => [
            'url'       => 'merchant/gst',
            'routeName' => 'merchant_gst_edit'
        ],

         // Invitations
        'invitation_create'                 => [
            'url'       => 'invitations',
            'routeName' => 'invitations_send'
         ],

        'invitation_resend'                 => [
            'url'       => 'invitations/{id}/resend',
            'routeName' => 'invitation_resend'
        ],
        'invitation_edit'                   => [
            'url'       => 'invitations/{id}',
            'routeName' => 'invitations_edit'
        ],
        'invitation_delete'                 => [
            'url'       => 'invitations/{id}',
            'routeName' => 'invitations_delete'
        ],

        // Virtual Accounts
        'virtual_account_fetch_multiple'    => [
            'url'       => 'virtual_accounts',
            'routeName' => 'virtual_accounts_read'
        ],
        'virtual_account_fetch'             => [
            'url'       => 'virtual_accounts/{id}',
            'routeName' => 'virtual_accounts_read'
        ],
        'virtual_account_create'            => [
            'url'       => 'virtual_accounts',
            'routeName' => 'virtual_accounts_write'
        ],
        'virtual_account_update'            => [
            'url'       => 'virtual_accounts/{id}',
            'routeName' => 'virtual_accounts_write'
        ],
        'virtual_account_fetch_payments'    => [
            'url'       => 'virtual_accounts/{id}/payments',
            'routeName' => 'virtual_accounts_read'
        ],

        // Subscriptions
        'subscription_fetch_multiple'    => [
            'url'       => 'subscriptions',
            'routeName' => 'subscriptions_read'
        ],
        'subscription_account_fetch'             => [
            'url'       => 'subscriptions/{id}',
            'routeName' => 'subscriptions_read'
        ],
        'subscription_create'            => [
            'url'       => 'subscriptions',
            'routeName' => 'subscriptions_write'
        ],
        'subscription_update'            => [
            'url'       => 'subscriptions/{id}',
            'routeName' => 'subscriptions_write'
        ],
        'subscription_delete'            => [
            'url'       => 'subscriptions/{id}',
            'routeName' => 'subscriptions_write'
        ],
        'subscription_cancel'            => [
            'url'       => 'subscriptions/{id}/cancel',
            'routeName' => 'subscriptions_write'
        ],
        'subscription_manual_retry' => [
            'url'       => 'invoices/{invoice_id}/charge',
            'routeName' => 'subscriptions_write'
        ],
        'subscription_test_charge' => [
            'url'       => 'subscriptions/{id}/charge',
            'routeName' => 'subscriptions_write'
        ],

        // Plans
        'plan_fetch_multiple'    => [
            'url'       => 'plans',
            'routeName' => 'subscriptions_read'
        ],
        'plan_account_fetch'             => [
            'url'       => 'plans/{id}',
            'routeName' => 'subscriptions_read'
        ],
        'plan_create'            => [
            'url'       => 'plans',
            'routeName' => 'subscriptions_write'
        ],
        'plan_update'            => [
            'url'       => 'plans/{id}',
            'routeName' => 'subscriptions_write'
        ],
        'plan_delete'            => [
            'url'       => 'plans/{id}',
            'routeName' => 'subscriptions_write'
        ],

        // Addons
        'subscription_create_addon'      => 'subscriptions/{subscription_id}/addons',
        'addon_fetch'                    => 'addons/{addon_id}',
        'addon_fetch_multiple'           => 'addons',
        'addons_fetch_due'               => 'subscriptions/{subscription_id}/addons/due',
        'addon_delete'                   => 'addons/{addon_id}',

        // Feature onboarding responses
        'feature_onboarding_fetch_all_responses' => 'feature/onboarding/responses',

        // Onboarding
        'feature_onboarding_create' => [
            'url'       => 'feature/onboarding/{feature}',
            'routeName' => 'feature_onboarding_create'
        ],
        'feature_onboarding_fetch_responses' => [
            'url'       => 'feature/onboarding/{feature}/responses',
            'routeName' => 'feature_onboarding_fetch_responses'
        ],

        // User Merchant Mapping Action
        'user_merchant_mapping_action'      => 'users/{id}/{action}',

        // Create SubMerchant
        'merchant_sub_create'               => 'submerchants',
        // Create SubMerchant User
        'create_submerchant_user'           => [
            'url'       => 'submerchant/user/{id}',
            'routeName' => 'subuser_register'
        ],

        'merchant_pre_signup_details'       => 'pre_signup',
        'merchant_edit_pre_signup_details'  => 'pre_signup',
    ],

    // auth
    'admin_proxy' => [
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

        // User Signup
        'user_register'                     => 'users/register',
        // User Login
        'user_login'                        => 'users/login',
        // Resend Verification
        'user_resend_verification'          => 'users/resend-verification',
        // Fetch user
        'user_fetch'                        => 'users/{id}',
        // Fetch User by email
        'user_fetch_email'                  => 'users/email/{email}',
        // User change password
        'user_change_password'              => 'users/{id}/password',
        // Forgot Password
        'user_reset_password_create'        => 'users/reset-password',
        // User Forgot Password Reset Password using Token
        'user_reset_password_token'         => 'users/reset-password-token',

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

        'admin_lead_verify'                 => 'admin-lead/verify/{token}',
        'merchant_admin_lead_put'           => 'admin-lead-merchant/{id}',

        // Get Org details by hostname (for heimdall specifics)
        'org_get_by_hostname'               => 'orgs/hostname/{hostname}',

        // Get Merchant Users
        'merchant_fetch_users'              => 'merchants/{id}/users',
        // Edit Merchant Email
        'merchant_edit_email'               => 'merchants/{id}/email',

        // Accept/Reject Invitation
        'invitation_action'                 => 'invitations/{id}/{action}',
        // Invitation fetch by token
        'invitation_fetch_by_token'         => 'invitations/token/{token}',

        // Make test payment for Virtual Account
        'bank_transfer_process'             => 'ecollect/validate',

        'admin_authentication'              => 'admin/authenticate',
        'admin_oauth_authenticate'          => 'admin/oauth_login',
        'admin_edit_app_auth'               => 'admin-app-auth/{id}',
        'admin_get_app_auth'                => 'current_admin',

        'user_merchant_upgrade'             => 'users/upgrade-merchant',
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

        'merchant_action'                   => 'merchants/{id}/action',
        'merchant_live_enable'              => 'merchants/{id}/live/enable',
        'merchant_live_disable'             => 'merchants/{id}/live/disable',

        // Entities
        'admin_fetch_entity_by_id'          => 'admin/{type}/{id}',
        'admin_fetch_terminal_by_id'        => 'admin/terminal/{id}',
        'admin_fetch_entity_multiple'       => 'admin/{type}',

        // Confirm User
        'user_confirm_by_data'              => 'users/confirm_user_by_data',

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
        'dispute_edit'                      => 'disputes/{id}',
    ],
];

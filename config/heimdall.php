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
            Permission::VIEW_HOMEPAGE => 'View Dashboard Home',
            Permission::UPDATE_CONFIG_KEY  => [
                'description' => 'update config keys',
                'assignable'  => true,
            ],
            Permission::CONFIRM_USER => [
                'assignable'  => true,
            ],
            Permission::VIEW_CITIES => [
                'assignable'  => true,
                'description' => 'view list of cities',
            ],
            Permission::USER_CREATE  => [
                'assignable'  => true,
            ],
            Permission::USER_FETCH_ADMIN => [
                'assignable'  => true,
            ],
            Permission::CANCEL_BATCH => 'cancel batch',
            Permission::BULK_CREATE_ENTITY      => [
                'assignable' => true,
                'workflow'   => false,
            ],
            Permission::CORRECT_MERCHANT_OWNER_MISMATCH => 'Correct Merchant Owner Mismatch',
            Permission::VIEW_CONFIG_KEYS => [
                'assignable' => true,
            ],
            Permission::AUTH_LOCAL_ADMIN => [
                'assignable' => true,
            ],
        ],

        PermissionCategory::CONFIG_KEY => [
            Permission::MANAGE_CONFIG_KEYS => [
                'assignable' => true
            ],
            Permission::SET_CONFIG_KEYS => [
                'assignable'  => true,
            ],
            Permission::DELETE_CONFIG_KEY => [
                'assignable'  => true,
            ],
        ],

        PermissionCategory::FILE_STORE => [
            Permission::ADMIN_GET_FILE => [
                'assignable' => true,
            ]
        ],
        PermissionCategory::RAZORX => [
            Permission::RAZORX_APPROVERS => 'Approve workflows for activation of razorx experiments',
        ],

        PermissionCategory::MERCHANT => [
            Permission::VIEW_ALL_MERCHANTS            => [
                'description' => 'View all merchants in merchant lists',
                'assignable'  => true,
            ],
            Permission::MERCHANT_ACTIONS              => [
                'assignable'  => true,
            ],
            Permission::CREATE_MERCHANT               => [
                'assignable'  => true,
            ],
            Permission::VIEW_MERCHANT                 => [
                'description' => 'View a particular merchant details',
                'assignable'  => true,
            ],
            Permission::VIEW_MERCHANT_EXTERNAL        => [
                'description' => 'View a particular merchant details in external org',
                'assignable'  => true,
            ],
            Permission::MANAGE_ONBOARDING_SUBMISSIONS => [
                'description' => 'View and update product onboarding submissions and the activation statuses',
                'assignable'  => true,
            ],
            Permission::MERCHANT_EMAIL_EDIT           => [
                'description' => 'Edit Merchant Email',
                'assignable'  => true,
            ],
            Permission::MERCHANT_PRICING_PLANS => [
                'description' => 'List all the merchant pricing plans',
                'assignable'  => true,
            ],
            Permission::PAYMENTS_TERMINAL_BUY_PRICING_PLANS => [
                'description' => 'List all the terminal buy pricing plans',
                'assignable'  => true,
            ],
            Permission::ADMIN_FETCH_MERCHANTS => [
                'description' => 'Fetch Merchants',
                'assignable'  => true,
            ],
            Permission::ONBOARDING_AND_ACTIVATIONS_VIEW => [
                'description' => 'Fetch Merchants for unified dashboard tab on admin dashboard',
                'assignable' => true,
            ],
            Permission::EXECUTE_MERCHANT_MAX_PAYMENT_LIMIT_WORKFLOW => [
                'description' => 'To create workflow for updating max payment limit',
                'assignable'  => true,
                'workflow'    => true,
            ],
            Permission::VIEW_BANKING_CONFIGS => [
                'description' => 'view banking DCS configs',
                'assignable'  => true,
            ],
             Permission::UPSERT_BANKING_CONFIGS => [
                'description' => 'upsert banking DCS configs',
                'assignable'  => true,
            ]
        ],

        PermissionCategory::MERCHANT_REQUEST => [
            Permission::VIEW_MERCHANT_REQUESTS => [
                'description' => 'View merchant requests',
                'assignable'  => true,
            ],
            Permission::EDIT_MERCHANT_REQUESTS => [
                'description' => 'Edit merchant requests',
                'assignable'  => true,
            ],
        ],

        PermissionCategory::PARTNER => [
            Permission::VIEW_PARTNERS => [
                'description' => 'View partner details',
                'assignable'  => true,
            ],
            Permission::EDIT_PARTNERS => [
                'description' => 'Edit partner details',
                'assignable'  => true,
            ],
            Permission::COMMISSION_CAPTURE => [
                'description' => 'Capture commission',
                'assignable'  => true,
            ],
            Permission::COMMISSION_PAYOUT => [
                'description' => 'Clear on hold flag of partner commission transactions which triggers settlement',
                'assignable'  => true,
            ],
            Permission::VIEW_COMMISSIONS => [
                'description' => 'View commission details of a partner',
                'assignable'  => true,
            ],
            Permission::PARTNER_AND_SUBMERCHANT_ACTIONS => [
                'description' => 'Partner and submerchant debug actions',
                'assignable'  => true,
            ],
            Permission::ADMIN_MANAGE_PARTNERS => [
                'description' => 'Back fill merchant applications of partner',
                'assignable'  => true,
            ],
            Permission::EDIT_ACTIVATE_PARTNER => [
                'description' => 'Edit partner activation status',
                'assignable'  => true,
                'workflow'    => true,
            ],
            Permission::PARTNER_ACTIONS                 => [
                'description' => 'To perform actions on partner activation',
                'assignable'  => true,
                'workflow'    => true,
            ],
            Permission::ASSIGN_PARTNER_ACTIVATION_REVIEWER => [
                'description' => 'Assign Reviews for Partner Activation Forms',
            ],
        ],

        PermissionCategory::MERCHANT_DETAIL => [
            Permission::VIEW_MERCHANT_BALANCE                  => [
                'description' => 'View merchant balance in merchant details',
                'assignable'  => true,
            ],
            Permission::VIEW_MERCHANT_FEATURES                 => '',
            Permission::FEATURE_ONBOARDING_FETCH_ALL_RESPONSES => '',
            Permission::VIEW_MERCHANT_BANKS                    => '',
            Permission::VIEW_NETWORKS                          => '',
            Permission::VIEW_MERCHANT_BANK_ACCOUNTS         => [
                'assignable' => true,
            ],
            Permission::MERCHANT_ACTIVATION_REVIEWERS      => [
                'assignable' => true,
            ],
            Permission::VIEW_MERCHANT_DOCUMENT             => [
                'assignable' => true,
            ],
            Permission::ADMIN_UPLOAD_MERCHANT_DOCUMENT     => [
                'assignable' => true,
            ],
            Permission::FEATURE_ONBOARDING_FETCH_ALL_RESPONSES => '',
            Permission::VIEW_MERCHANT_LOGIN                 => '',
            Permission::VIEW_ACTIVITY                       => '',
            Permission::VIEW_MERCHANT_HDFC_EXCEL            => '',
            Permission::VIEW_BENEFICIARY_FILE               => '',
            Permission::VIEW_MERCHANT_SCREENSHOT            => [
                'assignable' => true,
            ],
            Permission::VIEW_ALL_MERCHANT_AGGREGATIONS      => '',
            Permission::VIEW_MERCHANT_AGGREGATIONS          => '',
            Permission::VIEW_MERCHANT_TAGS                  => '',
            Permission::VIEW_MERCHANT_CAPITAL_TAGS          => '',
            Permission::MERCHANT_SEND_ACTIVATION_MAIL       => [
                'assignable' => true,
            ],
            Permission::SET_PRICING_RULES                   => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::DELETE_EMI_PLAN                     => '',
            Permission::CREATE_EMI_PLAN                     => '',
            Permission::CREATE_MERCHANT_LOCK                => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::CREATE_MERCHANT_UNLOCK              => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT                       => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::ONBOARDING_AND_ACTIVATIONS_EDIT     => [
                'assignable' => true,
            ],
            Permission::SEND_REQUEST_ACTIVATION_DOCUMENTS_NOTIFICATION => [
                'assignable' => true
            ],
            Permission::EDIT_MERCHANT_TAGS                  => '',
            Permission::EDIT_MERCHANT_FEATURES              => [
                'assignable' => true,
            ],
            Permission::MANAGE_BULK_FEATURE_MAPPING         => [
                'assignable' => true,
            ],
            Permission::MANAGE_BULK_MERCHANT_TAGGING        => [
                'assignable' => true,
            ],
            Permission::MERCHANT_BENEFICIARY_UPLOAD         => [
                'assignable' => true,
            ],
            Permission::EDIT_MERCHANT_FEATURES              => '',
            Permission::EDIT_MERCHANT_BANK_DETAIL           => '',

            Permission::EDIT_MERCHANT_GSTIN_DETAIL          => [
                'workflow'    => true,
            ],
            Permission::UPDATE_MERCHANT_GSTIN_DETAIL        => [
                'workflow'    => true,
            ],

            Permission::EDIT_MERCHANT_WEBSITE_DETAIL        => '',

            Permission::UPDATE_MERCHANT_WEBSITE            => [
                'assignable'  => true,
                'workflow'    => true,
            ],

            Permission::DECRYPT_MERCHANT_WEBSITE_COMMENT   => [
                'assignable'  => true,
                'workflow'    => false,
            ],

            Permission::ADD_ADDITIONAL_WEBSITE            => [
                'assignable'  => true,
                'workflow'    => true,
            ],

            Permission::EDIT_IIN_RULE                       => '',
            Permission::EDIT_IIN_RULE_BULK                  => [
                'description' => 'Bulk Edit IIN Rule',
                'assignable'  => true,
                'workflow'    => true,
            ],
            Permission::EDIT_ACTIVATE_MERCHANT              => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_KEY_ACCESS            => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_ENABLE_LIVE           => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_DISABLE_LIVE          => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_ARCHIVE               => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_UNARCHIVE             => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_SUSPEND               => [
                'assignable' => true,
            ],
            Permission::EDIT_MERCHANT_UNSUSPEND             => [
                'assignable' => true,
            ],
            Permission::EDIT_MERCHANT_RISK_THRESHOLD        => [
                'assignable' => true,
            ],
            Permission::EDIT_MERCHANT_FORCE_ACTIVATION      => [
                'assignable' => true,
                'workflow'   => true,
            ],
            Permission::EDIT_MERCHANT_METHODS               => '',
            Permission::EDIT_MERCHANT_RISK_ATTRIBUTES       => '',
            Permission::EDIT_MERCHANT_ENABLE_INTERNATIONAL  => '',
            Permission::EDIT_MERCHANT_DISABLE_INTERNATIONAL => '',
            Permission::EDIT_MERCHANT_TERMINAL              => '',
            Permission::VIEW_TERMINAL                       =>  [
                'description' => 'Ability to view a terminal',
                'assignable' => true,
            ],
            Permission::VIEW_TERMINAL_EXTERNAL_ORG          =>  [
                'description' => 'Ability to view a terminal from outside the org',
                'assignable' => true,
            ],
            Permission::INCREASE_TRANSACTION_LIMIT          => [
                'assignable'  => true,
                'workflow'    => true,
            ],
            Permission::INCREASE_INTERNATIONAL_TRANSACTION_LIMIT  => [
                'assignable'  => true,
                'workflow'    => true,
            ],
            Permission::ENABLE_NON_3DS_PROCESSING  => [
                'assignable'  => true,
                'workflow'    => true,
            ],
            Permission::TOGGLE_TERMINAL                     => [
                'description' => 'Ability to enable or disable a terminal',
                'assignable' => true,
                'workflow'    => true
            ],
            Permission::CHECK_TERMINAL_SECRET               => [
                'description' => 'Ability to check terminal secrets and passwords',
                'assignable'  => true,
            ],
            Permission::SUBMIT_ONE_CA => [
                'description' => 'Ability to submit one ca form',
                'assignable'  => true,
            ],
            Permission::TERMINAL_MANAGE_MERCHANT            => [
                'description' => 'Ability to add or remove sub merchants to a terminal',
                'assignable' => true,
                'workflow'    => true
            ],
            Permission::EDIT_MERCHANT_PRICING               => '',
            Permission::EDIT_MERCHANT_COMMENTS              => '',
            Permission::VIEW_MERCHANT_COMPANY_INFO          => [
                'assignable' => true,
            ],
            Permission::VIEW_MERCHANT_CREDITS_LOG           => '',
            Permission::ADD_MERCHANT_CREDITS                => [
                'assignable' => true,
            ],
            Permission::EDIT_MERCHANT_CREDITS => [
                'description' => 'Ability to merchant edit credits',
                'assignable'  => true,
            ],
            Permission::DELETE_MERCHANT_CREDITS             => '',
            Permission::EDIT_MERCHANT_SCREENSHOT            => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::VIEW_PAYMENT_VERIFY                 => '',
            Permission::FETCH_PAYMENT_CONFIG_ADMIN          => '',
            Permission::EDIT_VERIFY_PAYMENTS                => '',
            Permission::EDIT_AUTHORIZED_FAILED_PAYMENT      => '',
            Permission::VIEW_REFUND_PAYMENTS                => '',
            Permission::EDIT_AUTHORIZED_REFUND_PAYMENT      => '',
            Permission::PAYMENTS_UPDATE_REFUND_AT           => '',
            Permission::AUTHORIZE_PAYMENT                   => [
                'assignable'  => true,
            ],
            Permission::FETCH_PAYMENT_CONFIG_ADMIN          => [
                'assignable'  => true,
            ] ,
            Permission::VERIFY_PAYMENT                      => [
                'assignable'  => true,
            ],
            Permission::VERIFY_REFUND                       => [
                'assignable'  => true,
            ],
            Permission::RETRY_REFUND_FAILED                 => '',
            Permission::EDIT_REFUND                         => [
                'description' => 'edit_refund_permission',
                'assignable'  => true,
            ],
            Permission::UPDATE_SCROOGE_REFUND_REFERENCE1    => [
                'description' => 'update_scrooge_refund_reference1_permission',
                'assignable'  => true,
            ],
            Permission::BULK_RETRY_REFUNDS_VIA_FTA    => [
                'description' => 'bulk_retry_refunds_via_fta_permission',
                'assignable'  => true,
            ],
            Permission::REFRESH_SCROOGE_FTA_MODES_CACHE    => [
                'description' => 'refresh_scrooge_fta_modes_cache_permission',
                'assignable'  => true,
            ],
            Permission::UPDATE_REFUND_REFERENCE1    => [
                'description' => 'update_refund_reference1_permission',
                'assignable'  => true,
            ],
            Permission::UPDATE_PROCESSED_REFUNDS_STATUS    => [
                'description' => 'update_processed_refunds_status_permission',
                'assignable'  => true,
            ],
            Permission::REVERSE_FAILED_REFUNDS    => [
                'description' => 'reverse_failed_refunds_permission',
                'assignable'  => true,
            ],
            Permission::EDIT_INSTANT_REFUNDS_MODE_CONFIG    => [
                'description' => 'edit_instant_refunds_mode_config_permission',
                'assignable'  => true,
            ],
            Permission::EDIT_SCROOGE_REDIS_CONFIG                         => [
                'description' => 'edit_scrooge_redis_config_permission',
                'assignable'  => true,
            ],
            Permission::RETRY_REFUND                        => [
                'description' => 'Retry refunds',
                'assignable'  => true,
            ],
            Permission::RETRY_REFUNDS_WITHOUT_VERIFY        => [
                'description' => 'Retry scrooge refunds without verify',
                'assignable'  => true,
            ],
            Permission::RETRY_REFUNDS_WITH_APPENDED_ID      => [
                'description' => 'Retry scrooge refunds after appending to the refund ID',
                'assignable'  => true,
            ],
            Permission::GENERATE_REFUND_EXCEL               => [
                'assignable'  => true,
            ],
            Permission::GENERATE_EMI_EXCEL                  => [
                'assignable'  => true,
            ],
            Permission::EDIT_PAYMENT_REFUND                 => '',
            Permission::EDIT_PAYMENT_CAPTURE                => '',
            Permission::EDIT_MERCHANT_CONFIRM               => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::CREATE_BENEFICIARY_FILE             => '',
            Permission::CREATE_NETBANKING_REFUND            => '',
            Permission::CREATE_EMI_FILES                    => '',
            Permission::MANAGE_EMI_PLANS                    => [
                'assignable' => true,
            ],
            Permission::CREATE_SETTLEMENT_INITIATE          => '',
            Permission::GET_IRCTC_SETTLEMENT_FILE           => '',
            Permission::DELETE_TERMINAL                     => [
                'description' => 'Delete a Terminal',
                'workflow'   => true
            ],
            Permission::EDIT_TERMINAL                       => [
                'description' => 'Edits a Terminal',
                'workflow'   => true
            ],
            Permission::ENABLE_TERMINALS_BULK               => [
                'description' => 'Edits a Terminal',
                'workflow'   => true
            ],
            Permission::EDIT_TERMINAL_GOD_MODE              => [
                'description' => 'Edits a Terminal',
                'workflow'   => true
            ],
            Permission::PAYMENTS_BATCH_CREATE_TERMINALS_BULK => [
                'description' => 'create terminals in bulk',
                'assignable'  => true,
            ],
            Permission::INTERNAL_INSTRUMENT_CREATE_BULK => [
                'description' => 'create internal instrument in bulk',
                'assignable'  => true,
            ],
            Permission::PAYOUT_LINKS_ADMIN_BULK_CREATE => [
                'description' => 'create payout links in bulk',
                'assignable'  => true,
            ],
            Permission::TALLY_PAYOUT_BULK_CREATE => [
                'description' => 'create tally payouts in bulk',
                'assignable'  => true,
            ],
            Permission::CAPTURE_SETTING_BATCH_UPLOAD        => [
                'description' => 'Permission to add capture settings in batch',
                'assignable'  => true
            ],
            Permission::CREATE_SETTLEMENTS_RECONCILE        => '',
            Permission::DEACTIVATE_PROMOTION                 => [
                'assignable' => true,
                'description'   => 'allows deactivating a promotion'
            ],
            Permission::CREATE_PROMOTION_COUPON             => '',
            Permission::CREATE_RECONCILIATE                 => '',
            Permission::BATCH_API_CALL                      => '',
            Permission::MERCHANT_RESTRICT                   => '',
            Permission::UPDATE_USER_CONTACT_MOBILE          => '',
            Permission::USER_ACCOUNT_LOCK_UNLOCK            => '',
            Permission::VIEW_ACTIVATION_FORM                => [
                'assignable' => true,
            ],
            Permission::RESET_WEBHOOK_DATA                => [
                'assignable' => true,
            ],
            Permission::EDIT_MERCHANT_LOCK_ACTIVATION       => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_UNLOCK_ACTIVATION     => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_HOLD_FUNDS            => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_RELEASE_FUNDS         => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_ENABLE_RECEIPT        => '',
            Permission::EDIT_MERCHANT_DISABLE_RECEIPT       => '',
            Permission::EDIT_MERCHANT_RECEIPT_EMAIL_EVENT   => '',
            Permission::EDIT_BULK_MERCHANT                  => '',
            Permission::ASSIGN_MERCHANT_TERMINAL            => [
                'description' => 'Change primary merchant of a terminal',
                'workflow'    => true
            ],
            Permission::CREATE_EXTERNAL_ORG_TERMINALS       => [
                'description'   => 'Create paysecure terminal',
                'assignable'    => true,
            ],
            Permission::EDIT_EXTERNAL_ORG_TERMINALS     => [
                'description'   => 'Edit paysecure terminal',
                'assignable'    => true,
            ],
            Permission::ASSIGN_MERCHANT_BANKS               => '',
            Permission::ADD_MERCHANT_ADJUSTMENT             => '',
            Permission::EDIT_MERCHANT_EMAIL                 => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::EDIT_MERCHANT_ADDITIONAL_EMAIL      => [
                'assignable' => true,
                'workflow'   => false,
            ],
            Permission::MERCHANT_AUTOFILL_FORM              => '',
            Permission::EDIT_MERCHANT_MARK_REFERRED         => '',
            Permission::VIEW_AS_ENTITY                      => '',
            Permission::VIEW_MERCHANT_REFERRER              => '',
            Permission::VIEW_MERCHANT_BALANCE_TEST          => [
                'assignable' => true,
            ],
            Permission::VIEW_MERCHANT_BALANCE_LIVE          => [
                'assignable' => true,
            ],
            Permission::ADD_RECONCILIATION_FILE             => '',
            Permission::UPLOAD_NACH_MIGRATION               => '',
            Permission::VERIFY_NACH_UPLOADS                 => '',
            Permission::ADD_MANUAL_RECONCILIATION_FILE      => [
                'description' => 'Upload manually prepared MIS file to mark txn reconciled (used by FinOps)',
                'assignable'  => true,
            ],
            Permission::GET_RECONCILIATION                  => [
                'description' => 'Used for recon dashboard related routes',
                'assignable'  => true,
            ],
            Permission::ADD_SETTLEMENT_RECONCILIATION       => '',
            Permission::RETRY_SETTLEMENT                    => '',
            Permission::MERCHANT_INVOICE_EDIT               => '',
            Permission::MERCHANT_INVOICE_CONTROL            => '',
            Permission::SETTLEMENT_SERVICE_MERCHANT_CONFIG_EDIT => [
                'description' => 'This permission will be used to edit merchant config in new settlement service',
                'assignable'  => true,
            ],
            Permission::SEND_NEWSLETTER                     => '',
            Permission::TRIGGER_DUMMY_ERROR                 => '',
            Permission::MAKE_API_CALL                       => '',
            Permission::MAKE_ADMIN_API_CALL                 => [
                'description'   => 'Permission to make raw admin API calls',
                'assignable'    => true,
            ],
            Permission::VAULT_TOKEN_CREATE                 => [
                'description'   => 'Permission to create vault token API call',
                'assignable'    => true,
            ],
            Permission::SCHEDULE_CREATE                     => '',
            Permission::SCHEDULE_FETCH                      => '',
            Permission::SCHEDULE_FETCH_MULTIPLE             => '',
            Permission::SCHEDULE_DELETE                     => '',
            Permission::SCHEDULE_UPDATE                     => '',
            Permission::SCHEDULE_ASSIGN                     => '',
            Permission::SCHEDULE_ASSIGN_BULK                => '',
            Permission::PRICING_ASSIGN_BULK                 => '',
            Permission::METHODS_ASSIGN_BULK                 => '',
            Permission::SCHEDULE_MIGRATION                  => '',
            Permission::VIEW_ACTIONS                        => '',
            Permission::VIEW_MERCHANT_STATS                 => '',
            Permission::DELETE_MERCHANT_FEATURES            => [
                'description' => 'Delete a merchant feature',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::VIEW_MERCHANT_REPORT                => [
                'description' => 'View Merchant Reports',
            ],
            Permission::GENERATE_VP_INVOICE_ZIP                => [
                'description' => 'Generate bulk vendor payments invoices zip',
            ],
            Permission::VIEW_SPECIAL_MERCHANT_REPORT        => [
                'description' => 'View custom merchant reports for a few large clients',
            ],
            Permission::CREATE_MERCHANT_OFFER               => [
                'description' => 'Create offer for a merchant',
            ],
            Permission::EDIT_MERCHANT_OFFER                 => [
                'description' => 'Edit offer for a merchant',
            ],
            Permission::CREATE_QR_CODE => [
                'description' => 'Create VAs for a merchant',
            ],
            Permission::ASSIGN_MERCHANT_HANDLE              => 'Assign merchant handle',
            Permission::VIEW_MERCHANT_PRICING               => 'View Mercant Pricing Plan',
            Permission::VIEW_MERCHANT_ANALYTICS             => [
                'description' => 'View Merchant Analytics',
                'assignable'  => true,
                'workflow'    => false
            ],
            Permission::ASSIGN_MERCHANT_ACTIVATION_REVIEWER => [
                'description' => 'Assign Reviews for Merchant Activation Forms',
            ],
            Permission::CREATE_VIRTUAL_ACCOUNTS => [
                'description' => 'Create VAs for a merchant',
            ],
            Permission::CREATE_QR_CODE_CONFIG => [
                'description' => 'Create qr code configs for a merchant',
            ],
            Permission::BANK_TRANSFER_INSERT => [
                'description' => 'Insert bank transfers for failed payments',
                'assignable'  => true,
            ],
            Permission::USER_PASSWORD_RESET => [
                'description' => 'Reset user password on associated merchant page',
                'assignable'  => true,
            ],
            Permission::VIEW_OPERATIONS_REPORT => [
                'description' => 'View Operations Reports',
                'assignable'  => true,
            ],
            Permission::PAYMENT_CAPTURE_BULK => [
                'description' => 'Bulk Capture Payment',
                'assignable'  => true,
                'workflow'    => false,
            ],
            Permission::FIX_PAYMENT_ATTEMPTED_ORDERS => '',
            Permission::FIX_PAYMENT_AUTHORIZED_AT => '',
            Permission::FORCE_AUTHORIZE_PAYMENT => '',
            Permission::AUTOCAPTURE_PAYMENTS_MAIL => '',
            Permission::UPDATE_GEO_IP => '',
            Permission::MIGRATE_TOKENS_TO_GATEWAY_TOKENS => '',
            Permission::PAYMENT_AUTHORIZE_TIMEOUT => '',
            Permission::PAYMENT_CAPTURE_GATEWAY_MANUAL => '',
            Permission::PAYMENT_CAPTURE_VERIFY => '',
            Permission::CREATE_PAYMENT_CONFIG => '',
            Permission::DELETE_PAYMENT_CONFIG =>'',
            Permission::UPDATE_PAYMENT_CONFIG => '',
            Permission::REFUND_MULTIPLE_AUTHORIZE_PAYMENTS => '',
            Permission::MERCHANT_BALANCE_BULK_BACKFILL => '',
            Permission::DOWNLOAD_CREDIT_BUREAU_REPORTS => [
                'description' => 'Allow downloads of credit bureau reports of merchants',
                'assignable'  => true,
            ],
            Permission::EDIT_MERCHANT_INTERNATIONAL         => '',
            Permission::EDIT_MERCHANT_PG_INTERNATIONAL      => '',
            Permission::EDIT_MERCHANT_PROD_V2_INTERNATIONAL => '',
            Permission::TOGGLE_INTERNATIONAL_REVAMPED       => [
                'workflow'      => true,
                'assignable'    => true
            ],

            Permission::EDIT_MERCHANT_INTERNATIONAL_NEW  => '',
            Permission::TOGGLE_TRANSACTION_HOLD_STATUS => '',
            Permission::CURRENCY_FETCH_RATES => [
                'description' => 'Fetch exchange rates for a currency',
                'assignable'  => true
            ],
            Permission::MERCHANT_RISK_ALERT_FOH          => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::MERCHANT_RISK_ALERT_UPSERT_RULE          => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::MERCHANT_RISK_ALERT_DELETE_RULE          => [
                'assignable' => true,
                'workflow'   => true
            ],
            Permission::FETCH_MERCHANT_BALANCE_CONFIG => [
                'description' => 'Fetch merchant balance config',
                'assignable'  => true
            ],
            Permission::CREATE_MERCHANT_BALANCE_CONFIG => [
                'description' => 'Add merchant balance config',
                'assignable'  => true
            ],
            Permission::EDIT_MERCHANT_BALANCE_CONFIG => [
                'description' => 'Edit merchant balance config',
                'assignable'  => true
            ],
            Permission::UPDATE_ENTITY_BALANCE_ID => [
                'description' => 'Update Entity balance ID',
                'assignable'  => true
            ],
            Permission::CREATE_REWARD => [
                'description' => 'Create Reward for merchant',
                'assignable'  => true
            ],
            Permission::UPDATE_REWARD => [
                'description' => 'Update Reward for merchant',
                'assignable'  => true
            ],
            Permission::DELETE_REWARD => [
                'description' => 'Delete existing reward',
                'assignable'  => true
            ],
            Permission::GET_ADVERTISER_LOGO => [
                'description' => 'Get Advertiser Logo',
                'assignable'  => true
            ],
            Permission::REWARD_BATCH_MAIL => [
                'description' => 'Send Reward Mails to Merchants',
                'assignable'  => true
            ],
            Permission::ADMIN_FETCH_FUND_ACCOUNT_VALIDATION => [
                'assignable' => true,
            ],
            Permission::VIEW_SALESFORCE_OPPORTUNITY_DETAIL => [
                'assignable' => true,
            ],
            Permission::REFUNDS_BANK_FILE_UPLOAD => [
                'description' => 'Upload Banks Refund File',
                'assignable' => true,
            ],
            Permission::UPDATE_TRUSTED_BADGE_STATUS => [
                'description' => 'Blacklist or whitelist trusted badge for merchant',
                'assignable'  => true
            ],
            Permission::BULK_TOKENISATION => [
                'description' => 'Bulk tokenise saved cards',
                'assignable'  => true
            ],
            Permission::CREATE_CREDIT_BUREAU_REPORTS => [
                'assignable'  => true,
            ],
            Permission::MERCHANT_ACTIVATION_ARCHIVE => [
                'assignable'  => true,
            ],
            Permission::MERCHANT_BANK_ACCOUNT_DETAIL_CREATE => [
                'assignable'  => true,
            ],
            Permission::MERCHANT_BANK_ACCOUNT_DETAIL_UPDATE => [
                'assignable'  => true,
            ],
            Permission::MERCHANT_SETTLEMENT_BANK_ACCOUNT_CREATE => [
                'assignable'  => true,
            ],
            Permission::MERCHANT_SETTLEMENT_BANK_ACCOUNT_UPDATE => [
                'assignable'  => true,
            ],

            Permission::MERCHANT_SETTLEMENT_BANK_ACCOUNT_DELETE     => [
                'assignable'  => true,
            ],
            Permission::MERCHANT_BANK_ACCOUNT_EDIT     => [
                'assignable'  => true,
            ],
            Permission::MERCHANT_BANK_ACCOUNT_TEST_GENERATE     => [
                'assignable'  => true,
            ],
            Permission::MERCHANT_BANK_ACCOUNT_UPDATE     => [
                'assignable'  => true,
            ],
            Permission::PAYMENTS_REFUND_EDIT_BULK   => [
                'assignable'  => true,
            ],
            Permission::PAYMENTS_REFUND_FAILED_VERIFY_BULK   => [
                'assignable' => true,
            ],
            Permission::PAYMENTS_REFUND_SCROOGE_CREATE   => [
                'assignable' => true,
            ],
            Permission::PAYMENTS_REFUND_SCROOGE_CREATE_BULK   => [
                'assignable' => true,
            ],
            Permission::MERCHANT_BANK_ACCOUNT_STATUS_CHANGE   => [
                'assignable' => true,
            ],
            Permission::PAYMENTS_TERMINAL_FETCH_BY_ID   => [
                'assignable' => true,
            ],
            Permission::PAYMENTS_TERMINAL_FETCH_MULTIPLE   => [
                'assignable' => true,
            ],
            Permission::PROXY_MERCHANT_GET_TERMINALS   => [
                'assignable' => true,
            ],
            Permission::BULK_REGENERATE_API_KEYS   => [
                'assignable'  => true,
                'workflow'    => false,
            ],
            Permission::MERCHANT_CLARIFICATION_ON_WORKFLOW  => [
                'description' => 'to raise needs clarification on workflow',
                'assignable'  => true,
            ],
            Permission::UPDATE_MOBILE_NUMBER           => [
                'assignable'  => true,
                'workflow'    => true,
            ],
            Permission::CREATE_CYBER_HELPDESK_WORKFLOW => [
                'assignable'  => true,
                'workflow'    => true,
            ]
        ],

        PermissionCategory::SETTLEMENT => [
            Permission::SETTLEMENT_BULK_UPDATE        => '',
            Permission::CREATE_NODAL_ACCOUNT_TRANSFER => '',
            Permission::SETTLEMENT_RELEASE_HOLD_PAYMENT => [
                'description' => 'Settlement relese payments on hold',
                'assignable'  => true,
            ],
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
            Permission::MERCHANT_DISPUTE_BULK_EDIT  => [
                'assignable'  => true,
            ],
            Permission::BULK_DISPUTE_INGESTION_FOR_BANK => [
                'assignable'  => true,
            ],
            Permission::DISPUTES_DCS_CONFIG_GET =>[
                'assignable'  => true,
            ],
            Permission::DISPUTES_DCS_CONFIG_UPDATE =>[
                'assignable'  => true,
            ],
        ],

        PermissionCategory::PRICING => [
            Permission::VIEW_PRICING_LIST         => [
                'description' => 'view pricinglist',
            ],
            Permission::CREATE_PRICING_PLAN       => [
                'description' => 'create pricing plan',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::PAYMENTS_CREATE_BUY_PRICING_PLAN       => [
                'description' => 'create buy pricing plan',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::UPDATE_PRICING_PLAN       => [
                'description' => 'update pricing plan',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::PAYMENTS_UPDATE_BUY_PRICING_PLAN       => [
                'description' => 'update buy pricing plan',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::DELETE_PRICING_PLAN_RULES => [
                'description' => 'delete pricing plan rules',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::CAPITAL_SETTLEMENT_ONDEMAND_PRICING => [
                'assignable'  => true,
            ],

            Permission::MERCHANT_PRICING_BULK_CREATE => [
                'assignable'  => true,
            ],
            Permission::PAYMENTS_BUY_PRICING_PLAN_RULE_ADD   => [
                'assignable'  => true,
            ],
            Permission::PAYMENTS_BUY_PRICING_PLAN_RULE_FORCE_DELETE   => [
                'assignable'  => true,
            ],

            Permission::MERCHANT_PRICING_PLAN_RULE_ADD => [
                'assignable'  => true,
            ],
            Permission::MERCHANT_PRICING_PLAN_RULE_DELETE => [
                'assignable'  => true,
            ],
        ],

        PermissionCategory::ENTITY   => [
            Permission::VIEW_ALL_ENTITY => [
                'description' => 'view all entity',
            ],
            Permission::VIEW_SCROOGE_REFUNDS => [
                'description' => 'view scrooge refunds dashboard',
                'assignable'  => true,
            ],
            Permission::EXTERNAL_ADMIN_VIEW_ALL_ENTITY => [
                'description'  => 'View all entities but with restriction on entity type on attributes.
                                   To be assigned to external admin-roles only',
                'assignable'   => true,
            ],
            Permission::SYNC_ENTITY_BY_ID => [
                'assignable'  => true,
                'workflow'    => true
            ],
        ],

        // UAM

        // ORG
        PermissionCategory::ORG      => [
            Permission::VIEW_ALL_ORG => [
                'description' => 'view all org',
            ],
            Permission::VIEW_ORG     => [
                'description' => 'view all org detail',
            ],
            Permission::CREATE_ORG   => [
                'description' => 'create org',
            ],
            Permission::EDIT_ORG     => [
                'description' => 'Edit org',
            ],
            Permission::DELETE_ORG   => [
                'description' => 'delete org',
            ],
        ],

        // Workflow
        PermissionCategory::WORKFLOW => [
            Permission::VIEW_WORKFLOW          => [
                'description' => 'View Workflows',
                'assignable'  => true,
            ],
            Permission::EDIT_WORKFLOW          => [
                'description' => 'edit workflow',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::DELETE_WORKFLOW        => [
                'description' => 'delete workflow',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::VIEW_ALL_WORKFLOW      => [
                'description' => 'view all workflow',
                'assignable'  => true,
            ],
            Permission::CREATE_WORKFLOW        => [
                'description' => 'create workflow',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::VIEW_WORKFLOW_REQUESTS => [
                'description' => 'View Workflow requests',
                'assignable'  => true,
            ],
            Permission::EDIT_ACTION => [
                'description' => 'Edit Action requests',
                'assignable'  => true,
            ],
            Permission::WFS_CONFIG_CREATE => [
                'description' => 'Config create requests',
                'assignable'  => true,
            ],
        ],

        // Roles
        PermissionCategory::ROLE     => [
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
        PermissionCategory::GROUP    => [
            Permission::VIEW_ALL_GROUP           => [
                'description' => 'view_all_group',
                'assignable'  => true,
            ],
            Permission::VIEW_GROUP               => [
                'description' => 'view_group',
                'assignable'  => true,
            ],
            Permission::CREATE_GROUP             => [
                'description' => 'create_group',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::EDIT_GROUP               => [
                'description' => 'create_group',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::DELETE_GROUP             => [
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
        PermissionCategory::ADMIN    => [
            Permission::VIEW_ALL_ADMIN => [
                'description' => 'view_all_admin',
                'assignable'  => true,
            ],
            Permission::VIEW_ADMIN     => [
                'description' => 'view_admin',
                'assignable'  => true,
            ],
            Permission::CREATE_ADMIN   => [
                'description' => 'create_admin',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::EDIT_ADMIN     => [
                'description' => 'edit admin',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::DELETE_ADMIN   => [
                'description' => 'delete admin',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::ADMIN_GET_APP_AUTH => [
                'assignable'  => true
            ],
            Permission::ADMIN_LEAD_VERIFY => [
                'assignable'  => true
            ],
            Permission::VIEW_ADMIN_REPORTS => [
                'description' => 'View Admin Reports',
                'assignable'  => true,
            ]
        ],

        PermissionCategory::ACTION     => [
            Permission::DB_META_QUERY                => '',
            Permission::OAUTH_SYNC_MERCHANT_MAP      => '',
            Permission::DOWNLOAD_NON_MERCHANT_REPORT => [
                'description' => 'download non-merchant reports',
                'assignable'  => true,
            ],
            Permission::REPORTING_DEVELOPER          => [
                'description' => 'Allow access to admin APIs for reporting On Demand',
                'assignable'  => true,
            ],
            Permission::ES_WRITE_OPERATION           => [
                'description' => 'Perform write operations on Elasticsearch',
            ],
            Permission::ADMIN_FILE_UPLOAD            => [
                'description' => 'Upload a file',
            ],
            Permission::EDIT_THROTTLE_SETTINGS       => [
                'description'  => 'Edit throttle settings',
            ],
            Permission::VIEW_THROTTLE_SETTINGS       => [
                'description'  => 'View throttle settings',
            ],
            Permission::STORK_WRITE_OPERATION        => [
                'description' => 'Perform write operations around stork integration e.g. webhook migrations etc',
                'assignable'  => true,
            ],
            Permission::STORK_SUPPORT_OPERATION      => [
                'description' => 'Perform various support operation e.g. processing bulk webhook events via csv etc',
                'assignable'  => true,
            ],
            Permission::STORK_WEBHOOK_REPLAY      => [
                'description' => 'List and replay webhooks events',
                'assignable'  => true,
            ],
            Permission::EDGE_WRITE_OPERATION         => [
                'description' => 'Allows performing various write operations around dual writes and migrations for credcase, and edge',
                'assignable'  => true,
            ],
            Permission::DEVELOPERS_READ              => [
                'description' => 'Perform general purpose read operations e.g. query cache stats, elasticsearch meta etc',
                'assignable'  => true,
            ],
            Permission::LEDGER_SERVICE_ACTIONS => [
                'description' => 'Allows performing various create/update operations on Ledger service such as account create etc',
                'assignable'  => true,
            ],
            Permission::LEDGER_CLIENT_ACTIONS => [
                'description' => 'Allows limited write operations on Ledger service which should be accessible to clients.',
                'assignable'  => true,
            ],
            Permission::LEDGER_VIEW_DASHBOARD => [
                'description' => 'Allows read operation on Ledger Dashboard to fetch accounts etc',
                'assignable'  => true,
            ],
            Permission::ENABLE_DOWNTIME_NOTIFICATION_X_DASHBOARD => [
                'description' => 'To be used by X Ops Team to create notifications on X Dashboard',
                'assignable'  => true,
            ],
            Permission::MANAGE_CAMPAIGNHQ_OPERATIONS => [
                'description' => 'Allows managing Campaigns on CampaignHQ Dashboard',
                'assignable'  => true,
            ],
            Permission::ADMIN_BULK_ASSIGN_ROLE => [
                'description' => 'Allows performing bulk role assignment on the admin users',
                'assignable'  => true,
            ],
            Permission::MAGIC_RTO_CONFIGS_VIEW => [
                'description' => 'Allows viewing magic rto configs',
                'assignable'  => true,
            ],
            Permission::MAGIC_RTO_CONFIGS_EDIT => [
                'description' => 'Allows updating magic rto configs',
                'assignable'  => true,
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
            Permission::REMINDER_OPERATION  => [
                'description' => 'reminder_operation',
            ],
        ],

        PermissionCategory::AUDIT_LOG  => [
            Permission::VIEW_AUDITLOG => [
                'description' => 'view_auditlog',
                'assignable'  => true,
            ],
        ],

        // Invitations
        PermissionCategory::INVITATION => [
            Permission::CREATE_MERCHANT_INVITE => [
                'description' => 'create_merchant_invite',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::EDIT_MERCHANT_INVITE   => [
                'description' => 'edit_merchant_invite',
                'assignable'  => true,
                'workflow'    => true
            ],
            Permission::VIEW_MERCHANT_INVITE   => [
                'description' => 'view_merchant_invite',
                'assignable'  => true,
            ],
        ],

        PermissionCategory::USER_INVITATION => [
            Permission::VIEW_INVITATION => [
                'description' => 'view user invites',
                'assignable'  => true,
            ],
        ],

        PermissionCategory::GATEWAY => [
            Permission::CREATE_GATEWAY_FILE => [
                'description' => 'create_gateway_file',
                'assignable'  => true,
            ],
            Permission::MANAGE_IINS => [
                'description' => 'manage_iins',
                'assignable'  => true,
            ]
        ],

        PermissionCategory::GATEWAY_RULE  => [
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

        PermissionCategory::RULE_VISIBILITY => [
            Permission::RULE_VISIBILITY => [
                'description'   => 'Gives Access to Rule Visibility Dashboard',
            ],
        ],

        PermissionCategory::OPTIMIZER_SINGLE_RECON => [
            Permission::OPTIMIZER_SINGLE_RECON => [
                'description'   => 'Gives Access to trigger single recon API'
            ],
        ],

        PermissionCategory::GATEWAY_DOWNTIME  => [
            Permission::CREATE_GATEWAY_DOWNTIME => [
                'description' => 'Create Gateway Downtime',
            ],
            Permission::UPDATE_GATEWAY_DOWNTIME => [
                'description' => 'Update Gateway Downtime',
            ],
            Permission::VIEW_GATEWAY_DOWNTIME => [
                'description' => 'View Gateway Downtime',
            ],
        ],

        PermissionCategory::PAYOUT_DOWNTIME  => [
            Permission::MANAGE_PAYOUT_DOWNTIME => [
                'description' => 'Create, Update and Email Payout Downtime',
            ],
            Permission::VIEW_PAYOUT_DOWNTIME => [
                'description' => 'View Payout Downtime',
            ],
        ],

        PermissionCategory::FUND_LOADING_DOWNTIME  => [
            Permission::MANAGE_FUND_LOADING_DOWNTIME => [
                'description' => 'Create, Update and Notify Fund loading Downtime',
            ],
            Permission::VIEW_FUND_LOADING_DOWNTIME => [
                'description' => 'View Fund loading Downtime',
            ],
        ],

        PermissionCategory::BANKING_ACCOUNT_STATEMENT  => [
            Permission::INSERT_AND_UPDATE_BAS => [
                'description' => 'Insert missing banking account statements and update relevant entities',
            ],
            Permission::FETCH_MISSING_BAS => [
                'description' => 'Fetch missing banking account statements',
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

        // RZP White label emandate config
        PermissionCategory::EMANDATE_CONFIG => [
            Permission::MANAGE_EMANDATE_CONFIG   => [
                'description' => 'Edit EMANDATE Config',
                'assignable'  => true
            ]
        ],

        PermissionCategory::MAGIC_OPS_CONFIG => [
            Permission::MAGIC_OPS   => [
                'description' => 'To be used by Magic Ops Team to fetch/update details on admin dashboard',
                'assignable'  => true
            ]
        ],

        PermissionCategory::BATCH => [
            Permission::RETRY_BATCH        => [
                'description' => 'Retry batch processing',
                'assignable'  => true,
            ],
            Permission::ADMIN_BATCH_CREATE => [
                'description' => 'Create admin type batches',
                'assignable'  => true,
            ],
            Permission::ADJUSTMENT_BATCH_UPLOAD => [
                'description' => 'Upload batch file to create adjustments in bulk',
                'assignable'  => true,
            ],
            Permission::CREATE_BULK_ADJUSTMENT => [
                'description' => 'Upload batch file to create adjustments in bulk',
                'assignable'  => true,
                'workflow'    => true,
            ],
            Permission::REPORTING_BATCH_UPLOAD => [
                'description' => 'Upload batch file to create large reports from reporting',
                'assignable'  => true,
            ],
            Permission::CREDITS_BATCH_UPLOAD => [
                'description' => 'Upload batch file to assign credits to merchants in bulk',
                'assignable'  => true,
            ],
            Permission::IIN_BATCH_UPLOAD                    => [
                'description' => 'Used to update IINs in bulk',
                'assignable'  => true
            ],
            Permission::MPAN_BATCH_UPLOAD                   => [
                'description' => 'Upload mpan creation batch',
                'assignable'  => true
            ],
            Permission::EMANDATE_BATCH_UPLOAD => [
                'description' => 'Upload batch file to process e-mandate payments',
                'assignable'  => true,
            ],
            Permission::NACH_BATCH_UPLOAD => [
                'description' => 'Upload batch file to process nach payments',
                'assignable'  => true,
            ],
            Permission::ECOLLECT_ICICI_BATCH_UPLOAD => [
                'description' => 'Upload batch file to process ecollect_icici payments',
                'assignable'  => true,
            ],
            Permission::ECOLLECT_RBL_BATCH_UPLOAD => [
                'description' => 'Upload batch file to process ecollect_rbl payments',
                'assignable'  => true,
            ],
            Permission::ECOLLECT_YESBANK_BATCH_UPLOAD => [
                'description' => 'Upload batch file to process ecollect_yesbank payments',
                'assignable'  => true,
            ],
            Permission::VIRTUAL_BANK_ACCOUNT_BATCH_UPLOAD => [
                'description' => 'Upload batch file to process virtual_bank_account payments',
                'assignable'  => true,
            ],
            Permission::MERCHANT_CONFIG_INHERITANCE => [
                'description' => '',
                'assignable'  => true,
            ],
            Permission::MERCHANT_ONBOARDING => [
                'description' => '',
                'assignable'  => true,
            ],
            Permission::MERCHANT_STATUS_ACTIVATION => [
                'description' => '',
                'assignable'  => true,
            ],
            Permission::PRICING_RULE => [
                'description' => '',
                'assignable'  => true,
            ],
            Permission::MDR_ADJUSTMENTS => [
                'description' => '',
                'assignable'  => true,
            ],
            Permission::MERCHANT_ACTIVATION => [
                'description' => '',
                'assignable'  => true,
            ],
            Permission::INSTANT_ACTIVATION => [
                'description' => '',
                'assignable'  => true,
            ]
        ],

        PermissionCategory::SHIELD => [
            Permission::CREATE_SHIELD_RULE                  => 'Create shield rule',
            Permission::EDIT_SHIELD_RULE                    => 'Edit shield rule',
            Permission::DELETE_SHIELD_RULE                  => 'Delete shield rule',
            Permission::VIEW_SHIELD_LIST                    => 'View shield list',
            Permission::CREATE_SHIELD_LIST                  => 'Create shield list',
            Permission::DELETE_SHIELD_LIST                  => 'Delete shield list',
            Permission::ADD_SHIELD_LIST_ITEMS               => 'Add Shield List Items',
            Permission::PURGE_SHIELD_LIST_ITEMS             => 'Purge Shield List Items',
            Permission::DELETE_SHIELD_LIST_ITEM             => 'Delete Shield List Item',
            Permission::RETRIEVE_SHIELD_UI_SETTINGS         => 'Retrieve shield admin UI settings',
            Permission::VIEW_RISK_THRESHOLD_CONFIG          => 'View risk threshold config',
            Permission::CREATE_RISK_THRESHOLD_CONFIG        => 'Create risk threshold config',
            Permission::UPDATE_RISK_THRESHOLD_CONFIG        => 'Update risk threshold config',
            Permission::DELETE_RISK_THRESHOLD_CONFIG        => 'Delete risk threshold config',
            Permission::VIEW_MERCHANT_RISK_THRESHOLD        => 'View merchant risk threshold',
            Permission::CREATE_MERCHANT_RISK_THRESHOLD      => 'Create merchant risk threshold',
            Permission::UPDATE_MERCHANT_RISK_THRESHOLD      => 'Update merchant risk threshold',
            Permission::DELETE_MERCHANT_RISK_THRESHOLD      => 'Delete merchant risk threshold',
            Permission::BULK_UPDATE_MERCHANT_RISK_THRESHOLD => 'Bulk Update merchant risk threshold',
        ],

        PermissionCategory::REPORTING => [
            Permission::CREATE_SELF_SERVE_REPORT        => 'Create Self Serve reporting configs',
            Permission::GET_SELF_SERVE_REPORT           => 'View Self Serve reporting config',
            Permission::REPORT_CONFIG_FULL_OPERATIONS   => 'For creating/updating full reporting configs',
        ],

        PermissionCategory::SUBSCRIPTIONS => [
            Permission::MODIFY_SUBSCRIPTION_DATA => [
                'description' => 'Modify subscriptions form admin dashboard',
                'assignable'  => true
            ]
        ],

        PermissionCategory::ROUTE => [
            Permission::DEBUG_TRANSFERS_ROUTES => [
                'description' => 'Debug Transfers/Route from admin dashboard',
                'assignable'  => true
            ],
            Permission::DUMMY_ROUTE => [
                'description' => 'dummy route',
                'assignable'  => true
            ],
            Permission::PG_ROUTER_ORDER_SYNC => [
                'description' => 'Sync Order into PG Router',
                'assignable'  => true
            ]
        ],

        PermissionCategory::ROUTE => [
            Permission::BANK_TRANSFER_MODIFY_PAYER_ACCOUNT => [
                'description' => 'Bank transfer modify payer account',
                'assignable'  => true
            ],
            Permission::LINKED_ACCOUNT_REFERENCE_DATA_CREATE => [
                'description' => 'Create Linked Account Reference Data',
                'assignable'  => true
            ],
            Permission::LINKED_ACCOUNT_REFERENCE_DATA_UPDATE => [
                'description' => 'Update linked account reference data',
                'assignable'  => true,
            ],
            Permission::AMC_LINKED_ACCOUNT_CREATION => [
                'description' => 'Create AMC Linked Account for Mutual Fund Distributor Merchants',
                'assignable'  => true
            ],
        ],

        PermissionCategory::VIRTUAL_ACCOUNT => [
            Permission::DEBUG_VIRTUAL_ACCOUNT => [
                'description' => 'Debug virtual accounts from admin dashboard',
                'assignable'  => true
            ]
        ],

        PermissionCategory::SUBSCRIPTION_REGISTRATIONS => [
            Permission::TOKEN_REGISTRATION_ACTIONS => [
                'description' => 'Token registration authenticate and associate',
                'assignable'  => true
            ]
        ],

        PermissionCategory::NPS => [
            Permission::NPS_SURVEY => [
                'description' => 'To be used for creating or editing nps',
                'assignable'  => true,
            ],
        ],

        PermissionCategory::RBL_MID_OFFICE => [
            Permission::RBL_BANK_MID_OFFICE_EDIT_LEAD => [
                'description' => 'RBL Bank MID Office to edit CA Lead',
                'assignable'  => true,
            ],
            Permission::RBL_BANK_MID_OFFICE_MANAGE_LEAD => [
                'description' => 'RBL Bank MID Office to manage CA Lead',
                'assignable'  => true,
            ],
            Permission::RBL_BANK_MID_OFFICE_VIEW_LEAD => [
                'description' => 'RBL Bank MID Office to view CA Lead',
                'assignable'  => true,
            ],
        ],

        // RazorpayX
        PermissionCategory::RAZORPAYX_BANKING => [
            Permission::BANKING_UPDATE_ACCOUNT => [
                'description' => 'Updating banking account details of the merchant',
                'assignable'  => true,
            ],

            Permission::ASSIGN_BANKING_ACCOUNT_REVIEWER => [
                'description' => 'Adds reviewer to banking account',
                'assignable'  => true,
            ],

            Permission::CREATE_BANKING_VIRTUAL_ACCOUNTS => [
                'description' => 'Create Banking VAs for a merchant',
                'assignable'  => true,
            ],
            Permission::UPDATE_FREE_PAYOUTS_ATTRIBUTES => [
                'description' => 'Update free payout attributes',
                'assignable'  => true,
            ],
            Permission::FREE_PAYOUT_MIGRATION_TO_PS => [
                'description' => 'Free payout migration to payouts service',
                'assignable'  => true,
            ],
            Permission::VIEW_FREE_PAYOUTS_ATTRIBUTES => [
                'description' => 'View free payout attributes',
                'assignable'  => true,
            ],
            Permission::RX_ADMIN_ACTION_PERMISSION => [
                'description' => 'To be used to hit any route that needs tech admin route access.',
                'assignable'  => true,
            ],
            Permission::MOB_ADMIN => [
                'description' => 'To perform CRUD operations on kycBundle/productBundle in MOB',
                'assignable'  => true,
            ],
        ],

        PermissionCategory::RAZORPAYX_APPS => [
            Permission::TAX_PAYMENT_ADMIN_AUTH_EXECUTE => [
                'description' => 'Allow executing admin tax-payment apis',
                'assignable'  => true,
            ],
            Permission::PAYOUT_LINK_ADMIN_AUTH_EXECUTE => [
                'description' => 'Allow executing admin payout-link apis',
                'assignable'  => true,
            ],
        ],

        // Razorpay Capital Services
        PermissionCategory::RAZORPAY_CAPITAL => [
            Permission::LOANS_EDIT => [
                'description' => 'Allow access to capital-los service routes/actions from dashboard',
                'assignable'  => true,
            ],
             Permission::LOS_BASIC => [
                'description' => 'Allow access to capital-los service routes/actions from dashboard',
                'assignable'  => true,
             ],

            Permission::CAPITAL_LOS_CREATE_APPLICATION => [
                'description' => 'Allows access to create application on los from admin dashboard',
                'assignable'  => true,
            ],

            Permission::LOS_CARDS => [
                'description' => 'Allow access to los for cards',
                'assignable'  => true,
            ],

            Permission::CAPITAL_CARDS => [
                'description' => 'Allow access to capital-cards service from dashboard',
                'assignable'  => true,
            ],

            Permission::CAPITAL_CARDS_REWARDS_READ => [
                'description' => 'Allow access to capital-cards service reward related read APIs from dashboard',
                'assignable'  => true,
            ],

            Permission::CAPITAL_CARDS_REWARDS_EDIT => [
                'description' => 'Allow access to capital-cards service reward related write APIs from dashboard',
                'assignable'  => true,
            ],

            Permission::CAPITAL_RISK_DEVELOPER => [
                'description' => 'Allow all required accesses to capital risk developers',
                'assignable'  => true,
            ],

            Permission::CAPITAL_SCORECARD => [
                'description' => 'Allow access to capital-scorecard service from dashboard',
                'assignable'  => true,
            ],

            Permission::EDIT_SCORECARD_GOVERNOR_CONF => [
                'description' => 'Allow access to editing scorecard governor configuration',
                'assignable'  => true,
                'workflow'    => true,
            ],

            Permission::ONE_PAGER => [
                'description' => 'Allow access to capital-scorecard one pager data',
                'assignable'  => true,
            ],

            Permission::SCORECARD_EDIT => [
                'description' => 'Allow access to editing los application scorecard',
                'assignable'  => true,
            ],

            Permission::SCORECARD_VIEW => [
                'description' => 'Allow access to view scorecard entities on Admin dashboard UI',
                'assignable'  => true,
            ],

            Permission::DEDUPE_MATCH => [
                'description' => 'Allow access to generate dedupe in Capital Risk/ Dedupe Tab',
                'assignable'  => true,
            ],

            Permission::DEDUPE_VIEW => [
                'description' => 'Allow access to view dedupe in Capital Risk/ Dedupe Tab',
                'assignable'  => true,
            ],

            Permission::GENERATE_STP => [
                'description' => 'Allow access to trigger STP generation',
                'assignable'  => true,
            ],

            Permission::SCORECARD_CLI_CLD_VIEW => [
                'description' => 'Allow access to download CLI/CLD files from dashboard',
                'assignable'  => true,
            ],

            Permission::SCORECARD_CLI_CLD_REVIEW => [
                'description' => 'Allow access to download and upload CLI/CLD files from dashboard',
                'assignable'  => true,
            ],

            Permission::CAPITAL_CREATE_PAYMENT_LINK => [
                'description' => 'Allow capital to create payment link',
                'assignable'  => true,
            ],

            Permission::WALLETS => [
                'description' => 'Allow access to wallet service admin actions',
                'assignable'  => true,
            ],

            Permission::LOC => [
                'description' => 'Allow access to capital-loc service from dashboard',
                'assignable'  => true,
            ],

            Permission::LOC_CONFIG_EDIT => [
                'description' => 'Allow update access to capital-loc withdrawal_config, source_accounts and destination_accounts',
                'assignable'  => true,
            ],

            Permission::LOC_CONFIG_VIEW => [
                'description' => 'Allow viewing access to capital-loc withdrawal_config, source_accounts and destination_accounts',
                'assignable'  => true,
            ],

            Permission::LOC_WITHDRAWAL_EDIT => [
                'description' => 'Allow update access to capital-loc withdrawals and repayments',
                'assignable'  => true,
            ],

            Permission::LOC_WITHDRAWAL_VIEW => [
                'description' => 'Allow viewing access to capital-loc withdrawals and repayments',
                'assignable'  => true,
            ],

            Permission::FINANCIAL_DATA_SERVICE => [
                'description' => 'Allow access to Financial Data Service routes from dashboard',
                'assignable'  => true,
            ],
            Permission::CAPITAL_DEVELOPER => [
                'description' => 'Allow access to admin APIs for ES On Demand',
                'assignable'  => true,
            ],
            Permission::SETTLEMENT_ONDEMAND_TRANSFER_RETRY => [
                'description' => 'Allow access to ondemand transfer trigger API',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_APPLICATION_READ => [
                'description' => 'Allow access to view Capital LOS applications',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_APPLICATION_EDIT => [
                'description' => 'Allow access to make changes to Capital LOS applications',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_APPLICATION_CLOSURE => [
                'description' => 'Allow access to close/reject Capital LOS applications',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_APPLICATION_DOC_READ => [
                'description' => 'Allow access to view Capital LOS application documents',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_APPLICATION_DOC_EDIT => [
                'description' => 'Allow access to make changes to Capital LOS application documents',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_APPLICATION_DOC_REVIEW => [
                'description' => 'Allow access to accept/reject Capital LOS application documents',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_APPLICATION_SCORE_EDIT => [
                'description' => 'Allow access to add UW score for Capital LOS applications',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_APPLICATION_OFFER_READ => [
                'description' => 'Allow access to view Capital LOS application offers',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_APPLICATION_OFFER_EDIT => [
                'description' => 'Allow access to make changes to Capital LOS application offers',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_APPLICATION_DISBURSAL_READ => [
                'description' => 'Allow access to view Capital LOS application disbursals',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_APPLICATION_DISBURSAL_EDIT => [
                'description' => 'Allow access to make changes to Capital LOS application disbursals',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_APPLICATION_ESIGN_READ => [
                'description' => 'Allow access to view E-Sign flow for Capital LOS application',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_APPLICATION_ESIGN_EDIT => [
                'description' => 'Allow access to make changes in E-Sign flow in Capital LOS',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_D2C_READ => [
                'description' => 'Allow access to view D2C bureau reports in Capital LOS - detailed unmasked view',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_D2C_MASKED_READ => [
                'description' => 'Allow access to view D2C bureau reports in Capital LOS - masked summary',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_D2C_SUMMARY_READ => [
                'description' => 'Allow access to view D2C bureau reports in Capital LOS - unmasked summary',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_D2C_REPORT_READ => [
                'description' => 'Allow access to access D2C bureau report files',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_PRODUCTS_READ => [
                'description' => 'Allow access to view products in Capital LOS',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_PRODUCTS_EDIT => [
                'description' => 'Allow access to add/update products in Capital LOS',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_DOCUMENTS_READ => [
                'description' => 'Allow access to view master documents in Capital LOS',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_DOCUMENTS_EDIT => [
                'description' => 'Allow access to add/update master documents in Capital LOS',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_LENDER_READ => [
                'description' => 'Allow access to view lenders in Capital LOS',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_LENDER_EDIT => [
                'description' => 'Allow access to add/update lenders in Capital LOS',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_M2P_EDIT => [
                'description' => 'Allow access to make M2P integration related API calls',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_BUREAU_COMPLIANCE_EDIT => [
                'description' => 'Allow access to purge bureau related data points on behalf of the user',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_DEVELOPER => [
                'description' => 'Allow access to make specific admin API calls in Capital LOS(for Capital LOS developers)',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_SCORECARD_FILES_DELETE => [
                'description' => 'Allow access to delete scorecard files',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_D2C_INVITE_READ => [
                'description' => 'Allows an admin user to list existing invites for D2C bureau report pull',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_D2C_INVITE_EDIT => [
                'description' => 'Allows an admin user to request for an invite for D2C bureau report pull',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_APPLICATION_OFFER_EDIT_PRIVILEGED => [
                'description' => 'Allows an admin user to create an offer above a specified limit',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_APPLICATION_DOC_VERIFICATION_DETAILS_READ => [
                'description' => 'Allows an admin user to view document verification details in Capital LOS',
                'assignable'  => true,
            ],
            Permission::CAPITAL_LOS_APPLICATION_DOC_VERIFICATION_DETAILS_TRIGGER => [
                'description' => 'Allows an admin user to trigger document verification flows in Capital LOS',
                'assignable'  => true,
            ],
        ],

        PermissionCategory::PAYOUTS => [
            Permission::CREATE_PAYOUT => [
                'description' => 'Merchant can create a new payout',
                'assignable'  => false,
                'workflow'    => true,
            ],
            Permission::REJECT_PAYOUT => [
                'description' => 'Allows Admin to reject payout',
                'assignable'  => false,
                'workflow'    => true,
            ],
            Permission::PROCESS_FEE_RECOVERY => [
                'assignable' => true,
            ],
            Permission::CREATE_PROMOTION_EVENT => [
                'assignable' => true
            ],
            Permission::ASSIGN_FEE_RECOVERY_SCHEDULE => [
                'assignable' => true,
            ],
            Permission::PAYOUT_STATUS_UPDATE_MANUALLY => [
                'assignable' => true,
            ],
            Permission::BANKING_ACCOUNT_STATEMENT_RUN_MANUALLY => [
                'assignable' => true,
            ],
            Permission::UPDATE_BULK_PAYOUT_AMOUNT_TYPE => [
                'assignable' => true,
            ],
            Permission::ADMIN_SUB_VIRTUAL_ACCOUNT => [
                'assignable' => true,
            ],
            Permission::ADMIN_PROCESS_PENDING_BANK_TRANSFER => [
                'assignable' => true,
            ],
            Permission::MANUALLY_LINK_RBL_ACCOUNT_STATEMENT => [
                'assignable' => true,
            ],
            Permission::CREATE_LOW_BALANCE_CONFIG_ADMIN => [
                'assignable' => true,
            ],
            Permission::UPDATE_LOW_BALANCE_CONFIG_ADMIN => [
                'assignable' => true,
            ],
            Permission::RETRY_PAYOUTS_ON_SERVICE => [
                'assignable' => true,
            ],
            Permission::SET_MERCHANT_SLA_FOR_ON_HOLD_PAYOUTS => [
                'description' => 'Set merchant SLA for on-hold payouts',
                'assignable' => true
            ],
            Permission::MANAGE_PAYOUT_MODE_CONFIG => [
                'description' => 'Manage payout mode config for direct account payouts',
                'assignable' => true
            ],
        ],

        PermissionCategory::REDIS_CONFIG_PERIMSSIONS => [
            Permission::SET_RX_ACCOUNT_PREFIX => [
                'description'   => 'Allows to set RX account prefixes',
                'assignable'    => true,
            ],
            Permission::SET_MERCHANT_SLA_FOR_ON_HOLD_PAYOUTS => [
                'description'   => 'Allows to set merchant SLAs in Redis for on-hold payouts',
                'assinable'     => true
            ],
            Permission::SET_SHARED_ACCOUNT_ALLOWED_CHANNELS => [
                'description'   => 'Allows to set shared account channels',
                'assignable'    => true,
            ],
            Permission::EDIT_DESTINATION_MIDS_TO_WHITELIST_VA_TO_VA_PAYOUTS => [
                'description'   => 'Allows to edit whitelisted destination MIDs for VA to VA payouts',
                'assignable'    => true,
            ],
            Permission::SET_RX_GLOBALLY_WHITELISTED_PAYER_ACCOUNTS => [
                'description'   => 'Allows to set globally whitelisted payer accounts for fund loading for RazorpayX',
                'assignable'    => true,
            ],
            Permission::SET_PAYER_ACCOUNT_INVALID_REGEX => [
                'description'   => 'Allows to set invalid regexes that might come as part of Payer Account Number in Fund Loading',
                'assignable'    => true,
            ],
            Permission::USE_MASTER_DB_CONNECTION => [
                'description'   => 'Allows to use master db connection',
                'assignable'    => true,
            ],
            Permission::SET_PAYER_NAME_INVALID_REGEX => [
                'description'   => 'Allows to set invalid regexes that might come as part of Payer Name in Fund Loading',
                'assignable'    => true,
            ]
        ],

        PermissionCategory::UPI => [
            Permission::UPI_MANAGE_DATA => [
                'description'   => 'Manage UPI data in UPI/UPS entities',
                'assignable'    => true,
            ],

            Permission::UPI_MANAGE_PSPS => [
                'description'   => 'Manage UPI PSPs and configurations',
                'assignable'    => true,
            ],
        ],

        PermissionCategory::P2P => [
            Permission::P2P_MANAGE_MERCHANT => [
                'description'   => 'Allows to manage merchant setup',
                'assignable'    => true,
            ],
        ],

        PermissionCategory::RENDERING_PREFERENCES => [
            Permission::MANAGE_RENDERING_PREFERENCES => [
                'description' => 'Manage rendering preferences from admin dashboard',
                'assignable'  => true
            ]
        ],

        PermissionCategory::INSTRUMENT_REQUESTS => [
            Permission::VIEW_INTERNAL_INSTRUMENT_REQUEST => [
                'description'   => 'View single/bulk internal instrument requests',
                'assignable'    => true,
            ],
            Permission::UPDATE_KAM_INTERNAL_INSTRUMENT_REQUEST => [
                'description'   => 'Update single internal instrument requests on kam',
                'assignable'    => true,
            ],
            Permission::UPDATE_INTERNAL_INSTRUMENT_REQUEST => [
                'description'   => 'Update single/bulk internal instrument requests',
                'assignable'    => true,
            ],
            Permission::CANCEL_INTERNAL_INSTRUMENT_REQUEST => [
                'description'   => 'Cancel single/bulk internal instrument requests',
                'assignable'    => true,
            ],
            Permission::DELETE_INTERNAL_INSTRUMENT_REQUEST => [
                'description'   => 'Delete internal instrument requests',
                'assignable'    => true,
            ],
            Permission::VIEW_MERCHANT_INSTRUMENT_REQUEST => [
                'description'   => 'View single/bulk merchant instrument requests',
                'assignable'    => true,
            ],
            Permission::UPDATE_MERCHANT_INSTRUMENT_REQUEST => [
                'description'   => 'Update single/bulk merchant instrument requests',
                'assignable'    => true,
            ],
            Permission::SKIP_ACTIVATION_CHECK_WHILE_RAISING_MIR_FROM_KAM => [
                'description'   => 'Skip activation check for merchant instrument requests raised from KAM dashboard',
                'assignable'    => true,
            ],
            Permission::UPDATE_MERCHANT_INSTRUMENT => [
                'description'   => 'Update merchant instrument',
                'assignable'    => true,
            ],
            Permission::VIEW_IIR_DISCREPANCY    =>  [
                'description'   => 'View IIR Discrepancy',
                'assignable'    => true,
            ],
            Permission::CREATE_IIR_DISCREPANCY    =>  [
                'description'   => 'Create IIR Discrepancy',
                'assignable'    => true,
            ],
            Permission::CREATE_DISCREPANCY    =>  [
                'description'   => 'Create Static Discrepancy',
                'assignable'    => true,
            ],
            Permission::EDIT_DISCREPANCY    =>  [
                'description'   => 'Edit Static Discrepancy',
                'assignable'    => true,
            ],
            Permission::VIEW_IIR_TEMPLATE    =>  [
                'description'   => 'View IIR Template Mappings',
                'assignable'    => true,
            ],
            Permission::EDIT_IIR_TEMPLATE    =>  [
                'description'   => 'Update IIR Template Mappings',
                'assignable'    => true,
            ]
        ],

        PermissionCategory::GATEWAY_CREDENTIAL => [
            Permission::VIEW_GATEWAY_CREDENTIAL => [
                'description'   => 'View gateway level credentials',
                'assignable'    => true,
            ],
            Permission::CREATE_GATEWAY_CREDENTIAL => [
                'description'   => 'Create gateway level credentials',
                'assignable'    => true,
            ],
            Permission::DELETE_GATEWAY_CREDENTIAL => [
                'description'   => 'Delete gateway level credentials',
                'assignable'    => true,
            ],
        ],

        PermissionCategory::TERMINAL_TESTING => [
            Permission::MANAGE_TERMINAL_TESTING => [
                'description'   => 'Allow terminal testing from admin dashboard',
                'assignable'    => true,
            ],
            Permission::EXECUTE_TERMINAL_TEST => [
                'description'   => 'Trigger automated terminal testing',
                'assignable'    => true,
            ],
        ],

        PermissionCategory::FUND_ACCOUNT_VALIDATION => [
            Permission::BULK_PATCH_FUND_ACCOUNT_VALIDATION => [
                'description'   => 'Bulk patch FAV requests',
                'assignable'    => true,
            ],

            Permission::FUND_ACCOUNT_VALIDATION_ADMIN => [
                'description'   => 'Admin FAV requests',
                'assignable'    => true,
            ],
        ],

        PermissionCategory::IP_WHITELIST => [
            Permission::ADMIN_MERCHANT_IP_WHITELIST => [
                'description'   => 'Admin IP whitelist',
                'assignable'    => true,
            ],
        ],

        PermissionCategory::MERCHANT_NOTIFICATION_CONFIG => [
            Permission::MERCHANT_NOTIFICATION_CONFIG_ADMIN  => 'Merchant Notification Config Admin Permissions',
        ],

        PermissionCategory::PAYMENT_LINK_V2 => [
            Permission::PAYMENT_LINKS_V2_ADMIN => [
                'description' => 'Admin actions for payment links v2 service',
                'assignable'  => true
            ],
            Permission::PAYMENT_LINKS_OPS_BATCH_CANCEL => [
                'description' => 'Ops permission for payment links batch cancel',
                'assignable'  => true
            ]
        ],

        PermissionCategory::TRANSACTIONS => [
            Permission::MARK_TRANSACTIONS_POSTPAID => [
                'description' => 'Admin action for marking transactions postpaid',
                'assignable'  => true
            ],
            Permission::CREATE_TRANSACTION_FEE_BREAKUP => [
                'description' => 'Create transaction fee break up',
                'assignable'  => true
            ]
        ],

        PermissionCategory::BANKING_ACCOUNT_TPV_CONFIG => [
            Permission::CREATE_BANKING_ACCOUNT_TPV => [
                'description' => 'Create tpv',
                'assignable'  => true,
            ],
            Permission::EDIT_BANKING_ACCOUNT_TPV   => [
                'description' => 'Edit tpv',
                'assignable'  => true,
            ],
            Permission::VIEW_BANKING_ACCOUNT_TPV   => [
                'description' => 'View tpvs for a merchant',
                'assignable'  => true,
            ],
        ],

        PermissionCategory::INHERITANCE  => [
            Permission::MANAGE_INHERITANCE   => [
                'description'   => 'Manage merchant config inheritance(currently only inherits terminals)',
                'assignable'    => true,
            ],
        ],

        PermissionCategory::FRESHCHAT => [
            Permission::MANAGE_FRESHCHAT => [
                'description'   => 'Manage chat configurations like timings/day of week to enable chat',
                'assignable'    => true,
            ],
        ],

        PermissionCategory::RECON => [
            Permission::RECON_OPERATION => [
                'description'   => 'Manage access to recon dashboard on admin dashboard',
                'assignable'    => true,
            ],

            Permission::RECON_ADMIN_OPERATION => [
                'description'   => 'Manage access to recon actions on admin dashboard',
                'assignable'    => true,
            ],

            Permission::RECON_SERVICE_REQUEST => [
                'assignable'  => true,
            ],

            Permission::RECON_FILE_UPLOAD => [
                'assignable'  => true,
            ],
        ],

        PermissionCategory::FTS_DASHBOARD_ADMIN => [
            Permission::FTS_SOURCE_ACCOUNT_UPDATE => [
                'description'   => 'Update FTS source account details. This may also update banking account details creds depending upon the source of the request',
                'assignable'    => true,
            ],
            Permission::FTS_SOURCE_ACCOUNT_GRACEFUL_UPDATE => [
                'description'   => 'Add more creds to the FTS source account. This may also update baking account details creds depending upon the source of the request',
                'assignable'    => true,
            ],
        ],
        PermissionCategory::MERCHANT_BULK_UPDATE => [
            Permission::EDIT_MERCHANT_SUSPEND_BULK               => [
                'description' => 'Edit request for bulk suspend and unsuspend',
                'assignable'  => true,
                'workflow'    => true,
            ],
            Permission::EDIT_MERCHANT_TOGGLE_LIVE_BULK            => [
                'description' => 'Edit request for enable and disable live',
                'assignable'  => true,
                'workflow'    => true,
            ],
            Permission::EDIT_MERCHANT_HOLD_FUNDS_BULK             => [
                'description' => 'Edit request for bulk hold funds and release funds',
                'assignable'  => true,
                'workflow'    => true,
            ],
            Permission::EDIT_MERCHANT_DISABLE_INTERNATIONAL_BULK             => [
                'description' => 'Edit request for bulk International disablement',
                'assignable'  => true,
                'workflow'    => true,
            ],
            Permission::EDIT_MERCHANT_ENABLE_INTERNATIONAL_BULK             => [
                'description' => 'Edit request for bulk International disablement',
                'assignable'  => true,
                'workflow'    => true,
            ],
            Permission::EXECUTE_MERCHANT_SUSPEND_BULK               => [
                'description' => 'execute request for bulk suspend and unsuspend',
                'assignable'  => true,
                'workflow'    => true,
            ],
            Permission::EXECUTE_MERCHANT_TOGGLE_LIVE_BULK            => [
                'description' => 'execute request for enable and disable live',
                'assignable'  => true,
                'workflow'    => true,
            ],
            Permission::EXECUTE_MERCHANT_HOLD_FUNDS_BULK             => [
                'description' => 'Edit request for bulk hold funds and release funds',
                'assignable'  => true,
                'workflow'    => true,
            ],
            Permission::EXECUTE_MERCHANT_DISABLE_INTERNATIONAL_BULK             => [
                'description' => 'Execute request for bulk International disablement',
                'assignable'  => true,
                'workflow'    => true,
            ],
            Permission::EXECUTE_MERCHANT_ENABLE_INTERNATIONAL_BULK             => [
                'description' => 'Execute request for bulk International Enablement',
                'assignable'  => true,
                'workflow'    => true,
            ],
        ],

        PermissionCategory::COLLECTIONS => [
            Permission::CAPITAL_COLLECTIONS_ADMIN => [
                'assignable'  => true,
            ],
        ],

        PermissionCategory::COUPON => [
            Permission::PAYMENTS_PROMOTION_COUPON_APPLY => [
                'assignable'  => true,
            ],
            Permission::PAYMENTS_PROMOTION_COUPON_DELETE => [
                'assignable'  => true,
            ],
            Permission::PAYMENTS_PROMOTION_COUPON_UPDATE => [
                'assignable'  => true,
            ],
        ],

        PermissionCategory::CREDITS => [
            Permission::MERCHANT_ADD_CREDITS_BULK => [
                'assignable'  => true,
            ],
        ],

        PermissionCategory::EMAIL => [
            Permission::MERCHANT_EMAIL_ADDITIONAL_CREATE => [
                'assignable'  => true,
            ],
            Permission::MERCHANT_EMAIL_ADDITIONAL_DELETE => [
                'assignable'  => true,
            ],
        ],

        PermissionCategory::EVENT_PROMOTION => [
            Permission::PAYMENT_PROMOTION_EVENT_UPDATE => [
                'assignable'  => true,
            ],
        ],

        PermissionCategory::OFFER => [
            Permission::PAYMENTS_OFFER_BULK_CREATE => [
                'assignable'  => true,
            ],
            Permission::PAYMENTS_OFFER_BULK_DEACTIVATE => [
                'assignable'  => true,
            ],
        ],

        PermissionCategory::REFUND => [
            Permission::PAYMENTS_REFUNDS_RETRY_FUND_TRANSFERS_SCROOGE_SOURCE    => [
                'assignable'  => true,
            ],
            Permission::PAYMENTS_REFUNDS_RETRY_FUND_TRANSFERS_SCROOGE_CUSTOM    => [
                'assignable'  => true,
            ],
        ],

        PermissionCategory::NOCODE => [
            Permission::DEBUG_NOCODE_ROUTES => [
                'description' => 'To be used for debugging / data correction of payment pages / buttons / subscription buttons',
                'assignable'  => true,
            ],
        ],

        PermissionCategory::MERCHANT_FUND_MANAGEMENT => [
            Permission::EDIT_BALANCE_MANAGEMENT_CONFIG => [
                'description' => 'fetch/edit balance management config',
                'assignable'  => true,
            ],
        ],

        PermissionCategory::X_DASHBOARD_FE => [
            Permission::UPDATE_USER_ROLE => [
                'assignable'    => true,
            ],
            Permission::VIEW_PUBLIC_PROFILE => [
                'assignable'    => true,
            ],
            Permission::VIEW_MANAGE_TEAM => [
                'assignable'    => true,
            ],
            Permission::VIEW_DEVELOPER_CONTROLS => [
                'assignable'    => true,
            ],
            Permission::VIEW_BANKING => [
                'assignable'    => true,
            ],
            Permission::VIEW_BILLING => [
                'assignable'    => true,
            ],
            Permission::UPDATE_SECURITY_SETTINGS => [
                'assignable'    => true,
            ],
            Permission::UPDATE_BUSINESS_SETTINGS => [
                'assignable'    => true,
            ],
            Permission::EDIT_WORKFLOW => [
                'assignable'    => true,
            ],
            Permission::INTEGRATE_SHOPIFY_TOOL => [
                'assignable'    => true,
            ],
            Permission::VIEW_KYC => [
                'assignable'    => true,
            ],
            Permission::FILL_KYC => [
                'assignable'    => true,
            ],
            Permission::DOWNLOAD_REPORTING => [
                'assignable'    => true,
            ],
            Permission::CREATE_LOW_BALANCE_ALERTS => [
                'assignable'    => true,
            ],
            Permission::VIEW_PAYOUTS_REPORT => [
                'assignable'    => true,
            ],
            Permission::HAS_APP_ACCESS => [
                'assignable'    => true,
            ],
            Permission::VIEW_PRIVILEGES => [
                'assignable'    => true,
            ],
            Permission::CUSTOMER_SUPPORT_FULL_ACCESS => [
                'assignable'    => true,
            ],
        ],
    ],

    'workflows' => [
        'mock' => env('HEIMDALL_WORKFLOWS_MOCK', false),
    ],
];

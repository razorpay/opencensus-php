<?php

return [
    'experiments' => [

        // Add splitz experiment ids here.

        'ANNOUNCEMENT_RETENTION1_APRIL2021_SPLITZ' => env('ANNOUNCEMENT_RETENTION1_APRIL2021_SPLITZ',''),

        // developer csat survey experiment
        'ANNOUNCEMENT_DX_CSAT_APRIL2021_SPLITZ' => env('ANNOUNCEMENT_DX_CSAT_APRIL2021_SPLITZ', ''),

        // Partnership Survey
        'PARTNERSHIP_NPS' => env('PARTNERSHIP_NPS', ''),

        // partnership for Phantom
        'PARTNERSHIP_FOR_PHANTOM' => env('PARTNERSHIP_FOR_PHANTOM', ''),

        // partner onboarding resuming
        'SHOW_RESUME_ONBAORDING' => env('SHOW_RESUME_ONBAORDING', ''),

        'WHATSNEW_LAZY_EXPERIMENT_SPLITZ' => env('WHATSNEW_LAZY_EXPERIMENT_SPLITZ', ''),

        // Moonshine
        'RAZORPAYX_PAYROLL_EXPERIMENT_COHORT_0_SPLITZ' => env('RAZORPAYX_PAYROLL_EXPERIMENT_COHORT_0_SPLITZ', ''),
        'RAZORPAYX_PAYROLL_EXPERIMENT_COHORT_1_SPLITZ' => env('RAZORPAYX_PAYROLL_EXPERIMENT_COHORT_1_SPLITZ', ''),
        'RAZORPAYX_PAYROLL_EXPERIMENT_COHORT_2_SPLITZ' => env('RAZORPAYX_PAYROLL_EXPERIMENT_COHORT_2_SPLITZ', ''),
        'RAZORPAYX_PAYROLL_EXPERIMENT_SPLITZ' => env('RAZORPAYX_PAYROLL_EXPERIMENT_SPLITZ', ''),

        // partnership for RazorpayX
        'INDEPENDENT_PARTNER_KYC' => env('INDEPENDENT_PARTNER_KYC', ''),

        // pure platform switch for reseller partners
        'PURE_PLATFORM_SWITCH' => ENV('PURE_PLATFORM_SWITCH', ''),

        // Failure Analysis Splitz
        'FAILURE_ANALYSIS_TEXT_EXP_SPLITZ' => env('FAILURE_ANALYSIS_TEXT_EXP_SPLITZ', ''),
        'FAILURE_ANALYSIS_PAYMENT_COUNT_EXP_SPLITZ' => env('FAILURE_ANALYSIS_PAYMENT_COUNT_EXP_SPLITZ', ''),
        'FAILURE_ANALYSIS_ROLLOUT_EXP_SPLITZ' => env('FAILURE_ANALYSIS_ROLLOUT_EXP_SPLITZ', ''),
        'FAILURE_ANALYSIS_MTV_EXP_SPLITZ' => env('FAILURE_ANALYSIS_MTV_EXP_SPLITZ', ''),

        // EDX experiments
        'ZAPIER_ANNOUNCEMENT_SPLITZ' => env('ZAPIER_ANNOUNCEMENT_SPLITZ', ''),
        'AI_SENSY_BANNER_SPLITZ' => env('AI_SENSY_BANNER_SPLITZ', ''),
        'DEVELOPER_CONSOLE_SPLITZ' => env('DEVELOPER_CONSOLE_SPLITZ', ''),
        'DEVELOPER_CONSOLE_WEBHOOKS_TAB_SPLITZ' => env('DEVELOPER_CONSOLE_WEBHOOKS_TAB_SPLITZ', ''),

        // PP Zapier Banner
        'PP_ZAPIER_BANNER_SPLITZ' => env('PP_ZAPIER_BANNER_SPLITZ', ''),

        //Cross Border Payments Announcement
        'CROSS_BORDER_PAYMENTS_ANNOUNCEMENT' => env('CROSS_BORDER_PAYMENTS_ANNOUNCEMENT', ''),
        'SHOW_RAZORPAYX_WIDGET_EXP' => env('SHOW_RAZORPAYX_WIDGET_EXP', ''),

        //Nitro CC Announcement
        'CATALYST_FL_BANNER' => env('CATALYST_FL_BANNER', ''),
        'CATALYST_EF_BANNER' => env('CATALYST_EF_BANNER', ''),
        'CATALYST_G_BANNER' => env('CATALYST_G_BANNER', ''),
        'AB_BANNER_CAROUSEL' => env('AB_BANNER_CAROUSEL', ''),

        //2FA Mobile Signup
        'ENABLE_2FA_MOBILE_SIGNUP_EXP_SPLITZ' => env('ENABLE_2FA_MOBILE_SIGNUP_EXP_SPLITZ', ''),

        //Digilocker EKYC
        'ENABLE_DIGILOCKER_EKYC_EXP_SPLITZ' => env('ENABLE_DIGILOCKER_EKYC_EXP_SPLITZ', ''),

        // Show L1 form on Login
        'SHOW_L1_FORM_ON_LOGIN' => env('SHOW_L1_FORM_ON_LOGIN', ''),

        // Show Activation Form full view
        'SHOW_ACTIVATION_FORM_FULL_VIEW' => env('SHOW_ACTIVATION_FORM_FULL_VIEW', ''),

        // Cross Sell Subscriptions
        'CROSS_SELL_SUBSCRIPTIONS_EDUCATION_EXP_SPLITZ' => env('CROSS_SELL_SUBSCRIPTIONS_EDUCATION_EXP_SPLITZ', ''),
        'CROSS_SELL_SUBSCRIPTIONS_OTHER_BUSINESS_EXP_SPLITZ' => env('CROSS_SELL_SUBSCRIPTIONS_OTHER_BUSINESS_EXP_SPLITZ', ''),

        // instant activation video enable
        'INSTANT_ACTIVATION_VIDEO_ENABLE_EXP_SPLITZ' => env('INSTANT_ACTIVATION_VIDEO_ENABLE_EXP_SPLITZ', ''),
        //new NC flow
        'ENABLE_EASY_DASHBOARD_NC' => env('ENABLE_EASY_DASHBOARD_NC', ''),
        'PARTNERSHIPS_SUBMERCHANT_ONBOARDING_VIA_EASY'      => env('PARTNERSHIPS_SUBMERCHANT_ONBOARDING_VIA_EASY', ''),

        // unified login signup
        'UNIFIED_PG_REDIRECTION_ENABLED' => env('UNIFIED_PG_REDIRECTION_ENABLED'),

        'DASHBOARD_HOMEPAGE_REDIRECTION_ENABLED' => env('DASHBOARD_HOMEPAGE_REDIRECTION_ENABLED'),

        'USL_REDIRECTION_SKIP_FOR_OAUTH' => env('USL_REDIRECTION_SKIP_FOR_OAUTH'),

        'PG3_V1_ENABLED'                 => env('PG3_V1_ENABLED'),

        // Onboarding all as resellers
        'PARTNERSHIP_ONBOARD_RESELLERS' => env('PARTNERSHIP_ONBOARD_RESELLERS', ''),
        'EASY_ONBOARDING_REDIRECT'      => env('EASY_ONBOARDING_REDIRECT', ''),

        'WEBSITE_COMPLIANCE_MODAL_EXP'      => env('WEBSITE_COMPLIANCE_MODAL_EXP', ''),
        'WEBSITE_COMPLIANCE_FLOW_EXP'      => env('WEBSITE_COMPLIANCE_FLOW_EXP', ''),

        // Cash Advance sidebar link position
        'CASH_ADVANCE_SIDEBAR_POSITION' => env('CASH_ADVANCE_SIDEBAR_POSITION', ''),

        // show invoices for current FY
        'INVOICE_CURRENT_FY' => env('INVOICE_CURRENT_FY', ''),

        //migrating apis to care ,this change is for adding reply api migration
        'ADD_REPLY_MIGRATION' => env('ADD_REPLY_MIGRATION', ''),

        // API Keys Page Revamp
        'API_KEYS_REVAMP' => env('API_KEYS_REVAMP', ''),

        // enable product led onboarding
        'PRODUCT_LED_ONBOARDING' => env('PRODUCT_LED_ONBOARDING', ''),

        // Account Settings Revamp
        'ACCOUNT_SETTINGS_REVAMP'  => env('ACCOUNT_SETTINGS_REVAMP', ''),

        // Universal Search V1
        'UNIVERSAL_SEARCH_ENABLED' => env('UNIVERSAL_SEARCH_ENABLED', ''),

         // Bank Account update Revamp
        'BANK_ACCOUNT_UPDATE_REVAMP'  => env('BANK_ACCOUNT_UPDATE_REVAMP', ''),

        // Contact Details Revamp
        'CONTACT_DETAILS_REVAMP' => env('CONTACT_DETAILS_REVAMP', ''),

        // Update User Name
        'USER_NAME_UPDATE' => env('USER_NAME_UPDATE', ''),

        // Settlement Revamp
        'SETTLEMENT_V3_REVAMP' => env('SETTLEMENT_V3_REVAMP', ''),

         // Payment Handle Onboarding
         'PAYMENT_HANDLE_ONBOARDING'  => env('PAYMENT_HANDLE_ONBOARDING', ''),

        // payroll wdiget on dashboard
        'SHOW_PAYROLL_WIDGET_EXP' => env('SHOW_PAYROLL_WIDGET_EXP', ''),

         // International enablement Revamp
        'INTERNATIONAL_ENABLEMENT_REVAMP'  => env('INTERNATIONAL_ENABLEMENT_REVAMP', ''),

        // get ticket api migrated to care service
        'GET_TICKET_MIGRATION' => env('GET_TICKET_MIGRATION', ''),

        // fetch all ticket api migrated to care service
        'FETCH_TICKETS_MIGRATION' => env('FETCH_TICKETS_MIGRATION', ''),

        // affordability widget on dashboard
        'SHOW_AFFORDABILITY_WIDGET_EXP' => env('SHOW_AFFORDABILITY_WIDGET_EXP', ''),
        'SHOW_AFF_WIDGET_SHOPIFY_WAIT_LIST' => env('SHOW_AFF_WIDGET_SHOPIFY_WAIT_LIST', ''),
        'SHOW_AFF_WIDGET_WOOC_WAIT_LIST' => env('SHOW_AFF_WIDGET_WOOC_WAIT_LIST', ''),

        // Partnership for Capital
        'PARTNERSHIP_CAPITAL' => env('PARTNERSHIP_CAPITAL', ''),

        // Partnership for Marketplace
        'ROUTE_PARTNERSHIPS' => env('ROUTE_PARTNERSHIPS', ''),

        // Revoke Application oauth
        'REVOKE_APPLICATION' => env('REVOKE_APPLICATION', ''),

        // Bundle Pricing
        'BUNDLE_PRICING' => env('BUNDLE_PRICING', ''),

        // settlement dashboard visibility
        'SETTLEMENT_DASHBOARD_VISIBILITY' => env('SETTLEMENT_DASHBOARD_VISIBILITY', ''),

        // FTUX for Onboarding
        'ONBOARDING_FTUX' => env('ONBOARDING_FTUX', ''),

        // FTUX  V2 for Onboarding
        'ONBOARDING_FTUX_V2' => env('ONBOARDING_FTUX_V2', ''),

        // Disable easy onboarding redirection for banking origin requests
        'DISABLE_EASY_REDIRECTION_FOR_BANKING' => env('DISABLE_EASY_REDIRECTION_FOR_BANKING', ''),

        // eligible for pos
        'ELIGIBLE_FOR_POS' => env('ELIGIBLE_FOR_POS', ''),

         // MSME for Proprietership
         'COLLECT_MSME_CERTIFICATE_PROPRIETORSHIP' => env('COLLECT_MSME_CERTIFICATE_PROPRIETORSHIP', ''),

        // Payment Pages - Ecommerce
        'PP_ECOMMERCE_SPLITZ' => env('PP_ECOMMERCE_SPLITZ', ''),

        //Magic Prepay COD
        'MAGIC_PREPAY_COD' => env('MAGIC_PREPAY_COD', ''),

        //Magic RTO Analytics
        'MAGIC_RTO_ANALYTICS_V3' => env('MAGIC_RTO_ANALYTICS_V3', ''),

        // Magic
        'MAGIC_COD_ENGINE' => env('MAGIC_COD_ENGINE'),
        'MAGIC_ORDER_ANALYTICS'  => env('MAGIC_ORDER_ANALYTICS', ''),
        'MAGIC_ORDER_ANALYTICS_CR'  => env('MAGIC_ORDER_ANALYTICS_CR', ''),
        'MAGIC_SHOPIFY_ORDER_EDIT' => env('MAGIC_SHOPIFY_ORDER_EDIT', ''),

        // Affordability Onboarding for SBI CC EMi and credit card segeration
        'SHOW_SEGREGATED_CREDIT_EMI_METHODS' => env('SHOW_SEGREGATED_CREDIT_EMI_METHODS', ''),
        'SHOW_INTERNATIONAL_PAYMENTS_BUTTON' => env('SHOW_INTERNATIONAL_PAYMENTS_BUTTON', ''),

        // Chunked Based Streaming - View Page
        'CHUNKED_BASED_STREAMING' => env('CHUNKED_BASED_STREAMING', ''),

        // Dashboard User Concurrent API call
        'DASHBOARD_USER_CONCURRENT_API_CALL' => env('DASHBOARD_USER_CONCURRENT_API_CALL', ''),
        // Ecosystem Downtimes UI - Availability and Downtime
        'ECOSYSTEM_DOWNTIMES' => env('ECOSYSTEM_DOWNTIMES',''),
        'SUCCESS_RATE_ADMIN' => env('SUCCESS_RATE_ADMIN', ''),

        // For showing Ternimal status banner on dashboard.
        'SHOW_TERMINAL_STATUS_BANNER' => env('SHOW_TERMINAL_STATUS_BANNER',''),

        // For showing IS PLUS PLUS IN Settle Now - Capital
        'CAPITAL_ISPLUSPLUS_SPLITZ' => env('CAPITAL_ISPLUSPLUS_SPLITZ', ''),
        // Cross border payments experiments
        'N_EXPONENT_SUPPORT' => env('N_EXPONENT_SUPPORT',''),

        'SODEXO_INSTRUMENT' => env('SODEXO_INSTRUMENT',''),

        'SEARCH_V2_PHASE_1' => env('SEARCH_V2_PHASE_1',''),

        'RECURRING_CARD_MULTI_FREQUENCY' => env('RECURRING_CARD_MULTI_FREQUENCY', ''),

        'RECURRING_DEBIT_PATTERN' => env('RECURRING_DEBIT_PATTERN', ''),

        'CHECKOUT_ANALYTICS' => env('CHECKOUT_ANALYTICS',''),
         // For showing offline transactions and functionalities for omni channel merchants
        'OMNI_CHANNEL_MERCHANTS' => env('OMNI_CHANNEL_MERCHANTS', ''),

        // Splitz experiment caching enabled
        'SPLITZ_API_CACHING_ENABLED' => env('SPLITZ_API_CACHING_ENABLED', ''),

        // razorx caching enabled
        'RAZORX_CACHING_ENABLED' => env('RAZORX_CACHING_ENABLED', ''),

        // unified signup
        'CURLEC_REDIRECTION_ENABLED' => env('CURLEC_REDIRECTION_ENABLED', ''),

        // 2fa  Session disabling
        'SESSION_DISABLED_2FA' => env('SESSION_DISABLED_2FA', ''),



        // enable testing for vas
        'ENABLE_TESTING_FOR_VAS' => env('ENABLE_TESTING_FOR_VAS', ''),

        // chunked based streaming disabled
        'CHUNKED_BASED_STREAMING_DISABLED' => env('CHUNKED_BASED_STREAMING_DISABLED', ''),

        // disables capital Instant Settlements Settle Now button
        'CAPITAL_ES_BLOCKED_SPLITZ' => env('CAPITAL_ES_BLOCKED_SPLITZ', ''),

        // 2fa  for password and api key
        'PASSWORD_API_KEY_2FA' => env('PASSWORD_API_KEY_2FA', ''),

        // 2FA for route linked account batch upload
        'ROUTE_LINKED_ACCOUNT_2FA' => env('ROUTE_LINKED_ACCOUNT_2FA', ''),

        // 2FA for route linked account update
        'BLOCK_LINKED_ACCOUNT_BA_UPDATE_2FA' => env('BLOCK_LINKED_ACCOUNT_BA_UPDATE_2FA', ''),

        // enable rrn search in Payments
        'VAS_RRN_SEARCH' => env('VAS_RRN_SEARCH', ''),

        // Ramp Account and Settings for Excluded Segment of merchants
        'RAMP_ACCOUNT_SETTINGS_FOR_EXCLUDED_SEGMENT' => env('RAMP_ACCOUNT_SETTINGS_FOR_EXCLUDED_SEGMENT', ''),

        // Ramp Account and Settings for Excluded Segment of merchants
        'RAMP_SETTLEMENTS_FOR_EXCLUDED_SEGMENT' => env('RAMP_SETTLEMENTS_FOR_EXCLUDED_SEGMENT', ''),

        // Ramp Account and Settings for Jnk Omni Enabled merchants
        'ENABLE_TRANSACTIONS_CLEANUP' => env('ENABLE_TRANSACTIONS_CLEANUP', ''),

        // Shell redirection experiment
        'SHELL_REDIRECTION_EXPERIMENT_ID' => env('SHELL_REDIRECTION_EXPERIMENT_ID', ''),

        // Rtux enabled experiment
        'RTUX_ENABLED_SPLITZ_EXPERIMENT_ID' => env('RTUX_ENABLED_SPLITZ_EXPERIMENT_ID', ''),

        // Enable new auth rearch
        "NEW_AUTH_REARCH" => env('NEW_AUTH_REARCH', ''),

        // banking unified signup
        'BANKING_REDIRECTION_ENABLED' => env('BANKING_REDIRECTION_ENABLED', ''),

        'ABAC_ACCESS_DASHBOARD'   =>  env('ABAC_ACCESS_DASHBOARD', ''),
    ]
];

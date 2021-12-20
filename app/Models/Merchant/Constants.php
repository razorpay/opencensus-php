<?php

namespace RZP\Models\Merchant;

use RZP\Models\Admin\ConfigKey;
use \RZP\Models\Admin\Service as AdminService;
use RZP\Models\Merchant\Detail\BusinessCategory;
use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Models\Merchant\Detail\Status as ActivationStatus;

/**
 * General constants for Merchant Model.
 */
final class Constants
{
    const M2M_REFERRAL_ENABLE_AFTER_MIN_TRANSACTION_VOLUME  = "M2M_REFERRAL_ENABLE_AFTER_MIN_TRANSACTION_VOLUME";
    const M2M_REFERRAL_ENABLE_AFTER_MIN_ACTIVATED_TIME      = "M2M_REFERRAL_ENABLE_AFTER_MIN_ACTIVATED_TIME";
    const M2M_REFERRAL_MIN_TRANSACTION_COUNT                = "M2M_REFERRAL_MIN_TRANSACTION_COUNT";
    const M2M_REFERRALS_ENABLE_CRON                         = 'm2m_referrals_enable_cron';
    const M2M_REFERRAL_TIME_BOUND_THRESHOLD                 = 30;
    const TRACE                                   = 'trace';
    const API_MUTEX                               = 'api.mutex';
    const EDIT                                    = 'edit';
    const INPUT                                   = "input";
    const INDIVIDUAL                              = 'individual';
    const CONTACT                                 = 'contact';
    const TIMESTAMP                               = 'timestamp';
    const REF                                     = 'ref';
    const DATE                                    = 'date';
    const SIGNUP_DATE                             = 'signup_date';
    const SUSPEND                                 = 'suspend';
    const UNSUSPEND                               = 'unsuspend';
    const PAYMENT_TIMEOUT_WINDOW                  = 'payment_timeout_window';
    const MERCHANT                                = 'merchant';
    const PARAMS                                  = 'params';
    const REPO                                    = 'repo';
    const RECEIVER                                = 'receiver';
    const TEMPLATE                                = 'template';
    const OWNER_TYPE                              = 'ownerType';
    const TEMPLATE_NAME                           = 'template_name';
    const MERCHANT_NAME                           = 'merchantName';
    const DASHBOARD_URL                           = 'dashboardUrl';
    const CONFIG                                  = 'config';
    const OWNER_ID                                = 'ownerId';
    const APPLICATIONS_DASHBOARD_URL              = 'applications.dashboard.url';
    const SOURCE                                  = 'source';
    const TOTAL                                   = 'total';
    const MILESTONE                               = 'milestone';
    const PAYMENT                                 = 'payment';
    const RECIPIENTS                              = 'recipients';
    const MERCHANTS                               = 'merchants';
    const MERCHANTID                              = 'merchantId';
    const ACTIVATION_STATUS                       = 'activationStatus';
    const WORKFLOW_URL                            = 'workflowUrl';
    const BUSINESS_TYPE                           = 'businessType';
    const TYPE                                    = 'type';
    const LEVEL                                   = 'level';
    const WORKFLOW                                = 'workflow';
    const ID                                      = 'id';
    // Used for pagination in submerchant listing for partners
    const TO                                      = 'to';
    const FROM                                    = 'from';
    const SKIP                                    = 'skip';
    const COUNT                                   = 'count';

    // Partner constants
    const BANK                                    = 'bank';
    const PARTNER                                 = 'partner';
    const RESELLER                                = 'reseller';
    const AGGREGATOR                              = 'aggregator';
    const FULLY_MANAGED                           = 'fully_managed';
    const PURE_PLATFORM                           = 'pure_platform';
    const PARTNER_INTENT                          = 'partner_intent';
    const TRANSLATE_WEBHOOK_GATEWAY               = 'translate_webhook_gateway';

    const PENNY_TESTING_COUNT                     = 'penny_testing_count';

    const DOCUMENT_TYPE                           = 'document_type';
    const ENTITY                                  = 'entity';
    const IDEMPOTENT_ID                           = 'idempotent_id';
    const BATCH_ACTION                            = 'batch_action';


    const MERCHANT_ID                             = 'merchant_id';
    const SUBMERCHANT_ID                          = 'submerchant_id';
    const PARTNER_ID                              = 'partner_id';
    const MARK_AS_PARTNER_IN_PROGRESS             = 'mark_as_partner_in_progress';
    const MARK_AS_PARTNER_LOCK_TIME_OUT           = 30; //seconds
    const MERCHANT_ONBOARDING                     = 'merchant_onboarding';

    // Used in partners flows
    const APPLICATION_ID                          = 'application_id';
    const APP_TYPE                                = 'app_type';

    // used in merchant es sync
    const INTERVAL                                 = 'interval';
    const RECORDS_PROCESSED                        = 'records_processed';

    const REMOVE                                  = 'remove';

    // mailing lists
    const LIVE                                    = 'live';

    const RECENT                                  = 'recent';

    const ALL                                     = 'all';

    const LIVE_SETTLEMENT_ON_DEMAND               = 'live_settlement_on_demand';

    const LIVE_SETTLEMENT_DEFAULT                 = 'live_settlement_default';

    const ALL_SETTLEMENT_ON_DEMAND                = 'all_settlement_on_demand';

    const ALL_SETTLEMENT_DEFAULT                  = 'all_settlement_default';

    const RAZORX_EXPERIMENT_ON                    = 'on';

    const MERCHANT_MUTEX_LOCK_TIMEOUT                 = '60';
    const MERCHANT_MUTEX_RETRY_COUNT                  = '2';

    const SUCCESS                                 = 'Success';
    const FAILURE                                 = 'Failure';

    // This are added to remove the email_id being tagged in the slack
    const DASHBOARD_INTERNAL                      = 'DASHBOARD_INTERNAL';
    const MERCHANT_USER                           = 'MERCHANT_USER';

    const USER_CONTACT_MOBILE                     = 'user_contact_mobile';

    // Instant Refunds Pricing Fetch related constants
    const RULES                     = 'rules';
    const CUSTOM_PRICING            = 'custom_pricing';
    const MAX_RULES_TO_BE_DISPLAYED = 6;

    const IS_USER_EXIST                                 = 'is_user_exist';
    const IS_TEAM_MEMBER                                = 'is_team_member';
    const IS_OWNER                                      = 'is_owner';
    const LOGOUT_SESSIONS_FOR_USERS                     = 'logout_sessions_for_users';
    const MERCHANT_EMAIL_UPDATE_CACHE_KEY               = 'merchant_email_update_%s';
    const MERCHANT_EMAIL_UPDATE_CACHE_TTL               = 60 * 60 * 24;    // 24 hours (Multiplying by 60 since cache put() expect ttl in seconds)
    const CURRENT_OWNER_EMAIL                           = 'current_owner_email';
    const REATTACH_CURRENT_OWNER                        = 'reattach_current_owner';
    const SET_CONTACT_EMAIL                             = 'set_contact_email';

    const OLD_PRICING_PLAN_ID                           = 'old_pricing_plan_id';
    const NEW_PRICING_PLAN_ID                           = 'new_pricing_plan_id';
    const SELF_SERVE_FOR_FEE_BEARER                     = 'SelfServe';

    const COMMENT                                       = 'comment';
    const NEW_TRANSACTION_LIMIT_BY_MERCHANT             = 'new_transaction_limit_by_merchant';
    const TRANSACTION_LIMIT_INCREASE_REASON             = 'transaction_limit_increase_reason';
    const TRANSACTION_LIMIT_INCREASE_INVOICE_URL        = 'transaction_limit_increase_invoice_url';
    const INCREASE_TRANSACTION_LIMIT                    = 'increase_transaction_limit';
    const UPDATED_TRANSACTION_LIMIT                     = 'updated_transaction_limit';
    const GSTIN_UPDATE_SELF_SERVE                       = 'gstin_update_self_serve';

    const INCREASE_TRANSACTION_LIMIT_POST_WORKFLOW_APPROVE          = 'RZP\Http\Controllers\MerchantController@postTransactionLimitWorkflowApprove';

    const TRANSACTION_LIMIT_INCREASE_REASON_COMMENT                 = 'Transaction Limit Increase Reason: %s';
    const TRANSACTION_LIMIT_INCREASE_SUPPORT_DOCUMENT_URL_COMMENT   = 'Support Document (Invoice) URL: %sadmin/entity/ufh.files/live/file_%s';

    public static $EntityBatchActionSettingParams = [
        self::BATCH_ACTION,
        self::IDEMPOTENT_ID,
        self::ENTITY,
        Entity::ID,
    ];

    public static $internationalActionMapping = [
        Action::ENABLE_INTERNATIONAL  => 1,
        Action::DISABLE_INTERNATIONAL => 0,
    ];

    public static $partnerTypes = [
        self::BANK,
        self::RESELLER,
        self::AGGREGATOR,
        self::FULLY_MANAGED,
        self::PURE_PLATFORM,
    ];

    // Used in merchant activation elastic search flows
    const INSTANT_ACTIVATION = 'instant_activation';

    // Used in elastic search merchant search flows
    const BUSINESS_TYPE_BUCKET = 'business_type_bucket';

    const IS_WHITELISTED_ACTIVATION = 'is_whitelisted_activation';

    // need clarification constants
    const REASON_TYPE            = 'reason_type';
    const FIELD_TYPE             = 'field_type';
    const FIELD_VALUE            = 'field_value';
    const FIELD_NAME             = 'field_name';
    const REASON                 = 'reason';
    const REASON_CODE            = 'reason_code';
    const CUSTOM_REASON_TYPE     = 'custom';
    const PREDEFINED_REASON_TYPE = 'predefined';
    const DOCUMENT               = 'document';
    const REASON_FROM            = 'from';
    const NC_COUNT               = 'nc_count';
    const IS_CURRENT             = 'is_current';

    const  ADDITIONAL_WEBSITE       = 'additional_website';
    const  ENABLE_INTERNATIONAL     = 'enable_international';
    const  OLD_ENABLE_INTERNATIONAL = 'old_enable_international';
    const  BANK_DETAIL_UPDATE       = 'bank_detail_update';
    const  PERMISSION               = 'permission';
    const  NO_ACTION_RECEIVED       = 'no_action_received';
    const  IN_REVIEW                = 'in_review';
    const  APPROVED                 = 'approved';
    const  UPDATE_BUSINESS_WEBSITE  = 'update_business_website';
    const  ADD_ADDITIONAL_WEBSITE   = 'add_additional_website';

    const ENABLE_INTERNATIONAL_PG      = 'enable_international_pg';
    const ENABLE_INTERNATIONAL_PROD_V2 = 'enable_international_prod_v2';

    const INTERNATIONAL_WORKFLOW_LIST = [
        self::ENABLE_INTERNATIONAL_PG,
        self::ENABLE_INTERNATIONAL_PROD_V2,
    ];

    const X_HOURS_AFTER_ACTIVATION_FORM_SUBMISSION = "x_hours_after_activation_form_submission";
    const X_HOURS_WITHIN_ACTIVATION_FORM_SUBMISSION = "x_hours_within_activation_form_submission";

    const DEDUPE_MERCHANT       = "dedupe_merchant";
    const NON_DEDUPE_MERCHANT   = "non_dedupe_merchant";

    const MESSAGE       = "message";
    const CTA_LIST      = "cta_list";
    const SHOW_POPUP    = "show_popup";
    const DEFAULT       = "default";
    const MIN_ACTIVATION_PROGRESS       = "minimum_activation_progress";
    const MAX_ACTIVATION_PROGRESS       = "maximum_activation_progress";

    // Possible CTAs
    const CONTINUE_WITH_TICKET      =    "continue_with_ticket";
    const COMPLETE_ACTIVATION_FORM  =    "complete_activation_form";
    const FILL_ACTIVATION_FORM      =    "fill_activation_form";
    const NEEDS_CLARIFICATION       =    "needs_clarification";
    const FAQS                      =    "faqs";
    const THANKS                    =    "thanks";
    const AGGREGATIONS              =    "aggregations";
    const FILTERS                   =    "filters";

    const MERCHANT_WORKFLOW_CLARIFICATION                =  "merchant_workflow_clarification";
    const WORKFLOW_CLARIFICATION_DOCUMENTS_IDS           =  "clarification_documents_ids";
    const UFH_FILE_URL                                   = "%sadmin/entity/ufh.files/live/file_%s ,  ";
    const MERCHANT_WORKFLOW_CLARIFICATION_FILES_PREFIX   = 'Files shared by merchant: ';

    const unregisteredMerchantMaximumTransactionLimit = [
        BusinessCategory::FINANCIAL_SERVICES        => 10000000,
        BusinessCategory::EDUCATION                 => 20000000,
        BusinessCategory::HEALTHCARE                => 10000000,
        BusinessCategory::UTILITIES                 => 5000000,
        BusinessCategory::LOGISTICS                 => 10000000,
        BusinessCategory::TOURS_AND_TRAVEL          => 10000000,
        BusinessCategory::TRANSPORT                 => 10000000,
        BusinessCategory::ECOMMERCE                 => 20000000,
        BusinessCategory::FOOD                      => 10000000,
        BusinessCategory::IT_AND_SOFTWARE           => 10000000,
        BusinessCategory::GAMING                    => 10000000,
        BusinessCategory::MEDIA_AND_ENTERTAINMENT   => 10000000,
        BusinessCategory::SERVICES                  => 10000000,
        BusinessCategory::HOUSING                   => 10000000,
        BusinessCategory::NOT_FOR_PROFIT            => 10000000,
        BusinessCategory::SOCIAL                    => 10000000,
        BusinessCategory::OTHERS                    => 10000000,
    ];

    const registeredMerchantMaximumTransactionLimit = [
        BusinessCategory::FINANCIAL_SERVICES        => 100000000,
        BusinessCategory::EDUCATION                 => 100000000,
        BusinessCategory::HEALTHCARE                => 50000000,
        BusinessCategory::UTILITIES                 => 20000000,
        BusinessCategory::GOVERNMENT                => 100000000,
        BusinessCategory::LOGISTICS                 => 50000000,
        BusinessCategory::TOURS_AND_TRAVEL          => 100000000,
        BusinessCategory::TRANSPORT                 => 50000000,
        BusinessCategory::ECOMMERCE                 => 100000000,
        BusinessCategory::FOOD                      => 20000000,
        BusinessCategory::IT_AND_SOFTWARE           => 100000000,
        BusinessCategory::GAMING                    => 20000000,
        BusinessCategory::MEDIA_AND_ENTERTAINMENT   => 20000000,
        BusinessCategory::SERVICES                  => 50000000,
        BusinessCategory::HOUSING                   => 100000000,
        BusinessCategory::NOT_FOR_PROFIT            => 100000000,
        BusinessCategory::SOCIAL                    => 20000000,
        BusinessCategory::OTHERS                    => 50000000,
    ];

    /**
     * Partner types that are allowed to view and manage
     * partner settings like client creds.
     *
     * @var array
     */
    public static $settingsAccessPartnerTypes = [
        self::FULLY_MANAGED,
        self::AGGREGATOR,
    ];

    /**
     * Partner types that are allowed to view and manage webhooks.
     *
     * @var array
     */
    public static $webhooksAccessPartnerTypes = [
        self::FULLY_MANAGED,
        self::AGGREGATOR,
        self::PURE_PLATFORM,
    ];

    /**
     * Step Map gives information on attributes filled by merchant Step wise.
     * this is used to let merchant know what all the steps are finished and
     * can continue from where merchant left the activation form.
     */
    const STEP_MAP = [
        Detail\Entity::CONTACT_NAME                => 1,
        Detail\Entity::CONTACT_EMAIL               => 1,
        Detail\Entity::CONTACT_MOBILE              => 1,

        Detail\Entity::BUSINESS_TYPE               => 2,
        Detail\Entity::BUSINESS_NAME               => 2,
        Detail\Entity::BUSINESS_DBA                => 2,
        Detail\Entity::BUSINESS_INTERNATIONAL      => 2,
        Detail\Entity::BUSINESS_REGISTERED_ADDRESS => 2,
        Detail\Entity::BUSINESS_REGISTERED_STATE   => 2,
        Detail\Entity::BUSINESS_REGISTERED_CITY    => 2,
        Detail\Entity::BUSINESS_REGISTERED_PIN     => 2,
        Detail\Entity::BUSINESS_OPERATION_ADDRESS  => 2,
        Detail\Entity::BUSINESS_OPERATION_STATE    => 2,
        Detail\Entity::BUSINESS_OPERATION_CITY     => 2,
        Detail\Entity::BUSINESS_OPERATION_PIN      => 2,
        Detail\Entity::GSTIN                       => 2,
        Detail\Entity::P_GSTIN                     => 2,
        Detail\Entity::PROMOTER_PAN                => 2,
        Detail\Entity::PROMOTER_PAN_NAME           => 2,

        Detail\Entity::BANK_BRANCH_IFSC            => 3,
        Detail\Entity::BANK_ACCOUNT_NUMBER         => 3,
        Detail\Entity::BANK_ACCOUNT_NAME           => 3,

        Detail\Entity::BUSINESS_PROOF_URL          => 4,
        Detail\Entity::BUSINESS_PAN_URL            => 4,
        Detail\Entity::ADDRESS_PROOF_URL           => 4,
        Detail\Entity::PROMOTER_ADDRESS_URL        => 4,
        Detail\Entity::FORM_12A_URL                => 4,
        Detail\Entity::FORM_80G_URL                => 4,
    ];

    const STEP_MAP_ACCOUNT = [
        Detail\Entity::BUSINESS_TYPE               => 1,
        Detail\Entity::BUSINESS_NAME               => 1,
        Detail\Entity::COMPANY_PAN                 => 1,
        Detail\Entity::PROMOTER_PAN                => 1,

        Detail\Entity::BANK_BRANCH_IFSC            => 2,
        Detail\Entity::BANK_ACCOUNT_NUMBER         => 2,
        Detail\Entity::BANK_ACCOUNT_NAME           => 2,

        Detail\Entity::ADDRESS_PROOF_URL           => 3,
        Detail\Entity::PROMOTER_PAN_URL            => 3,
    ];

    const UPLOAD_KEYS = [
        Detail\Entity::BUSINESS_PROOF_URL   => 'business_proof',
        Detail\Entity::BUSINESS_PAN_URL     => 'business_pan_proof',
        Detail\Entity::ADDRESS_PROOF_URL    => 'address_proof',
        Detail\Entity::PROMOTER_ADDRESS_URL => 'promoter_address_proof',
        Detail\Entity::FORM_12A_URL         => 'form_12a_url',
        Detail\Entity::FORM_80G_URL         => 'form_80g_url',
    ];

    const UPLOAD_KEYS_ACCOUNT = [
        Detail\Entity::ADDRESS_PROOF_URL    => 'address_proof',
        Detail\Entity::PROMOTER_PAN_URL     => 'promoter_pan_proof',
    ];

    const PRE_SIGNUP_FIELDS = [
        Detail\Entity::BUSINESS_TYPE,
        Detail\Entity::TRANSACTION_VOLUME,
        Detail\Entity::ROLE,
        Detail\Entity::DEPARTMENT,
        Detail\Entity::CONTACT_NAME,
        Detail\Entity::BUSINESS_NAME,
        Detail\Entity::CONTACT_MOBILE,
        Detail\Entity::BUSINESS_WEBSITE,
        Detail\Entity::CONTACT_EMAIL,
    ];

    const ACTIVATION_MANDATORY_FIELDS = [
        Entity::CATEGORY,
        Entity::BILLING_LABEL,
    ];

    const INSTANT_ACTIVATION_MANDATORY_FIELDS = [
        Entity::CATEGORY,
        Entity::CATEGORY2,
        Entity::BILLING_LABEL,
    ];

    const INSURANCE_CATEGORIES = [
        '6211',
    ];

    const MERCHANT_WORKFLOWS = [
        self::ADDITIONAL_WEBSITE   => [
            self::PERMISSION => Permission::EDIT_MERCHANT_WEBSITE_DETAIL,
            self::ENTITY     => \RZP\Constants\Entity::MERCHANT_DETAIL,
        ],
        self::ENABLE_INTERNATIONAL => [
            self::PERMISSION => Permission::EDIT_MERCHANT_INTERNATIONAL_NEW,
            self::ENTITY     => \RZP\Constants\Entity::MERCHANT,
        ],
        self::OLD_ENABLE_INTERNATIONAL => [
            self::PERMISSION => Permission::EDIT_MERCHANT_INTERNATIONAL,
            self::ENTITY     => \RZP\Constants\Entity::MERCHANT,
        ],
        self::ENABLE_INTERNATIONAL_PG => [
            self::PERMISSION => Permission::EDIT_MERCHANT_PG_INTERNATIONAL,
            self::ENTITY     => \RZP\Constants\Entity::MERCHANT,
        ],
        self::ENABLE_INTERNATIONAL_PROD_V2 => [
            self::PERMISSION => Permission::EDIT_MERCHANT_PROD_V2_INTERNATIONAL,
            self::ENTITY     => \RZP\Constants\Entity::MERCHANT,
        ],
        self::BANK_DETAIL_UPDATE   => [
            self::PERMISSION => Permission::EDIT_MERCHANT_BANK_DETAIL,
            self::ENTITY     => \RZP\Constants\Entity::BANK_ACCOUNT,
        ],
        self::UPDATE_BUSINESS_WEBSITE   => [
            self::PERMISSION => Permission::UPDATE_MERCHANT_WEBSITE,
            self::ENTITY     => \RZP\Constants\Entity::MERCHANT_DETAIL,
        ],
        self::ADD_ADDITIONAL_WEBSITE   => [
            self::PERMISSION => Permission::ADD_ADDITIONAL_WEBSITE,
            self::ENTITY     => \RZP\Constants\Entity::MERCHANT_DETAIL,
        ],
        self::INCREASE_TRANSACTION_LIMIT   => [
            self::PERMISSION   => Permission::INCREASE_TRANSACTION_LIMIT,
            self::ENTITY       => \RZP\Constants\Entity::MERCHANT,
        ],
        self::GSTIN_UPDATE_SELF_SERVE      => [
            self::PERMISSION  => Permission::UPDATE_MERCHANT_GSTIN_DETAIL,
            self::ENTITY      => \RZP\Constants\Entity::MERCHANT_DETAIL,
        ],
    ];

    // Merchant Email Types For Instrumentation
    const MERCHANT_INSTRUMENT_STATUS_UPDATE = "merchant_instrument_status_update";
    const INSTRUMENT_STATUS_UPDATE_MERCHANT_MAIL = "instrument_status_update_merchant_mail";

    const TICKET_CREATION_POPUP_DATA_FOR_ACTIVATION_STATUS = [

        ActivationStatus::UNDER_REVIEW => [

            self::X_HOURS_AFTER_ACTIVATION_FORM_SUBMISSION => [
                self::SHOW_POPUP => true,
                self::MESSAGE    => "We received your activation form on :submission_at. Your documents and KYC details are under review. It usually takes 3-4 working days for our team to review your documents. We will reach out if we need any other clarification. Please go through our FAQs if you have any other queries.",
                self::CTA_LIST   => [self::CONTINUE_WITH_TICKET, self::FAQS],
            ],

            self::X_HOURS_WITHIN_ACTIVATION_FORM_SUBMISSION => [
                self::SHOW_POPUP => true,
                self::MESSAGE    => "We received your activation form on :submission_at. Your documents and KYC details are under review. It usually takes 3-4 working days for our team to review your documents. We will reach out if we need any clarification. Please go through our FAQs if you have any other queries.",
                self::CTA_LIST   => [self::THANKS, self::FAQS],
            ],
        ],

        ActivationStatus::NEEDS_CLARIFICATION => [

            self::DEDUPE_MERCHANT => [
                self::SHOW_POPUP => false,
                self::MESSAGE    => "",
                self::CTA_LIST   => [],
            ],

            self::NON_DEDUPE_MERCHANT => [
                self::SHOW_POPUP => true,
                self::MESSAGE    => "Your account activation is pending. After reviewing the documents and KYC details submitted by you, our team has requested for some clarifications. Kindly share the requested information to serve you better. If you have any other concerns, please feel free to raise a ticket.",
                self::CTA_LIST   => [self::CONTINUE_WITH_TICKET, self::NEEDS_CLARIFICATION],
            ]
        ],

        ActivationStatus::REJECTED => [

            self::DEFAULT => [
                self::SHOW_POPUP => true,
                self::MESSAGE    => "We regret to inform you that we will not be able to support your business as the bank has not approved your activation form. Your account is terminated with a hold on the funds for the chargeback period of 120 days from the date of rejection. Please go through our FAQs if you have any other queries.",
                self::CTA_LIST   => [self::CONTINUE_WITH_TICKET, self::FAQS],
            ]
        ],
    ];

    const TICKET_CREATION_POPUP_DATA_FOR_ACTIVATION_PROGRESS_RANGES = [
        [
            self::MIN_ACTIVATION_PROGRESS => 0,
            self::MAX_ACTIVATION_PROGRESS => ConfigKey::MAX_ACTIVATION_PROGRESS_FOR_POPUP_RANGE1,
            self::SHOW_POPUP              => true,
            self::MESSAGE                 => "Your account activation is pending. Please submit the KYC form to serve you better. It usually takes 3-4 working days for our team to review your documents post submission. We will reach out if we need any clarification. Please go through our FAQs if you have any other queries.",
            self::CTA_LIST                => [self::FAQS, self::FILL_ACTIVATION_FORM],
        ],
        [
            self::MIN_ACTIVATION_PROGRESS => ConfigKey::MAX_ACTIVATION_PROGRESS_FOR_POPUP_RANGE1,
            self::MAX_ACTIVATION_PROGRESS => 100,
            self::SHOW_POPUP              => true,
            self::MESSAGE                 => "Your account activation is pending. Please submit the KYC form to serve you better. It usually takes 3-4 working days for our team to review your documents post submission. We will reach out if we need any clarification. If you have any other concerns, please feel free to raise a ticket.",
            self::CTA_LIST                => [self::CONTINUE_WITH_TICKET, self::COMPLETE_ACTIVATION_FORM],
        ],
    ];

    const MERCHANT_RISK_SCORE_DATA_DRUID_QUERY  = 'SELECT * FROM druid.risk_scoring_fact WHERE merchants_id = \'%s\'';
    const MERCHANT_RISK_SCORE_DRUID_KEY_MAPPING = [
        'Transacting_Dedupe_Merchant_Risk_Scoring_Transacting_Dedupe_Merchant_Risk_Score' => 'transaction_dedupe_merchant_risk_score',
        'Global_Merchant_Risk_Scoring_Global_Merchant_Risk_Score'                         => 'global_merchant_risk_score',
        'merchant_vintage_merchant_vintage'                                               => 'merchant_vintage',
        'Payment_Details_first_transaction_date'                                          => 'first_transaction_date_attempted',
        'Payment_Details_last_transaction_date'                                           => 'last_transaction_date_attempted',
        'Payment_Details_lifetime_captured_payments'                                      => 'number_of_transactions_captured.0.lifetime',
        'Payment_Details_past_one_month_captured_payments'                                => 'number_of_transactions_captured.1.1_month',
        'Payment_Details_lifetime_captured_gmv'                                           => 'total_GMV_captured.0.lifetime',
        'Payment_Details_past_one_month_captured_gmv'                                     => 'total_GMV_captured.1.1_month',
        'Payment_Details_lifetime_success_rate'                                           => 'success_rate_(%).0.lifetime',
        'Payment_Details_past_one_month_success_rate'                                     => 'success_rate_(%).1.1_month',
        'Domestic_cts_overall_lifetime_cts'                                               => 'domestic_merchant_chargeback_to_sale_ratio_(%).0.lifetime',
        'Domestic_cts_3months_last_3_months_cts'                                          => 'domestic_merchant_chargeback_to_sale_ratio_(%).1.3_months',
        'Domestic_FTS_lifetime_domestic_FTS'                                              => 'domestic_merchant_fraud_to_sale_ratio_(%).0.lifetime',
        'Domestic_FTS_past_3_month_domestic_FTS'                                          => 'domestic_merchant_fraud_to_sale_ratio_(%).1.3_months',
        'Dispute_ltd_lifetime_disputes'                                                   => 'total_dispute_count.0.lifetime',
        'Dispute_1month_past_1_month_disputes'                                            => 'total_dispute_count.1.1_month',
        'International_Payment_Details_lifetime_captured_payments'                        => 'international_details.number_of_transactions_captured.0.lifetime',
        'International_Payment_Details_past_one_month_captured_payments'                  => 'international_details.number_of_transactions_captured.1.1_month',
        'International_Payment_Details_lifetime_captured_gmv'                             => 'international_details.total_GMV_captured.0.lifetime',
        'International_Payment_Details_past_one_month_captured_gmv'                       => 'international_details.total_GMV_captured.1.1_month',
        'International_OAR_Order_Approval_rate'                                           => 'international_details.international_order_approval_rate_(%)',
        'International_Payment_Details_lifetime_success_rate'                             => 'international_details.success_rate_(%).0.lifetime',
        'International_Payment_Details_past_one_month_success_rate'                       => 'international_details.success_rate_(%).1.1_month',
        'International_cts_overall_lifetime_cts'                                          => 'international_details.merchant_CTS_(%).0.lifetime',
        'International_cts_3months_last_3_months_cts'                                     => 'international_details.merchant_CTS_(%).1.3_months',
        'International_FTS_lifetime_international_FTS'                                    => 'international_details.merchant_FTS_(%).0.lifetime',
        'International_FTS_past_3_month_international_FTS'                                => 'international_details.merchant_FTS_(%).1.3_months',
        'PL_PP_Dedupe_pl_pp_deduped'                                                      => 'risk_alerts.PL_PP_dedupe',
        'Customer_Flagging_customer_flagged'                                              => 'risk_alerts.customer_flagging',
        'Blacklist_IP_blacklist_ip_entities'                                              => 'risk_alerts.blacklisted_ip_alerts',
        'workflows_data_FOH_workflows'                                                    => 'risk_workflow_count.FOH',
        'workflows_data_Suspend_workflows'                                                => 'risk_workflow_count.suspend',
        'workflows_data_Disable_live_workflows'                                           => 'risk_workflow_count.disable_live'
    ];
    const MERCHANT_RISK_SCORE_DATA_MONEY_FIELDS = [
        'total_GMV_captured.0.lifetime',
        'total_GMV_captured.1.1_month',
        'international_details.total_GMV_captured.0.lifetime',
        'international_details.total_GMV_captured.1.1_month',
    ];

    // merchant risk actions communication
    const SMS_TEMPLATE           = 'sms_template';
    const EMAIL_TEMPLATE         = 'email_template';
    const EMAIL_SUBJECT          = 'email_subject';
    const SMS_SOURCE             = 'api.bulk.risk.actions';
    const WHATSAPP_TEMPLATE_NAME = 'whatsapp_template_name';
    const WHATSAPP_TEMPLATE      = 'whatsapp_template';
    const DASHBOARD_TEMPLATE_TAG = 'dashboard_template_tag';

    const TAGS = 'tags';

    //cron tag names
    const MERCHANT_RISK_FOH_CRON_TAG            = 'Bulk_cron_tag_foh';
    const MERCHANT_RISK_SUSPEND_CRON_TAG        = 'Bulk_cron_tag_suspend';
    const MERCHANT_RISK_DISABLE_LIVE_CRON_TAG   = 'Bulk_cron_tag_disable_live';

    //FOH Notification templates
    const FOH_SMS_TEMPLATE           = 'sms.merchant_risk.generic.funds_on_hold.confirmation';
    const FOH_WHATSAPP_TEMPLATE_NAME = 'whatsapp.merchant_risk_actions.funds_on_hold';
    const FOH_WHATSAPP_TEMPLATE      = 'Hi {merchantName}, we regret to inform you that your settlements are under review due to risk alert for non-compliance with regulatory guidelines as set by our partner banks. Please check your email ID registered with Razorpay and help us with clarification to re-enable settlements';
    const FOH_DASHBOARD_TEMPLATE_TAG = 'mra_foh';
    const FOH_EMAIL_TEMPLATE         = 'emails.merchant.risk.generic.funds_on_hold.confirmation';
    const FOH_EMAIL_SUBJECT          = 'Razorpay Account Review: {merchant_name} | {merchant_id} | Funds under Review';

    //Suspend Notification templates
    const SUSPEND_ACCOUNT_SMS_TEMPLATE           = 'sms.merchant_risk_actions.suspend';
    const SUSPEND_ACCOUNT_WHATSAPP_TEMPLATE_NAME = 'whatsapp.merchant_risk_actions.suspend';
    const SUSPEND_ACCOUNT_WHATSAPP_TEMPLATE      = 'We have suspended your account as we observed suspicious account activity on your account - {merchant_id} in the name of M/s. {business_name} held with Razorpay. Please check your registered email for an email with subject Razorpay Account disabled: {merchant_name} | {merchant_id} for more details';

    //Disable Live Notification templates
    const DISABLE_LIVE_SMS_TEMPLATE           = 'sms.merchant_risk_actions.disable_live';
    const DISABLE_LIVE_EMAIL_TEMPLATE         = 'emails.merchant.risk.generic.disable_live.confirmation';
    const DISABLE_LIVE_EMAIL_SUBJECT          = 'Razorpay Account disabled: {merchant_name} | {merchant_id}';
    const DISABLE_LIVE_WHATSAPP_TEMPLATE_NAME = 'whatsapp.merchant_risk_actions.disable_live';
    const DISABLE_LIVE_WHATSAPP_TEMPLATE      = 'We have disabled your account as we observed suspicious account activity on your account - {merchant_id} in the name of M/s. {business_name} held with Razorpay.  Please check your registered email for an email with subject Razorpay Account disabled: {merchant_name} | {merchant_id} for more details';
    const DISABLE_LIVE_DASHBOARD_TEMPLATE_TAG = 'mra_disabled';

    // Mobile signup templates
    //FOH Notification templates
    const FOH_SMS_TEMPLATE_MOBILE_SIGNUP           = 'sms.risk.foh_confirmation_mobile_signup';
    const FOH_WHATSAPP_TEMPLATE_NAME_MOBILE_SIGNUP = 'whatsapp_risk_foh_confirmation_mobile_signup';
    const FOH_WHATSAPP_TEMPLATE_MOBILE_SIGNUP      = 'Hi {merchantName}, we regret to inform you that payment settlements to your Razorpay account are under review due to a risk alert raised by our banking partners. Please check link {supportTicketLink} and help us with the required clarification to re-enable settlements.';

    //Suspend Notification templates
    const SUSPEND_ACCOUNT_SMS_TEMPLATE_MOBILE_SIGNUP           = 'sms.risk.suspend_DL_mobile_signup';
    const SUSPEND_ACCOUNT_WHATSAPP_TEMPLATE_NAME_MOBILE_SIGNUP = 'whatsapp_risk_suspend_DL_mobile_signup';
    const SUSPEND_ACCOUNT_WHATSAPP_TEMPLATE_MOBILE_SIGNUP      = 'Hi {merchantName}, we had to suspend your Razorpay account as we observed some suspicious activity on it - {merchant_id} in the name of M/s. {business_name}. Please check link {supportTicketLink} for more details';

    //Disable Live Notification templates
    const DISABLE_LIVE_SMS_TEMPLATE_MOBILE_SIGNUP           = 'sms.risk.suspend_DL_mobile_signup';
    const DISABLE_LIVE_WHATSAPP_TEMPLATE_NAME_MOBILE_SIGNUP = 'whatsapp_risk_suspend_DL_mobile_signup';
    const DISABLE_LIVE_WHATSAPP_TEMPLATE_MOBILE_SIGNUP      = 'Hi {merchantName}, we had to suspend your Razorpay account as we observed some suspicious activity on it - {merchant_id} in the name of M/s. {business_name}. Please check link {supportTicketLink} for more details';

    //Disable International (temporary) Notification templates
    const DISABLE_INTERNATIONAL_TEMPORARY_SMS_TEMPLATE           = 'sms.risk.international_disablement_email_signup';
    const DISABLE_INTERNATIONAL_TEMPORARY_EMAIL_TEMPLATE         = 'emails.merchant.risk.generic.disable_international_temporary.confirmation';
    const DISABLE_INTERNATIONAL_TEMPORARY_EMAIL_SUBJECT          = 'Razorpay Account Review:  {merchant_name} | {merchant_id} | International Payment Acceptance Paused';
    const DISABLE_INTERNATIONAL_TEMPORARY_WHATSAPP_TEMPLATE_NAME = 'whatsapp_risk_international_disablement_email_signup';
    const DISABLE_INTERNATIONAL_TEMPORARY_WHATSAPP_TEMPLATE      = 'Hi {merchantName}, we regret to inform you that acceptance of international payments has been paused on your Razorpay account due to a risk alert raised by our banking partners. Please check your registered email ID for more details';

    //Disable International (permanent) Notification Template
    const DISABLE_INTERNATIONAL_PERMANENT_SMS_TEMPLATE           = 'sms.risk.international_disablement_email_signup';
    const DISABLE_INTERNATIONAL_PERMANENT_EMAIL_TEMPLATE         = 'emails.merchant.risk.generic.disable_international_permanent.confirmation';
    const DISABLE_INTERNATIONAL_PERMANENT_EMAIL_SUBJECT          = 'Razorpay Account Review:  {merchant_name} | {merchant_id} | International Disablement';
    const DISABLE_INTERNATIONAL_PERMANENT_WHATSAPP_TEMPLATE_NAME = 'whatsapp_risk_international_disablement_email_signup';
    const DISABLE_INTERNATIONAL_PERMANENT_WHATSAPP_TEMPLATE      = 'Hi {merchantName}, we regret to inform you that acceptance of international payments has been paused on your Razorpay account due to a risk alert raised by our banking partners. Please check your registered email ID for more details';

    //Disable International Mobile Signup Templates
    const DISABLE_INTERNATIONAL_SMS_TEMPLATE_MOBILE_SIGNUP           = 'sms.risk.international_disablement_mobile_signup';
    const DISABLE_INTERNATIONAL_WHATSAPP_TEMPLATE_NAME_MOBILE_SIGNUP = 'whatsapp_risk_international_disablement_email_signup';
    const DISABLE_INTERNATIONAL_WHATSAPP_TEMPLATE_MOBILE_SIGNUP      = 'Hi {merchantName}, we regret to inform you that acceptance of international payments has been paused due to a risk alert raised by our banking partners. Please check link {supportTicketLink} for more details';



    const MERCHANT_RISK_ACTION_CRON_ADD_TAG_MAP = [
        Action::SUSPEND      => self::MERCHANT_RISK_SUSPEND_CRON_TAG,
        Action::HOLD_FUNDS   => self::MERCHANT_RISK_FOH_CRON_TAG,
        Action::LIVE_DISABLE => self::MERCHANT_RISK_DISABLE_LIVE_CRON_TAG,
    ];

    const MERCHANT_RISK_ACTION_CRON_REMOVE_TAG_MAP = [
        Action::UNSUSPEND     => self::MERCHANT_RISK_SUSPEND_CRON_TAG,
        Action::RELEASE_FUNDS => self::MERCHANT_RISK_FOH_CRON_TAG,
        Action::LIVE_ENABLE   => self::MERCHANT_RISK_DISABLE_LIVE_CRON_TAG,
    ];

    const MERCHANT_RISK_ACTION_DASHBOARD_TAG = [
        Action::RELEASE_FUNDS => self::FOH_DASHBOARD_TEMPLATE_TAG,
        Action::LIVE_ENABLE   => self::DISABLE_LIVE_DASHBOARD_TEMPLATE_TAG,
    ];

    const MERCHANT_RISK_ACTIONS_MOBILE_SIGNUP_TEMPLATE_MAP = [
        Action::SUSPEND => [
            self::SMS_TEMPLATE              => self::SUSPEND_ACCOUNT_SMS_TEMPLATE_MOBILE_SIGNUP,
            self::WHATSAPP_TEMPLATE_NAME    => self::SUSPEND_ACCOUNT_WHATSAPP_TEMPLATE_NAME_MOBILE_SIGNUP,
            self::WHATSAPP_TEMPLATE         => self::SUSPEND_ACCOUNT_WHATSAPP_TEMPLATE_MOBILE_SIGNUP,
            self::EMAIL_TEMPLATE            => self::DISABLE_LIVE_EMAIL_TEMPLATE,
            self::EMAIL_SUBJECT             => self::DISABLE_LIVE_EMAIL_SUBJECT,
        ],

        Action::HOLD_FUNDS => [
            self::SMS_TEMPLATE               => self::FOH_SMS_TEMPLATE_MOBILE_SIGNUP,
            self::WHATSAPP_TEMPLATE_NAME     => self::FOH_WHATSAPP_TEMPLATE_NAME_MOBILE_SIGNUP,
            self::WHATSAPP_TEMPLATE          => self::FOH_WHATSAPP_TEMPLATE_MOBILE_SIGNUP,
            self::EMAIL_TEMPLATE             => self::FOH_EMAIL_TEMPLATE,
            self::EMAIL_SUBJECT              => self::FOH_EMAIL_SUBJECT,
        ],

        Action::LIVE_DISABLE => [
            self::SMS_TEMPLATE               => self::DISABLE_LIVE_SMS_TEMPLATE_MOBILE_SIGNUP,
            self::WHATSAPP_TEMPLATE_NAME     => self::DISABLE_LIVE_WHATSAPP_TEMPLATE_NAME_MOBILE_SIGNUP,
            self::WHATSAPP_TEMPLATE          => self::DISABLE_LIVE_WHATSAPP_TEMPLATE_MOBILE_SIGNUP,
            self::EMAIL_TEMPLATE             => self::DISABLE_LIVE_EMAIL_TEMPLATE,
            self::EMAIL_SUBJECT              => self::DISABLE_LIVE_EMAIL_SUBJECT,
        ],

        Action::DISABLE_INTERNATIONAL_TEMPORARY => [
            self::SMS_TEMPLATE               => self::DISABLE_INTERNATIONAL_SMS_TEMPLATE_MOBILE_SIGNUP,
            self::WHATSAPP_TEMPLATE_NAME     => self::DISABLE_INTERNATIONAL_WHATSAPP_TEMPLATE_NAME_MOBILE_SIGNUP,
            self::WHATSAPP_TEMPLATE          => self::DISABLE_INTERNATIONAL_WHATSAPP_TEMPLATE_MOBILE_SIGNUP,
            self::EMAIL_TEMPLATE             => self::DISABLE_INTERNATIONAL_TEMPORARY_EMAIL_TEMPLATE,
            self::EMAIL_SUBJECT              => self::DISABLE_INTERNATIONAL_TEMPORARY_EMAIL_SUBJECT,
        ],

        Action::DISABLE_INTERNATIONAL_PERMANENT => [
            self::SMS_TEMPLATE               => self::DISABLE_INTERNATIONAL_SMS_TEMPLATE_MOBILE_SIGNUP,
            self::WHATSAPP_TEMPLATE_NAME     => self::DISABLE_INTERNATIONAL_WHATSAPP_TEMPLATE_NAME_MOBILE_SIGNUP,
            self::WHATSAPP_TEMPLATE          => self::DISABLE_INTERNATIONAL_WHATSAPP_TEMPLATE_MOBILE_SIGNUP,
            self::EMAIL_TEMPLATE             => self::DISABLE_INTERNATIONAL_PERMANENT_EMAIL_TEMPLATE,
            self::EMAIL_SUBJECT              => self::DISABLE_INTERNATIONAL_PERMANENT_EMAIL_SUBJECT,
        ],
    ];

    const MERCHANT_RISK_ACTIONS_TEMPLATE_MAP = [
        Action::SUSPEND => [
            self::SMS_TEMPLATE              => self::SUSPEND_ACCOUNT_SMS_TEMPLATE,
            self::EMAIL_TEMPLATE            => self::DISABLE_LIVE_EMAIL_TEMPLATE,
            self::EMAIL_SUBJECT             => self::DISABLE_LIVE_EMAIL_SUBJECT,
            self::WHATSAPP_TEMPLATE_NAME    => self::SUSPEND_ACCOUNT_WHATSAPP_TEMPLATE_NAME,
            self::WHATSAPP_TEMPLATE         => self::SUSPEND_ACCOUNT_WHATSAPP_TEMPLATE,
        ],

        Action::HOLD_FUNDS => [
            self::SMS_TEMPLATE               => self::FOH_SMS_TEMPLATE,
            self::EMAIL_TEMPLATE             => self::FOH_EMAIL_TEMPLATE,
            self::EMAIL_SUBJECT              => self::FOH_EMAIL_SUBJECT,
            self::WHATSAPP_TEMPLATE_NAME     => self::FOH_WHATSAPP_TEMPLATE_NAME,
            self::WHATSAPP_TEMPLATE          => self::FOH_WHATSAPP_TEMPLATE,
        ],

        Action::LIVE_DISABLE => [
            self::SMS_TEMPLATE               => self::DISABLE_LIVE_SMS_TEMPLATE,
            self::EMAIL_TEMPLATE             => self::DISABLE_LIVE_EMAIL_TEMPLATE,
            self::EMAIL_SUBJECT              => self::DISABLE_LIVE_EMAIL_SUBJECT,
            self::WHATSAPP_TEMPLATE_NAME     => self::DISABLE_LIVE_WHATSAPP_TEMPLATE_NAME,
            self::WHATSAPP_TEMPLATE          => self::DISABLE_LIVE_WHATSAPP_TEMPLATE,
        ],

        //temporary
        Action::DISABLE_INTERNATIONAL_TEMPORARY => [
            self::SMS_TEMPLATE           => self::DISABLE_INTERNATIONAL_TEMPORARY_SMS_TEMPLATE,
            self::EMAIL_TEMPLATE         => self::DISABLE_INTERNATIONAL_TEMPORARY_EMAIL_TEMPLATE,
            self::EMAIL_SUBJECT          => self::DISABLE_INTERNATIONAL_TEMPORARY_EMAIL_SUBJECT,
            self::WHATSAPP_TEMPLATE_NAME => self::DISABLE_INTERNATIONAL_TEMPORARY_WHATSAPP_TEMPLATE_NAME,
            self::WHATSAPP_TEMPLATE      => self::DISABLE_INTERNATIONAL_TEMPORARY_WHATSAPP_TEMPLATE,
        ],
        Action::DISABLE_INTERNATIONAL_PERMANENT => [
            self::SMS_TEMPLATE           => self::DISABLE_INTERNATIONAL_PERMANENT_SMS_TEMPLATE,
            self::EMAIL_TEMPLATE         => self::DISABLE_INTERNATIONAL_PERMANENT_EMAIL_TEMPLATE,
            self::EMAIL_SUBJECT          => self::DISABLE_INTERNATIONAL_PERMANENT_EMAIL_SUBJECT,
            self::WHATSAPP_TEMPLATE_NAME => self::DISABLE_INTERNATIONAL_PERMANENT_WHATSAPP_TEMPLATE_NAME,
            self::WHATSAPP_TEMPLATE      => self::DISABLE_INTERNATIONAL_PERMANENT_WHATSAPP_TEMPLATE,
        ],
    ];

    // similar constants kept for backward compatibility. Cron to be removed later after 100% feature rollout
    const MERCHANT_RISK_ACTIONS_CRON_TAG_TEMPLATE_MAP = [
        self::MERCHANT_RISK_SUSPEND_CRON_TAG => [
            self::SMS_TEMPLATE           => self::SUSPEND_ACCOUNT_SMS_TEMPLATE,
            self::WHATSAPP_TEMPLATE_NAME => self::SUSPEND_ACCOUNT_WHATSAPP_TEMPLATE_NAME,
            self::WHATSAPP_TEMPLATE      => self::SUSPEND_ACCOUNT_WHATSAPP_TEMPLATE,
            self::DASHBOARD_TEMPLATE_TAG => "",
        ],

        self::MERCHANT_RISK_FOH_CRON_TAG => [
           self::SMS_TEMPLATE           => self::FOH_SMS_TEMPLATE,
           self::WHATSAPP_TEMPLATE_NAME => self::FOH_WHATSAPP_TEMPLATE_NAME,
           self::WHATSAPP_TEMPLATE      => self::FOH_WHATSAPP_TEMPLATE,
           self::DASHBOARD_TEMPLATE_TAG => self::FOH_DASHBOARD_TEMPLATE_TAG,
        ],

        self::MERCHANT_RISK_DISABLE_LIVE_CRON_TAG => [
           self::SMS_TEMPLATE           => self::DISABLE_LIVE_SMS_TEMPLATE,
           self::WHATSAPP_TEMPLATE_NAME => self::DISABLE_LIVE_WHATSAPP_TEMPLATE_NAME,
           self::WHATSAPP_TEMPLATE      => self::DISABLE_LIVE_WHATSAPP_TEMPLATE,
           self::DASHBOARD_TEMPLATE_TAG => self::DISABLE_LIVE_DASHBOARD_TEMPLATE_TAG,
        ],
    ];

    const FD_SUB_CATEGORY_FUNDS_ON_HOLD      = 'Funds on hold';

    const LINKED_ACCOUNT_PENNY_TESTING = 'linked_account_penny_testing';

    const CA_STATUS_MAP = [
        'created'       => 'Request received',
        'picked'        => 'Process started',
        'initiated'     => 'BANK KYC in progress',
        'cancelled'     => 'Request Cancelled',
        'unserviceable' => 'Unserviceable',
        'rejected'      => 'Request Rejected',
        'activated'     => 'Active',
        'archived'      => 'On hold',
        'processed'     => 'Activation In Progress'
    ];

    const RISK_CONSTRUCTIVE_ACTION_LIST = [
        Action::UNSUSPEND,
        Action::LIVE_ENABLE,
        Action::RELEASE_FUNDS,
    ];

    const USE_WORKFLOWS = 'use_workflows';

    const MERCHANT_TYPE_DIRECT_SALES      = 'Direct Sales';

    const MERCHANT_TYPE_KAM               = 'KAM';

    const PRESTO_QUERY_FIND_MERCHANT_TYPE = "select owner_role__c from hive.batch_sheets.poc_mapping_sheet where merchant_id__c = '%s'";

    // Merchant Payments failure analysis related constants
    const CUSTOMER        = 'customer';
    const GATEWAY         = 'gateway';
    const INTERNAL        = 'internal';
    const BUSINESS        = 'business';
    const ISSUER_BANK     = 'issuer_bank';
    const NETWORK         = 'network';
    const ISSUER           = 'issuer';
    const CUSTOMER_PSP     = 'customer_psp';
    const BENEFICIARY_BANK = 'beneficiary_bank';
    const PROVIDER        = 'provider';

    const CUSTOMER_DROP_OFF   = 'customer_dropp_off';
    const BANK_FAILURE        = 'bank_failure';
    const BUSINESS_FAILURE    = 'business_failure';
    const OTHER_FAILURE       = 'other_failure';
    const FAILURE_DETAILS     = 'failure_details';
    const SUMMARY             = 'summary';

    const NUMBER_OF_TOTAL_PAYMENTS      = 'number_of_total_payments';
    const NUMBER_OF_SUCCESSFUL_PAYMENTS = 'number_of_successful_payments';

    const QUERY_EXECUTION_TIME           = 'query_execution_time';
    const FAILURE_ANALYSIS_FOR_TIME_RANGE = 'failure_analysis_for_time_range';

    const BULK_WORKFLOW_ACTION_ID = 'bulk_workflow_action_id';

    const FRAUD_TYPE_TAG_TPL      = '%s_tag';

    // Mapping from payment error source to failure category
    const ERROR_SOURCE_CATEGORY = [
        self::GATEWAY          => self::BANK_FAILURE,
        self::BANK             => self::BANK_FAILURE,
        self::ISSUER_BANK      => self::BANK_FAILURE,
        self::NETWORK          => self::BANK_FAILURE,
        self::CUSTOMER_PSP     => self::BANK_FAILURE,
        self::ISSUER           => self::BANK_FAILURE,
        self::BENEFICIARY_BANK => self::BANK_FAILURE,
        self::CUSTOMER         => self::CUSTOMER_DROP_OFF,
        self::BUSINESS         => self::BUSINESS_FAILURE,
        self::MERCHANT         => self::BUSINESS_FAILURE,
        self::PROVIDER         => self::OTHER_FAILURE,
        self::INTERNAL         => self::OTHER_FAILURE,
    ];
}

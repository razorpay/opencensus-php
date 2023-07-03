<?php

namespace RZP\Models\Merchant;

use RZP\Models\Admin\ConfigKey;
use RZP\Models\BankingAccount\Channel;
use RZP\Models\Merchant\Detail\BusinessCategory;
use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Models\Merchant\Detail\Status as ActivationStatus;
use RZP\Models\Merchant\Document\Type;
use RZP\Models\Terminal\Category;
use RZP\Models\User\Role;

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
    const BANK_ACCOUNT_ID                         = 'bank_account_id';
    const PARAMS                                  = 'params';
    const IS_CTA_TEMPLATE                         = 'is_cta_template';
    const BUTTON_URL_PARAM                        = 'button_url_param';
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
    const IS_USED                                 = 'is_used';
    const DATA                                    = 'data';
    const PARENT_NAME                             = 'parent_name';

    // Partner constants
    const BANK_CA_ONBOARDING_PARTNER              = 'bank_ca_onboarding_partner';
    const BANK                                    = 'bank';
    const PARTNER                                 = 'partner';
    const RESELLER                                = 'reseller';
    const AGGREGATOR                              = 'aggregator';
    const FULLY_MANAGED                           = 'fully_managed';
    const PURE_PLATFORM                           = 'pure_platform';
    const PARTNER_INTENT                          = 'partner_intent';
    const TRANSLATE_WEBHOOK_GATEWAY               = 'translate_webhook_gateway';

    const CAPITAL_CORPORATE_CARD_PARTNERSHIP_TAG_PREFIX = 'capital-cc-submerchant-';
    const CAPITAL_LOC_PARTNERSHIP_TAG_PREFIX            = 'capital-loc-submerchant-';
    const PARTNER_REFERRAL_TAG_PREFIX                   = 'ref-';
    const CAPITAL_CORPORATE_CARD_PRODUCT_NAME           = 'CARDS';
    const CAPITAL_LOC_EMI_PRODUCT_NAME                  = 'LOC_EMI';
    const CREATE_CAPITAL_APPLICATION_LOS_URL            = "twirp/rzp.capital.los.origination.v1.ApplicationAPI/CreateApplicationNew";
    const GET_PRODUCTS_LOS_URL                          = "twirp/rzp.capital.los.admin.v1.ProductAPI/GetProducts";
    const GET_CAPITAL_APPLICATIONS_BULK_URL             = "twirp/rzp.capital.los.origination.v1.ApplicationAPI/GetApplicationsForPartnerBulk";
    const GET_CAPITAL_APPLICATIONS_URL                  = "twirp/rzp.capital.los.origination.v1.ApplicationAPI/GetApplicationsByParam";


    // Capital LOS CreateApplicationRequestNew payload fields
    const TENURE                                  = "tenure";
    const LEAD_SOURCE                             = "lead_source";
    const LEAD_SOURCE_ID                          = "lead_source_id";
    const SOURCE_DETAILS                          = "source_details";
    const PRODUCT_ID                              = "product_id";

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
    const SUBM_CREATE_ENTITIES_LOCK_TIME_OUT      = 600; //seconds
    const MERCHANT_ONBOARDING                     = 'merchant_onboarding';
    const SOURCE_DASHBOARD                        = 'dashboard';

    const AGGREGATOR_TO_RESELLER_UPDATE               = "aggregator_to_reseller_update";
    const AGGREGATOR_TO_RESELLER_UPDATE_LOCK_TIME_OUT = 30; //seconds
    const RESELLER_TO_AGGREGATOR_UPDATE               = "reseller_to_aggregator_update";
    const RESELLER_TO_AGGREGATOR_UPDATE_LOCK_TIME_OUT = 30; //seconds

    const RESELLER_TO_PURE_PLATFORM_MIGRATE               = "reseller_to_pure_platform_migrate";
    const RESELLER_TO_PURE_PLATFORM_MIGRATE_LOCK_TIME_OUT = 30; //seconds

    //Time stamp is of Tuesday, 18 October 2022 00:00:01
    const QR_RECEIVER_CREATATION_STOP_TIME_STAMP = 1666051201;
    // Used in partners flows
    const APPLICATION_ID                          = 'application_id';
    const APP_TYPE                                = 'app_type';
    const PUBLIC_KEY                              = 'public_key';

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

    const SPLITZ_LIVE                             = 'live';
    const SPLITZ_PILOT                            = 'pilot';
    const SPLITZ_KQU                              = 'kqu';

    const MERCHANT_MUTEX_LOCK_TIMEOUT                 = '60';
    const MERCHANT_MUTEX_RETRY_COUNT                  = '2';

    const SUCCESS                                 = 'Success';
    const FAILURE                                 = 'Failure';

    // This are added to remove the email_id being tagged in the slack
    const DASHBOARD_INTERNAL                      = 'DASHBOARD_INTERNAL';
    const MERCHANT_USER                           = 'MERCHANT_USER';

    const USER_CONTACT_MOBILE                     = 'user_contact_mobile';
    const CONTACT_INFO                            = 'contact_info';

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

    const WEBSITE_NAME                                  = 'website_name';
    const COMMENT                                       = 'comment';
    const NEW_TRANSACTION_LIMIT_BY_MERCHANT             = 'new_transaction_limit_by_merchant';
    const TRANSACTION_TYPE                              = 'transaction_type';
    const TRANSACTION_LIMIT_INCREASE_REASON             = 'transaction_limit_increase_reason';
    const TRANSACTION_LIMIT_INCREASE_INVOICE_URL        = 'transaction_limit_increase_invoice_url';
    const INCREASE_TRANSACTION_LIMIT                    = 'increase_transaction_limit';
    const INCREASE_INTERNATIONAL_TRANSACTION_LIMIT      = 'increase_international_transaction_limit';
    const ENABLE_NON_3DS_PROCESSING                     = 'enable_non_3ds_processing';
    const UPDATED_TRANSACTION_LIMIT                     = 'updated_transaction_limit';
    const GSTIN_UPDATE_SELF_SERVE                       = 'gstin_update_self_serve';
    const OLD_GSTIN                                     = 'old_gstin';
    const NEW_GSTIN                                     = 'new_gstin';
    const TRANSACTION_TYPE_INTERNATIONAL                = 'international';
    const TRANSACTION_TYPE_DOMESTIC                     = 'domestic';
    const OAUTH                                         = 'Oauth';
    const PARTNERSHIP                                   = 'Partnership';
    const PARTNER_TYPE_SWITCH                           = 'Partner_Type_Switch';

    const RAZORPAY_PRIVACY_POLICY_URL                   = 'https://razorpay.com/privacy/';
    const RAZORPAY_CA_TERMS_OF_USE                      = 'https://razorpay.com/x/terms/razorpayx/';
    const RAZORPAY_TERMS_OF_USE                         = 'https://razorpay.com/x/terms/';

    const RAZORPAY_PARTNERSHIP_TERMS             = 'https://razorpay.com/s/terms/partners/';
    const RAZORPAY_PARTNERSHIP_OAUTH_TERMS       = 'https://razorpay.com/terms/razorpayx/partnership/';

    const RAZORPAY_PARTNER_AUTH_TERMS            = 'https://razorpay.com/terms/razorpayx/partnership/';

    const RAZORPAY_LINE_OF_CREDIT_SIGN_UP        = 'https://razorpay.com/x/line-of-credit';

    const INCREASE_TRANSACTION_LIMIT_POST_WORKFLOW_APPROVE          = 'RZP\Http\Controllers\MerchantController@postTransactionLimitWorkflowApprove';

    const TRANSACTION_LIMIT_INCREASE_REASON_COMMENT                 = 'Transaction Limit Increase Reason: %s';
    const TRANSACTION_LIMIT_INCREASE_SUPPORT_DOCUMENT_URL_COMMENT   = 'Support Document (Invoice) URL: %sadmin/entity/ufh.files/live/file_%s';

    const DATA_LAKE_FETCH_POPULAR_PRODUCTS_ACRROSS_MERCHANT_QUERY = "SELECT product FROM hive.aggregate_pa.payments_product WHERE method!='transfer' AND created_at >= %s AND created_at <= %s GROUP BY product ORDER BY COUNT(*) DESC";
    const MERCHANT_POPULAR_PRODUCTS_CACHE_KEY                     = "care_service_merchant_popular_products";
    const MERCHANT_POPULAR_PRODUCTS_CRON_LAST_RUN_AT_KEY          = 'merchant_popular_products_cron_last_run_at';

    const PAYMENTS_ENABLED_AND_FREQUENTLY_TRANSACTED = 'payments_enabled_and_frequently_transacted';
    const PAYMENTS_ENABLED_AND_TRANSACTED            = 'payments_enabled_and_transacted';
    const PAYMENTS_ENABLED_AND_NOT_TRANSACTED        = 'payments_enabled_and_not_transacted';
    const PAYMENTS_NOT_ENABLED                       = 'payments_not_enabled';
    const PAYMENT_HANDLE                             = 'payment_handle';
    const ONBOARDING_CARD                            = 'onboarding_card';
    const ACCEPT_PAYMENTS                            = 'accept_payments';
    const SETTLEMENTS                                = 'settlements';
    const RECENT_TRANSACTIONS                        = 'recent_transactions';
    const PAYMENT_ANALYTICS                          = 'payment_analytics';
    const PRIORITY                                   = 'priority';
    const PRODUCTS                                   = 'products';
    const PROPS                                      = 'props';
    const PRODUCT                                    = 'product';
    const FTUX_COMPLETE                              = 'ftux_complete';
    const INTRODUCING                                = 'introducing';
    const USER_ROLES                                 = 'user_roles';
    const SEGMENT_TYPE                               = 'segment_type';
    const WIDGETS                                    = 'widgets';
    const ERROR                                      = 'error';
    const CODE                                       = 'code';
    const DESCRIPTION                                = 'description';
    const TITLE                                      = 'title';
    const VARIANT                                    = 'variant';
    const QR_CODE                                    = 'qr_code';
    const SUBSCRIPTIONS                              = 'subscriptions';
    const PAYMENT_BUTTON                             = 'payment_button';
    const TAP_AND_PAY                                = 'tap_and_pay';
    const PAYMENT_PAGES                              = 'payment_pages';
    const PAYMENT_LINK                               = 'payment_link';
    const PAYMENT_GATEWAY                            = 'payment_gateway';
    const IS_NEW_PRODUCT                             = 'is_new_product';
    const SYNC_FLOW                                  = 'sync_flow';
    const WORKFLOW_CREATED                           = 'workflow_created';
    const SESSION_COUNT_PREFIX                       = 'session_count_prefix';
    const MERCHANT_SEGMENT_TRANSACTION_COUNT         = 'merchant_segment_transaction_count';
    const TOTAL_TRANSACTIONS_IN_LAST_MONTH_TTL       = 60 * 60 * 24 * 2; //two days

    const SUBMERCHANT_FIRST_TRANSACTION_CRON_CACHE_KEY = 'submerchant_first_transaction_timestamp';

    const AUTO_UPDATE_MERCHANT_PRODUCTS = 'worker:auto_update_merchant_products';

    const DAILY_TRANSACTED_SUBMERCHANTS_JOB_PAGE_SIZE  = 100;
    const DAILY_TRANSACTED_SUBMERCHANTS_LIMIT          = 100000;
    const DAILY_TRANSACTED_SUBMERCHANTS_BATCH_SIZE     = 10;
    const DEFAULT_LAST_CRON_SUB_DAYS                   = 1;

    //Merchant tags
    const CAP_ES_0_DMT30           = 'CAP_ES_0_DMT30';
    const CAP_ES_0_XCA             = 'CAP_ES_0_XCA';
    const CAP_ES_0_PP              = 'CAP_ES_0_PP';
    const CAP_ES_0_ENTPG           = 'CAP_ES_0_ENTPG';
    const CAP_ES_0_SMEPG           = 'CAP_ES_0_SMEPG';
    const CAP_ES_0_OTHER           = 'CAP_ES_0_OTHER';
    const CAP_ES_STD_SC            = 'CAP_ES_STD_SC';
    const CAP_ES_STD_OD            = 'CAP_ES_STD_OD';
    const CAP_ES_STD_BOTH          = 'CAP_ES_STD_BOTH';
    const CAP_CA_FUNGIBLE_TO_PITCH = 'CAP_CA_FUNGIBLE_TO_PITCH';
    const CAP_CA_FUNGIBLE_ON_HOLD  = 'CAP_CA_FUNGIBLE_ON_HOLD';

    // Constant for total lead score
    const TOTAL_LEAD_SCORE = 'total_lead_score';


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
        self::BANK_CA_ONBOARDING_PARTNER
    ];

    public static $defaultFeaturesToPropagate = [
      'sourced_by_walnut369'
    ];

    //Capital Tags assigned to merchants
    public static $capitalMerchantTags = [
        self::CAP_ES_0_DMT30,
        self::CAP_ES_0_XCA,
        self::CAP_ES_0_PP,
        self::CAP_ES_0_ENTPG,
        self::CAP_ES_0_SMEPG,
        self::CAP_ES_0_OTHER,
        self::CAP_ES_STD_SC,
        self::CAP_ES_STD_OD,
        self::CAP_ES_STD_BOTH,
        self::CAP_CA_FUNGIBLE_ON_HOLD,
        self::CAP_CA_FUNGIBLE_TO_PITCH
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
    const TOGGLE_INTERNATIONAL_REVAMPED = 'toggle_international_revamped';

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

    const MASTERCARD = 'mastercard';
    const VISA       = 'visa';

    const MERCHANT_WORKFLOW_CLARIFICATION                =  "merchant_workflow_clarification";
    const WORKFLOW_CLARIFICATION_DOCUMENTS_IDS           =  "clarification_documents_ids";
    const UFH_FILE_URL                                   = "%sadmin/entity/ufh.files/live/file_%s ,  ";
    const MERCHANT_WORKFLOW_CLARIFICATION_FILES_PREFIX   = 'Files shared by merchant: ';

    const ENABLE_NON_3DS_WORKFLOW_APPROVE          = 'RZP\Http\Controllers\MerchantController@postEnableNon3dsWorkflowApprove';

    const ROLE                      = 'role';
    const ROLE_ID                   = 'role_id';
    const ROLE_NAME                 = 'role_name';
    const USERS                     = 'users';

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

    const listOfNetworksSupportedOn3ds2 = [
        self::MASTERCARD,
        self::VISA,
    ];

    const MERCHANT_ONBOARD_ON_NETWORK_METRO_TOPIC = 'merchant_onboarding_networks';

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
     * Partner types that are allowed to have referral links
     *
     * @var array
     */
    public static $referralPartnerTypes = [
        self::RESELLER,
        self::AGGREGATOR,
        self::FULLY_MANAGED,
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

    const EDUCATION_CATEGORIES = [
        '8211',
        '8220',
        '8241',
        '8244',
        '8249',
        '8299'
    ];

    const BFSI_MERCHANT_CATEGORIES = [
        '6211',
        '7322',
        '5960',
        '6300',
        '6529'
    ];

    const LINKED_ACCOUNT_ACTIONS_BLOCKED = [
        Entity::CATEGORY     => ['6211', '6012',],
        Entity::CATEGORY2    => [Category::MUTUAL_FUNDS, Category::LENDING,]
    ];

    const AUTO_CREATE_AMC_LINKED_ACCOUNT_MCC = [
        Entity::CATEGORY     => '6211',
        Entity::CATEGORY2    => Category::MUTUAL_FUNDS
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
        self::TOGGLE_INTERNATIONAL_REVAMPED => [
            self::PERMISSION => Permission::TOGGLE_INTERNATIONAL_REVAMPED,
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
        self::INCREASE_INTERNATIONAL_TRANSACTION_LIMIT   => [
            self::PERMISSION   => Permission::INCREASE_INTERNATIONAL_TRANSACTION_LIMIT,
            self::ENTITY       => \RZP\Constants\Entity::MERCHANT,
        ],
        self::GSTIN_UPDATE_SELF_SERVE      => [
            self::PERMISSION  => Permission::UPDATE_MERCHANT_GSTIN_DETAIL,
            self::ENTITY      => \RZP\Constants\Entity::MERCHANT_DETAIL,
        ],
        self::ENABLE_NON_3DS_PROCESSING =>[
            self::PERMISSION   => Permission::ENABLE_NON_3DS_PROCESSING,
            self::ENTITY       => \RZP\Constants\Entity::MERCHANT,
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

    const MERCHANT_RISK_SCORE_DATA_PINOT_QUERY  = 'SELECT * FROM pinot.risk_scoring_fact WHERE merchants_id = \'%s\'';

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

    const TAGS              = 'tags';
    const WITHOUT_TAGS      = 'without_tags';

    // Bank LMS tag names
    const ENABLE_RBL_LMS_DASHBOARD = 'ENABLE_RBL_LMS_DASHBOARD';

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


    //Debit note created
    const DEBIT_NOTE_CREATE_EMAIL_SIGNUP_SMS_TEMPLATE            = 'sms.risk.debit_note_email_signup';
    const DEBIT_NOTE_CREATE_EMAIL_SIGNUP_EMAIL_TEMPLATE          = 'emails.merchant.risk.debit_note.created';
    const DEBIT_NOTE_CREATE_EMAIL_SIGNUP_EMAIL_SUBJECT           = 'Regarding your commercial debit note - {merchant_id}';
    const DEBIT_NOTE_CREATE_EMAIL_SIGNUP_WHATSAPP_TEMPLATE_NAME  = 'whatsapp_risk_debit_note_email_signup';
    const DEBIT_NOTE_CREATE_EMAIL_SIGNUP_WHATSAPP_TEMPLATE       = 'Hi {merchant_name}, we wish to notify you regarding pending recoveries on your Razorpay Account. We request you to transfer the due amount to our Nodal account. Please check registered email for more details.';

    const DEBIT_NOTE_CREATE_MOBILE_SIGNUP_SMS_TEMPLATE           = 'sms.risk.debit_note_mobile_signup';
    const DEBIT_NOTE_CREATE_MOBILE_SIGNUP_EMAIL_TEMPLATE         = 'emails.merchant.risk.debit_note.created';
    const DEBIT_NOTE_CREATE_MOBILE_SIGNUP_EMAIL_SUBJECT          = 'Regarding your commercial debit note - {merchant_id}';
    const DEBIT_NOTE_CREATE_MOBILE_SIGNUP_WHATSAPP_TEMPLATE_NAME = 'whatsapp_risk_debit_note_mobile_signup';
    const DEBIT_NOTE_CREATE_MOBILE_SIGNUP_WHATSAPP_TEMPLATE      = 'Hi {merchant_name}, we wish to notify you regarding pending recoveries on your Razorpay Account. We request you to transfer the due amount to our Nodal account. Please check link {supportTicketLink} for more details.';

    //Disable International Mobile Signup Templates
    const DISABLE_INTERNATIONAL_SMS_TEMPLATE_MOBILE_SIGNUP           = 'sms.risk.international_disablement_mobile_signup';
    const DISABLE_INTERNATIONAL_WHATSAPP_TEMPLATE_NAME_MOBILE_SIGNUP = 'whatsapp_risk_international_disablement_email_signup';
    const DISABLE_INTERNATIONAL_WHATSAPP_TEMPLATE_MOBILE_SIGNUP      = 'Hi {merchantName}, we regret to inform you that acceptance of international payments has been paused due to a risk alert raised by our banking partners. Please check link {supportTicketLink} for more details';

    // WDA migration Experiments
    const WDA_MIGRATION_ACQUISITION_SPLITZ_EXP_ID = 'wda_migration_acquisition_splitz_exp_id';

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


        Action::DEBIT_NOTE_CREATE_MOBILE_SIGNUP => [
            self::SMS_TEMPLATE           => self::DEBIT_NOTE_CREATE_MOBILE_SIGNUP_SMS_TEMPLATE,
            self::EMAIL_TEMPLATE         => self::DEBIT_NOTE_CREATE_MOBILE_SIGNUP_EMAIL_TEMPLATE,
            self::EMAIL_SUBJECT          => self::DEBIT_NOTE_CREATE_MOBILE_SIGNUP_EMAIL_SUBJECT,
            self::WHATSAPP_TEMPLATE_NAME => self::DEBIT_NOTE_CREATE_MOBILE_SIGNUP_WHATSAPP_TEMPLATE_NAME,
            self::WHATSAPP_TEMPLATE      => self::DEBIT_NOTE_CREATE_MOBILE_SIGNUP_WHATSAPP_TEMPLATE,
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

        Action::DEBIT_NOTE_CREATE_EMAIL_SIGNUP => [
            self::SMS_TEMPLATE           => self::DEBIT_NOTE_CREATE_EMAIL_SIGNUP_SMS_TEMPLATE,
            self::EMAIL_TEMPLATE         => self::DEBIT_NOTE_CREATE_EMAIL_SIGNUP_EMAIL_TEMPLATE,
            self::EMAIL_SUBJECT          => self::DEBIT_NOTE_CREATE_EMAIL_SIGNUP_EMAIL_SUBJECT,
            self::WHATSAPP_TEMPLATE_NAME => self::DEBIT_NOTE_CREATE_EMAIL_SIGNUP_WHATSAPP_TEMPLATE_NAME,
            self::WHATSAPP_TEMPLATE      => self::DEBIT_NOTE_CREATE_EMAIL_SIGNUP_WHATSAPP_TEMPLATE,
        ],

        Action::DEBIT_NOTE_CREATE_MOBILE_SIGNUP => [
            self::SMS_TEMPLATE           => self::DEBIT_NOTE_CREATE_MOBILE_SIGNUP_SMS_TEMPLATE,
            self::EMAIL_TEMPLATE         => self::DEBIT_NOTE_CREATE_MOBILE_SIGNUP_EMAIL_TEMPLATE,
            self::EMAIL_SUBJECT          => self::DEBIT_NOTE_CREATE_MOBILE_SIGNUP_EMAIL_SUBJECT,
            self::WHATSAPP_TEMPLATE_NAME => self::DEBIT_NOTE_CREATE_MOBILE_SIGNUP_WHATSAPP_TEMPLATE_NAME,
            self::WHATSAPP_TEMPLATE      => self::DEBIT_NOTE_CREATE_MOBILE_SIGNUP_WHATSAPP_TEMPLATE,
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
    const FD_SUB_CATEGORY_DISABLE_LIVE       = 'Disable live';
    const FD_SUB_CATEGORY_SUSPEND            = 'Suspended Merchants';


    const FD_SUB_CATEGORY = [
        Action::HOLD_FUNDS                      => self::FD_SUB_CATEGORY_FUNDS_ON_HOLD,
        Action::LIVE_DISABLE                    => self::FD_SUB_CATEGORY_DISABLE_LIVE,
        Action::SUSPEND                         => self::FD_SUB_CATEGORY_SUSPEND,
    ];

    const LINKED_ACCOUNT_PENNY_TESTING = 'linked_account_penny_testing';

    const CA_STATUS_MAP = [
        self::DEFAULT => 'Application not initiated',

        Channel::RBL => [
            'created'       => 'Application completion pending',
            'picked'        => 'Razorpay processing',
            'initiated'     => 'Documents pick up pending',
            'processing'    => 'Account opening in progress',
            'processed'     => 'Account opened',
            'activated'     => 'Account activated',
            'cancelled'     => 'Request Cancelled',
            'unserviceable' => 'Unserviceable',
            'rejected'      => 'Request Rejected',
            'archived'      => 'On hold'
        ],

         Channel::ICICI => [
             'created'                   => 'Application completion pending',
             'user_submitted'            => 'Telephonic verification',
             'sent_to_bank'              => 'Documents pick up pending',
             'bank_processing'           => 'Account opening in progress',
             'account_opened'            => 'Account opened',
             'registration_request_sent' => 'Registration Request Sent',
             'account_activated'         => 'Account activated',
             'archived'                  => 'On hold',
             'rejected'                  => 'Request Rejected',
             'cancelled'                 => 'Request Cancelled',
             'unserviceable'             => 'Unserviceable'
         ]
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

    const DEFAULT_ERROR_DESCRIPTION = 'There was an issue with the payment request.';

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

    // Smart Dashboard Merchant Details Constants.
    const MERCHANT_DETAILS  = 'Merchant Details';
    const WEBSITE_DETAILS   = 'Website and App Details';
    const DOCUMENTS         = 'Documents';
    const ADDITIONAL_DETAILS = 'Additional Details';
    const SUBCATEGORY       = 'subcategory';
    const MERCHANT_CATEGORY = 'Merchant Category';
    const MERCHANTNAME      = 'Merchant Name';
    const COMPANY_CIN       = 'Company CIN';
    const COMPANY_PAN       = 'Company Pan';
    const BUSINESS_DOE      = 'Business Date of Establishment';
    const CONTACT_NUMBER    = 'Contact Number';
    const BUSINESSTYPE      = 'Business Type';
    const CONTACT_NAME      = 'Contact Name';
    const CONTACT_EMAIL     = 'Contact Email';
    const GSTIN_NUMBER      = 'GSTIN Number';
    const AVG_ORDER_VALUE   = 'Avg Order Value';
    const WEBSITE_LINK      = 'Website Link';
    const PRICING_POLICY    = 'Pricing Policy';
    const TERMS             = 'Terms & Conditions';
    const PRIVACY_POLICY    = 'Privacy Policy';
    const TERMS_OF_USE      = 'Terms of Use';
    const WEBSITEDETAILS    = 'Website Details';
    const REFUND_POLICY     = 'Refund Policy';
    const CANCELLATION_POLICY   = 'Cancellation Policy';
    const PLAYSTORE_URL     = 'Playstore URL';
    const APPSTORE_URL      = 'Appstore URL';
    const WEBSITE           = 'website';
    CONST PG_USE_CASE       = 'pg_use_case';

    const BUSINESS_DESCRIPTION = 'Business Description';
    const BUSINESS_MODEL       = 'Business Model';
    const BUSINESS_OPERATION_ADDRESS  = 'Business Operation Address';
    const BUSINESS_REGISTERED_ADDRESS = 'Business Registered Address';
    const FIELDS            = 'fields';
    const NAME              = 'name';
    const VALUE             = 'value';

    // static properties for different widgets and products for app scalability
    const APP_SCALABILITY_CONFIG_STATIC_PROPS = [

        self::PAYMENT_HANDLE => [
            self::VARIANT     => 'variantA',
            self::DESCRIPTION => 'Share your Razorpay.me link to get paid instantly',
            self::TITLE       => 'Payments Handle',
        ],

        self::ONBOARDING_CARD => [
            self::VARIANT     => 'variantA',
            self::DESCRIPTION => '',
            self::TITLE       => 'Onboarding Card',
        ],

        self::ACCEPT_PAYMENTS => [
            self::VARIANT     => 'variantA',
            self::DESCRIPTION => '',
            self::TITLE       => 'Accept Payments',
        ],

        self::SETTLEMENTS => [
            self::VARIANT     => 'variantA',
            self::DESCRIPTION => 'We have transferred all payments received to your bank',
            self::TITLE       => 'Settlements',
        ],

        self::PAYMENT_ANALYTICS => [
            self::VARIANT     => 'variantA',
            self::DESCRIPTION => '',
            self::TITLE       => 'Payment Insights',
        ],

        self::RECENT_TRANSACTIONS => [
            self::VARIANT     => 'variantA',
            self::DESCRIPTION => '',
            self::TITLE       => 'Recent Transactions',
        ],

        self::PAYMENT_LINK => [
            self::TYPE           => 'payment_link',
            self::TITLE          => 'Payment Link',
            self::DESCRIPTION    => 'Create a link and send it to your customers to accept payments',
            self::IS_NEW_PRODUCT => false
        ],

        self::PAYMENT_GATEWAY => [
            self::TYPE           => 'payment_gateway',
            self::TITLE          => 'Payment Gateway',
            self::DESCRIPTION    => 'Integrate a Gateway to your website through seamless API integration',
            self::IS_NEW_PRODUCT => false
        ],

        self::TAP_AND_PAY => [
            self::TYPE           => 'tap_and_pay',
            self::TITLE          => 'Tap & Pay',
            self::DESCRIPTION    => 'Accept card payments by swiping your card on your NFC enabled phone',
            self::IS_NEW_PRODUCT => false
        ],

        self::PAYMENT_PAGES => [
            self::TYPE           => 'payment_pages',
            self::TITLE          => 'Payment Pages',
            self::DESCRIPTION    => 'Create your custom branded page',
            self::IS_NEW_PRODUCT => false
        ],

        self::QR_CODE => [
            self::TYPE           => 'qr_code',
            self::TITLE          => 'QR',
            self::DESCRIPTION    => 'Generate QR codes on the go',
            self::IS_NEW_PRODUCT => false
        ],

        self::SUBSCRIPTIONS => [
            self::TYPE           => 'subscriptions',
            self::TITLE          => 'Subscriptions',
            self::DESCRIPTION    => 'Create custom subscription plans to collect recurring payments from customers automatically',
            self::IS_NEW_PRODUCT => true
        ],

        self::PAYMENT_BUTTON => [
            self::TYPE           => 'payment_button',
            self::TITLE          => 'Payment button',
            self::DESCRIPTION    => 'Add a quick checkout button on your website/app for one-time or recurring payments',
            self::IS_NEW_PRODUCT => true
        ],
    ];

    const VALID_ROLES_FOR_SUBSCRIPTIONS = [
        Role::OWNER,
        Role::ADMIN,
        Role::MANAGER,
        Role::OPERATIONS,
        Role::FINANCE,
        Role::SUPPORT,
    ];

    const VALID_ACTIVATION_STATUS_FOR_SUBSCRIPTIONS = [
        Detail\Status::ACTIVATED,
        Detail\Status::ACTIVATED_MCC_PENDING,
        Detail\Status::INSTANTLY_ACTIVATED,
    ];

    const VALID_ROLES_FOR_PAYMENT_BUTTON = [
        Role::OWNER,
        Role::ADMIN,
        Role::MANAGER,
        Role::OPERATIONS,
        Role::FINANCE,
        Role::SELLERAPP,
    ];

    const VALID_ACTIVATION_STATUS_FOR_PAYMENT_BUTTON = [
        Detail\Status::ACTIVATED,
        Detail\Status::ACTIVATED_MCC_PENDING,
        Detail\Status::INSTANTLY_ACTIVATED,
    ];

    // Mapping is used for smart dashboard merchant details.
    const SMART_DASHBOARD_MERCHANT_DETAILS_MAP = [
        self::MERCHANT_DETAILS => [
            [
                self::SUBCATEGORY => self::BUSINESSTYPE,
                self::FIELDS      => [
                    'merchant_details|business_type',
                ]
            ],
            [
                self::SUBCATEGORY => self::MERCHANT_CATEGORY,
                self::FIELDS      => [
                    'merchant|category',
                    'merchant|category2',
                    'merchant_details|business_category',
                    'merchant_details|business_subcategory',
                ]
            ],
            [
                self::SUBCATEGORY => self::BUSINESS_DESCRIPTION,
                self::FIELDS      => [
                    'merchant_details|business_description',
                ]
            ],
            [
                self::SUBCATEGORY => self::BUSINESS_MODEL,
                self::FIELDS      => [
                    'merchant_details|business_model',
                ]
            ],
            [
                self::SUBCATEGORY => self::MERCHANTNAME,
                self::FIELDS      => [
                    'merchant|billing_label',
                    'merchant_details|business_dba',
                ]
            ],
            [
                self::SUBCATEGORY => self::COMPANY_CIN,
                self::FIELDS      => [
                    'merchant_details|company_cin',
                ]
            ],
            [
                self::SUBCATEGORY => self::COMPANY_PAN,
                self::FIELDS      => [
                    'merchant_details|company_pan_name',
                    'merchant_details|company_pan',
                ]
            ],
            [
                self::SUBCATEGORY => self::BUSINESS_OPERATION_ADDRESS,
                self::FIELDS      => [
                    'merchant_details|business_operation_address',
                    'merchant_details|business_operation_address_l2',
                    'merchant_details|business_operation_country',
                    'merchant_details|business_operation_state',
                    'merchant_details|business_operation_city',
                    'merchant_details|business_operation_district',
                    'merchant_details|business_operation_pin',
                ]
            ],
            [
                self::SUBCATEGORY => 'Owner PAN Number',
                self::FIELDS      => [
                    'merchant_details|promoter_pan',
                ]
            ],
            [
                self::SUBCATEGORY => self::CONTACT_NAME,
                self::FIELDS      => [
                    'merchant_details|contact_name',
                ]
            ],
            [
                self::SUBCATEGORY => self::CONTACT_NUMBER,
                self::FIELDS      => [
                    'merchant_details|contact_mobile',
                    'merchant_details|contact_landline',
                ]
            ],
            [
                self::SUBCATEGORY => self::CONTACT_EMAIL,
                self::FIELDS      => [
                    'merchant_details|contact_email',
                ]
            ],
            [
                self::SUBCATEGORY => self::BUSINESS_REGISTERED_ADDRESS,
                self::FIELDS      => [
                    'merchant_details|business_registered_address',
                    'merchant_details|business_registered_address_l2',
                    'merchant_details|business_registered_country',
                    'merchant_details|business_registered_state',
                    'merchant_details|business_registered_city',
                    'merchant_details|business_registered_district',
                    'merchant_details|business_registered_pin',
                ]
            ],
            [
                self::SUBCATEGORY => self::GSTIN_NUMBER,
                self::FIELDS      => [
                    'merchant_details|gstin',
                ]
            ],
            [
                self::SUBCATEGORY => 'Authorised Signatory Residential Address',
                self::FIELDS      => [
                    'merchant_details|authorized_signatory_residential_address',
                ]
            ],
            [
                self::SUBCATEGORY => 'Authorised Signatory Date of Birth',
                self::FIELDS      => [
                    'merchant_details|authorized_signatory_dob',
                ]
            ],
            [
                self::SUBCATEGORY => self::BUSINESS_DOE,
                self::FIELDS      => [
                    'merchant_details|business_doe',
                ]
            ],
            [
                self::SUBCATEGORY => self::AVG_ORDER_VALUE,
                self::FIELDS      => [
                    'merchant_details|avg_order_min',
                ]
            ],
            [
                self::SUBCATEGORY => self::AVG_ORDER_VALUE,
                self::FIELDS      => [
                    'merchant_details|avg_order_max',
                ]
            ],
            [
                self::SUBCATEGORY => self::PG_USE_CASE,
                self::FIELDS      => [
                    'merchant_business_detail|pg_use_case',
                ]
            ],
        ],
        self::WEBSITE_DETAILS => [
            [
                self::SUBCATEGORY => self::WEBSITE_LINK,
                self::FIELDS      => [
                    'merchant|website',
                    'merchant_details|business_website',
                ]
            ],
            [
                self::SUBCATEGORY => self::PRICING_POLICY,
                self::FIELDS      => [
                    'merchant_details|website_pricing',
                    'merchant_business_detail|website_details|pricing',
                ]
            ],
            [
                self::SUBCATEGORY => self::TERMS,
                self::FIELDS      => [
                    'merchant_details|website_terms',
                    'merchant_business_detail|website_details|terms',
                ]
            ],
            [
                self::SUBCATEGORY => self::PRIVACY_POLICY,
                self::FIELDS      => [
                    'merchant_details|website_privacy',
                    'merchant_business_detail|website_details|privacy',
                ]
            ],
            [
                self::SUBCATEGORY => self::WEBSITEDETAILS,
                self::FIELDS      => [
                    'merchant_details|website_contact',
                    'merchant_business_detail|website_details|contact',
                    'merchant_details|website_about',
                    'merchant_business_detail|website_details|about',
                ]
            ],
            [
                self::SUBCATEGORY => self::REFUND_POLICY,
                self::FIELDS      => [
                    'merchant_details|website_refund',
                    'merchant_business_detail|website_details|refund',
                ]
            ],
            [
                self::SUBCATEGORY => self::CANCELLATION_POLICY,
                self::FIELDS      => [
                    'merchant_business_detail|website_details|cancellation',
                ]
            ],
            [
                self::SUBCATEGORY => self::PLAYSTORE_URL,
                self::FIELDS      => [
                    'merchant_details|playstore_url',
                    'merchant_business_detail|app_urls|playstore_url',
                ]
            ],
            [
                self::SUBCATEGORY => self::APPSTORE_URL,
                self::FIELDS      => [
                    'merchant_details|appstore_url',
                    'merchant_business_detail|app_urls|appstore_url',
                ]
            ],
        ],
        self::DOCUMENTS => [
            [
                self::SUBCATEGORY => Type::SEBI_REGISTRATION_CERTIFICATE,
                self::FIELDS      => [
                    'documents|sebi_registration_certificate',
                ]
            ],
            [
                self::SUBCATEGORY => Type::IRDAI_REGISTRATION_CERTIFICATE,
                self::FIELDS      => [
                    'documents|irdai_registration_certificate',
                ]
            ],
            [
                self::SUBCATEGORY => Type::FFMC_LICENSE,
                self::FIELDS      => [
                    'documents|ffmc_license',
                ]
            ],
            [
                self::SUBCATEGORY => Type::NBFC_REGISTRATION_CERTIFICATE,
                self::FIELDS      => [
                    'documents|nbfc_registration_certificate',
                ]
            ],
            [
                self::SUBCATEGORY => Type::AMFI_CERTIFICATE,
                self::FIELDS      => [
                    'documents|amfi_certificate',
                ]
            ],
            [
                self::SUBCATEGORY => Type::SLA_SEBI_REGISTRATION_CERTIFICATE,
                self::FIELDS      => [
                    'documents|sla_sebi_registration_certificate',
                ]
            ],
            [
                self::SUBCATEGORY => Type::SLA_IRDAI_REGISTRATION_CERTIFICATE,
                self::FIELDS      => [
                    'documents|sla_irdai_registration_certificate',
                ]
            ],
            [
                self::SUBCATEGORY => Type::SLA_FFMC_LICENSE,
                self::FIELDS      => [
                    'documents|sla_ffmc_license',
                ]
            ],
            [
                self::SUBCATEGORY => Type::SLA_NBFC_REGISTRATION_CERTIFICATE,
                self::FIELDS      => [
                    'documents|sla_nbfc_registration_certificate',
                ]
            ],
            [
                self::SUBCATEGORY => Type::SLA_AMFI_CERTIFICATE,
                self::FIELDS      => [
                    'documents|sla_amfi_certificate',
                ]
            ],
            [
                self::SUBCATEGORY => Type::SLA_IATA_CERTIFICATE,
                self::FIELDS      => [
                    'documents|sla_iata_certificate',
                ]
            ],
            [
                self::SUBCATEGORY => Type::AFFILIATION_CERTIFICATE,
                self::FIELDS      => [
                    'documents|affiliation_certificate',
                ]
            ],
            [
                self::SUBCATEGORY => Type::IATA_CERTIFICATE,
                self::FIELDS      => [
                    'documents|iata_certificate',
                ]
            ],
            [
                self::SUBCATEGORY => Type::PPI_LICENSE,
                self::FIELDS      => [
                    'documents|ppi_license',
                ]
            ],
            [
                self::SUBCATEGORY => Type::DRIVER_LICENSE_FRONT,
                self::FIELDS      => [
                    'documents|driver_license_front',
                ]
            ],
            [
                self::SUBCATEGORY => Type::DRIVER_LICENSE_BACK,
                self::FIELDS      => [
                    'documents|driver_license_back',
                ]
            ],
            [
                self::SUBCATEGORY => Type::AADHAR_FRONT,
                self::FIELDS      => [
                    'documents|aadhar_front',
                ]
            ],
            [
                self::SUBCATEGORY => Type::AADHAR_BACK,
                self::FIELDS      => [
                    'documents|aadhar_back',
                ]
            ],
            [
                self::SUBCATEGORY => Type::AADHAR_ZIP,
                self::FIELDS      => [
                    'documents|aadhar_zip',
                ]
            ],
            [
                self::SUBCATEGORY => Type::AADHAR_XML,
                self::FIELDS      => [
                    'documents|aadhar_xml',
                ]
            ],
            [
                self::SUBCATEGORY => Type::PASSPORT_FRONT,
                self::FIELDS      => [
                    'documents|passport_front',
                ]
            ],
            [
                self::SUBCATEGORY => Type::PASSPORT_BACK,
                self::FIELDS      => [
                    'documents|passport_back',
                ]
            ],
            [
                self::SUBCATEGORY => Type::VOTER_ID_FRONT,
                self::FIELDS      => [
                    'documents|voter_id_front',
                ]
            ],
            [
                self::SUBCATEGORY => Type::VOTER_ID_BACK,
                self::FIELDS      => [
                    'documents|voter_id_back',
                ]
            ],
            [
                self::SUBCATEGORY => Type::CANCELLED_CHEQUE,
                self::FIELDS      => [
                    'documents|cancelled_cheque',
                ]
            ],
            [
                self::SUBCATEGORY => Type::BUSINESS_PROOF_URL,
                self::FIELDS      => [
                    'documents|business_proof_url',
                ]
            ],
            [
                self::SUBCATEGORY => Type::BUSINESS_OPERATION_PROOF_URL,
                self::FIELDS      => [
                    'documents|business_operation_proof_url',
                ]
            ],
            [
                self::SUBCATEGORY => Type::BUSINESS_PAN_URL,
                self::FIELDS      => [
                    'documents|business_pan_url',
                ]
            ],
            [
                self::SUBCATEGORY => Type::ADDRESS_PROOF_URL,
                self::FIELDS      => [
                    'documents|address_proof_url',
                ]
            ],
            [
                self::SUBCATEGORY => Type::PROMOTER_PROOF_URL,
                self::FIELDS      => [
                    'documents|promoter_proof_url',
                ]
            ],
            [
                self::SUBCATEGORY => Type::PROMOTER_PAN_URL,
                self::FIELDS      => [
                    'documents|promoter_pan_url',
                ]
            ],
            [
                self::SUBCATEGORY => Type::PROMOTER_ADDRESS_URL,
                self::FIELDS      => [
                    'documents|promoter_address_url',
                ]
            ],
            [
                self::SUBCATEGORY => Type::FORM_12A_URL,
                self::FIELDS      => [
                    'documents|form_12a_url',
                ]
            ],
            [
                self::SUBCATEGORY => Type::FORM_80G_URL,
                self::FIELDS      => [
                    'documents|form_80g_url',
                ]
            ],
            [
                self::SUBCATEGORY => Type::MEMORANDUM_OF_ASSOCIATION,
                self::FIELDS      => [
                    'documents|memorandum_of_association',
                ]
            ],
            [
                self::SUBCATEGORY => Type::ARTICLE_OF_ASSOCIATION,
                self::FIELDS      => [
                    'documents|article_of_association',
                ]
            ],
            [
                self::SUBCATEGORY => Type::BOARD_RESOLUTION,
                self::FIELDS      => [
                    'documents|board_resolution',
                ]
            ],
            [
                self::SUBCATEGORY => Type::PERSONAL_PAN,
                self::FIELDS      => [
                    'documents|personal_pan',
                ]
            ],
            [
                self::SUBCATEGORY => Type::SHOP_ESTABLISHMENT_CERTIFICATE,
                self::FIELDS      => [
                    'documents|shop_establishment_certificate',
                ]
            ],
            [
                self::SUBCATEGORY => Type::GST_CERTIFICATE,
                self::FIELDS      => [
                    'documents|gst_certificate',
                ]
            ],
            [
                self::SUBCATEGORY => Type::MSME_CERTIFICATE,
                self::FIELDS      => [
                    'documents|msme_certificate',
                ]
            ],
            [
                self::SUBCATEGORY => Type::BANK_STATEMENT,
                self::FIELDS      => [
                    'documents|bank_statement',
                ]
            ],
            [
                self::SUBCATEGORY => Type::FIRS_FILE,
                self::FIELDS      => [
                    'documents|firs_file',
                ]
            ],
            [
                self::SUBCATEGORY => Type::FIRS_ZIP,
                self::FIELDS      => [
                    'documents|firs_zip',
                ]
            ],
        ]
    ];

    // We are adding additional fields so as to make UI rendering easier for FE for raising pricing and custom discrepancies (in additional details tab on view applications page). In future, FE will be showing merchant's pricing plan in additional details tab
    const SMART_DASHBOARD_MERCHANT_DETAILS_MAP_ADDITIONAL_DETAILS = [
            [
                self::SUBCATEGORY => "Pricing",
                self::FIELDS =>[[
                    'name' => 'pricing',
                    'value' => 'pricing',
                    'editable' => false
                ]]
            ],
            [
                self::SUBCATEGORY => 'Custom',
                self::FIELDS => [[
                    'name' => 'custom',
                    'value' => 'custom',
                    'editable' => false
                ]]
            ]
    ];

    const immutableSmartDashboardMerchantDetailsFields = [
        'merchant|category',
        'merchant|category2',
        'merchant_details|business_category',
        'merchant_details|business_subcategory',
        'merchant|billing_label',
        'merchant_details|business_dba',
        'merchant_details|business_type',
    ];

    const RATIO_OF_TOTAL_TRANSACTION_FOR_PLUGIN = .05;

    const END_TIMESTAMP = 'end_timestamp';

    const RAZORPAY_DISPLAY_NAME = 'Razorpay Software Pvt Ltd';

    const DOMAINS_TO_BE_EXCLUDED = [
        'google',
        'gmail',
        'myshopify',
        'wixsite',
        'business',
        'youtube',
        'facebook',
        'myphoneshop',
        'instagram',
        'wordpress',
        'company',
        'shiprocket',
        'rzp',
        'graphy',
        '000webhostapp',
        'gostore',
        'weebly',
        'localhost',
        'nexterp',
        'epizy',
        'linker',
        'ybl',
        'razorpay',
        'paytm',
        'godaddysites',
        'catalog',
        'unaux',
        'wa',
        'websites',
        'youtu',
        'mydukaan',
        'wix',
        'bit',
        't',
        'ueniweb',
        'bikry',
        'ecwid',
        'bikayi',
        'myshopprime',
        'yahoo',
        'dotpe',
        'zohocommerce',
        'payunow',
        'website2',
        'ezyro',
        'spayee',
        'rf',
        'devourin',
        'webs',
        'indiamart',
        '1',
        'apple',
        'forms',
        'zoho',
        'myshopmatic',
        'limetray',
        'shoopy',
        'learnyst',
        'godirekt',
        'xceednet',
        'twitter',
        'whatsapp',
        'paypal',
        'secure-booking-engine',
        'whats',
        'amazon',
        'g',
        'webnode',
        'tmdigi',
        'github',
        'linkedin',
        'ongraphy',
        'site123',
        'justdial',
        'pythonanywhere',
        'myownshop',
        'blogger',
        'instamojo',
        'shop101',
        'www',
        'http',
        'https',
        'quickeselling',
        'shopify',
        'worldpranichealing',
        'fb',
        'getmeashop',
        'linktr',
        'nowfloats',
        'badabusiness',
        'mahitibazaar',
        'adminapp',
        'page',
        'gamil',
        'boomer',
        'yelo',
        'myomni',
        'myeasystore',
        'etsy',
        'newzenler',
        'email',
        'mydash101',
    ];

    const PHANTOM_ONBOARDING_FLOW_ENABLED = 'PHANTOM_ONBOARDING_FLOW_ENABLED';

    const PHANTOM_ONBOARDING              = 'isPhantomOnboarding';

    const PHANTOM_SIGNUP                  = 'phantom_signup';

    const PARTNER_ACCESS  = 'partner_access';
    const PARTNER_NAME    = 'partner_name';

    const PGOS  = 'pgos';
    const API   = 'api';
    const ACCOUNT_SUSPENDED_DUE_TO_PARENT_MERCHANT_SUSPENSION = 'account_suspended_due_to_parent_merchant_suspension';
}

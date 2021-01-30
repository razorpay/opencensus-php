<?php

namespace RZP\Models\Merchant;

use RZP\Models\Admin\Permission\Name as Permission;

/**
 * General constants for Merchant Model.
 */
final class Constants
{
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

    // Instant Refunds Pricing Fetch related constants
    const RULES                     = 'rules';
    const CUSTOM_PRICING            = 'custom_pricing';
    const MAX_RULES_TO_BE_DISPLAYED = 6;

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

    const ENABLE_INTERNATIONAL_PG      = 'enable_international_pg';
    const ENABLE_INTERNATIONAL_PROD_V2 = 'enable_international_prod_v2';

    //merchant risk constants
    const BRAND_LIST = 'brand_list';
    const BLACKLIST = 'blacklist';
    const HIGH_RISK_LIST = 'high_risk_list';
    const EXACT_MATCH = 'exact_match';
    const FUZZY_MATCH = 'fuzzy_match';
    const FUZZY_MATCH_THRESHOLD = 'FUZZY_MATCH_THRESHOLD';
    const MERCHANT_RISK_CLIENT_TYPE_ONBOARDING = 'onboarding';

    const INTERNATIONAL_WORKFLOW_LIST = [
        self::ENABLE_INTERNATIONAL_PG,
        self::ENABLE_INTERNATIONAL_PROD_V2,
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
        ]
    ];

    const MERCHANT_RISK_CONFIG = [
        Detail\Entity::PROMOTER_PAN => [
            'lists' => [
                 self::BLACKLIST,
            ],
            'config_key' => 'promoter_pan'
        ],
        Detail\Entity::COMPANY_PAN => [
            'lists' => [
                self::BLACKLIST,
            ],
            'config_key' => 'company_pan'
        ],
        Detail\Entity::COMPANY_CIN => [
            'lists' => [
                self::BLACKLIST,
            ],
            'config_key' => 'cin'
        ],
        Detail\Entity::GSTIN => [
            'lists' => [
                self::BLACKLIST,
            ],
            'config_key' => 'gstin'
        ],
        Detail\Entity::BANK_ACCOUNT_NUMBER => [
            'lists' => [
                self::BLACKLIST,
            ],
            'config_key' => 'bank_account_number'
        ],
        Detail\Entity::BANK_BRANCH_IFSC => [
            'lists' => [
                self::BLACKLIST,
            ],
            'config_key' => 'bank_branch_ifsc'
        ],
        Detail\Entity::CONTACT_MOBILE => [
            'lists' => [
                self::BLACKLIST,
            ],
            'config_key' => 'mobile'
        ],
        Detail\Entity::CONTACT_EMAIL => [
            'lists' => [
                self::BLACKLIST,
            ],
            'config_key' => 'email'
        ],
        Detail\Entity::BUSINESS_WEBSITE => [
            'lists' => [
                self::BLACKLIST,
                self::BRAND_LIST
            ],
            'config_key' => 'website'
        ],
        Detail\Entity::CONTACT_NAME => [
            'lists' => [
                self::BLACKLIST,
                self::BRAND_LIST,
                self::HIGH_RISK_LIST
            ],
            'config_key' => 'merchant_name'
        ],
        Detail\Entity::BUSINESS_DBA => [
            'lists' => [
                self::BLACKLIST,
                self::BRAND_LIST,
                self::HIGH_RISK_LIST
            ],
            'config_key' => 'billing_name'
        ],
    ];

    const MERCHANT_RISK_ACTIONS = [
        [
            'keysToCheck' => [
                Detail\Entity::PROMOTER_PAN => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
            'method' => 'lockFormDeactivate'
        ],
        [
            'keysToCheck' => [
                Detail\Entity::COMPANY_PAN => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
            'method' => 'lockFormDeactivate'
        ],
        [
            'keysToCheck' => [
                Detail\Entity::COMPANY_CIN => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
            'method' => 'lockFormDeactivate'
        ],
        [
            'keysToCheck' => [
                Detail\Entity::GSTIN => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
            'method' => 'lockFormDeactivate'
        ],
        [
            'keysToCheck' => [
                Detail\Entity::BANK_ACCOUNT_NUMBER => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH,
                ],
                Detail\Entity::BANK_BRANCH_IFSC => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
            'method' => 'lockFormDeactivate'
        ],
        [
            'keysToCheck' => [
                Detail\Entity::CONTACT_MOBILE => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
            'method' => 'lockFormDeactivate'
        ],
        [
            'keysToCheck' => [
                Detail\Entity::CONTACT_EMAIL => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
            'method' => 'lockFormDeactivate'
        ],
        [
            'keysToCheck' => [
                Detail\Entity::BUSINESS_WEBSITE => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
            'method' => 'lockFormDeactivate'
        ],
        [
            'keysToCheck' => [
                Detail\Entity::CONTACT_NAME => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH
                ]
            ],
            'method' => 'lockFormDeactivate'
        ],
        [
            'keysToCheck' => [
                Detail\Entity::BUSINESS_DBA => [
                    'list' => self::BLACKLIST,
                    'matchType'=> self::EXACT_MATCH
                ]
            ],
            'method' => 'lockFormDeactivate'
        ],
        [
            'keysToCheck' => [
                Detail\Entity::BUSINESS_WEBSITE => [
                    'list' => self::BRAND_LIST,
                    'matchType'=> self::EXACT_MATCH,
                ]
            ],
            'method' => 'regUnderReview'
        ],
        [
            'keysToCheck' => [
                Detail\Entity::CONTACT_NAME => [
                    'list' => self::HIGH_RISK_LIST,
                    'matchType'=> self::EXACT_MATCH
                ]
            ],
            'method' => 'regUnderReview'
        ],
        [
            'keysToCheck' => [
                Detail\Entity::BUSINESS_DBA => [
                    'list' => self::HIGH_RISK_LIST,
                    'matchType'=> self::EXACT_MATCH
                ]
            ],
            'method' => 'regUnderReview'
        ],
        [
            'keysToCheck' => [
                Detail\Entity::CONTACT_NAME => [
                    'list' => self::BRAND_LIST,
                    'matchType'=> self::FUZZY_MATCH
                ]
            ],
            'method' => 'regUnderReview'
        ],
        [
            'keysToCheck' => [
                Detail\Entity::BUSINESS_DBA => [
                    'list' => self::BRAND_LIST,
                    'matchType'=> self::FUZZY_MATCH
                ]
            ],
            'method' => 'regUnderReview'
        ]
    ];

    // No of days for manual KYC
    const MANUAL_KYC_DAYS_WHEN_AUTO_KYC_PASSED    =     "3 to 5";
    const MANUAL_KYC_DAYS_WHEN_AUTO_KYC_FAILED    =     "7";
}

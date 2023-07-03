<?php

namespace RZP\Models\Merchant\Detail;


use RZP\Models\Bank\IFSC;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Store\ConfigKey;
use RZP\Models\Admin\Org\Entity as ORG_ENTITY;
use RZP\Models\Merchant\Website\Entity as WebsiteEntity;
use RZP\Models\Merchant\Email\Entity as EmailEntity;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant as BVSConstants;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;

class Constants
{
    const LIVE_PRIMARY_BALANCE          = 'Live Primary Balance';
    const REJECTION_CATEGORY_REASONS    ='Rejection Category - Rejection Reasons';
    const REJECTION_OPTION             ='Rejection Option';

    const BANK_ACCOUNT_VERIFICATION_MAX_ATTEMPT_COUNT = 'BANK_ACCOUNT_VERIFICATION_MAX_ATTEMPT_COUNT';

    const MRS_PROFANITY_CHECKER_DEPTH = 1;

    const ADMIN  = 'admin';
    const SYSTEM = 'system';

    const OWNER = 'owner';

    const OLD_CONTACT_NUMBER = 'old_contact_number';
    const NEW_CONTACT_NUMBER = 'new_contact_number';

    const NEEDS_CLARIFICATION_SOURCES = [self::ADMIN, self::SYSTEM];
    //verification retry constants
    const RETRY_DELAY_IN_SECONDS = 300;

    // Input params for fetchMerchantAccountRecovery
    const EMAIL = 'email';
    const PHONE = 'phone';
    const PAN   = 'pan';

    // Input params for cin verification
    const SIGNATORY_DETAILS = 'signatory_details';
    const COMPANY_CIN       = 'company_cin';

    // input params for pan verifier
    const PAN_NUMBER        = 'pan_number';
    const PROMOTER_PAN      = 'promoter_pan';
    const PROMOTER_PAN_NAME = 'promoter_pan_name';
    const MEMBERS           = 'members';

    // input for business pan verifier
    const COMPANY_PAN      = 'company_pan';
    const BUSINESS_PAN     = 'business_pan';
    const COMPANY_PAN_NAME = 'company_pan_name';

    const POI_STATUS                       = 'poi_status';
    const POA_STATUS                       = 'poa_status';
    const GSTIN_STATUS                     = 'gstin_status';
    const CIN_STATUS                       = 'cin_status';
    const DOCUMENT_TYPE                    = 'document_type';
    const DOCUMENT_SOURCE                  = 'document_source';
    const BANK_DETAILS_VERIFICATION_STATUS = 'bank_details_verification_status';
    const COMPANY_PAN_VERIFICATION_STATUS  = 'company_pan_verification_status';
    const EXTERNAL_VERIFIER                = 'external_verifier';
    const BUSINESS_TYPE                    = 'business_type';
    const IS_RETRY                         = 'is_retry';

    // pan verifier response types
    const INCORRECT_DETAILS = 'incorrect_details';
    const SUCCESS           = 'success';
    const FAILURE           = 'failure';
    const INVALID           = 'invalid';

    //Terminal Procurement banner status
    const FAILED        = 'failed';
    const REJECTED      = 'rejected';
    const PENDING       = 'pending';
    const PENDING_SEEN  = 'pending_seen';
    const PENDING_ACK   = 'pending_ack';
    const NO_BANNER     = 'no_banner';

    const TOKEN = 'token';
    //token timeout duration in mins
    const TOKEN_TTL                    = 15;
    const COUPON_CODE_CACHE_KEY_PREFIX = 'COUPON_CODE_';

    const SIGNED_URL             = 'signed_url';
    const PASSPORT_FRONT         = 'passport_front';
    const AADHAR_FRONT           = 'aadhar_front';
    const VOTER_ID_FRONT         = 'voter_id_front';
    const AADHAAR_FRONT_COMPLETE = 'aadhaar_front_complete';

    const BUSINESS_TYPE_DISPLAY_NAME = 'business_type_display_name';
    const BUSINESS_TYPE_KEY = 'business_type_key';

    // Email constants
    const MERCHANT             = 'merchant';
    const PARTNER              = 'partner';
    const ORG                  = 'org';
    const HOSTNAME             = 'hostname';
    const PARTNER_EMAIL             = 'partner_email';
    const CLARIFICATION_REASON = 'clarification_reason';

    // penny testing constants
    const MERCHANT_ID                                   = 'merchant_id';
    const ACCOUNT_STATUS                                = 'account_status';
    const REGISTERED_NAME                               = 'registered_name';
    const PENNY_TESTING_FUZZY_MATCH_PERCENTAGE_WITH_PAN = 'fuzzy_match_percentage_with_pan';
    const PENNY_TESTING_FUZZY_MATCH_BASE                = 'penny_testing_fuzzy_match_base';
    const PENNY_TESTING_FUZZY_MATCH_TYPE_FOR_PAN        = 'penny_testing_fuzzy_match_type_for_pan';
    const PENNY_TESTING_FUZZY_MATCH_ATTRIBUTE_TYPE      = 'penny_testing_fuzzy_match_attribute_type';
    const POA_FUZZY_MATCH_TYPE                          = 'poa_fuzzy_match_type';
    const POI_FUZZY_MATCH_TYPE                          = 'poi_fuzzy_match_type';
    const BANK_VERIFICATION_THRESHOLD_FOR_PAN           = 'bank_detail_verification_threshold_for_pan';
    const IS_NAME_MATCHED                               = 'is_name_matched';
    const PENNY_TESTING_ATTEMPT_COUNT_REDIS_KEY_PREFIX  = 'penny_testing_attempt_count';
    const PENNY_TESTING_ATTEMPT_COUNT_TTL_IN_MIN        = 180;
    const PENNY_TESTING_ATTEMPT_COUNT_TTL_IN_SEC        = 10800;
    const PENNY_TESTING_MAX_ATTEMPT                     = 2;
    const PENNY_TESTING_RETRY_PERIOD_IN_SEC             = 3600;
    const UNREGISTERED                                  = 'unregistered';
    const PENNY_TESTING_REASON                          = 'penny_testing_reason';

    // penny testing reasons
    const PENNY_TESTING_REASON_ONBOARDING          = 'onboarding';
    const PENNY_TESTING_REASON_BANK_ACCOUNT_UPDATE = 'bank_account_update';

    // merchant verification
    const VERIFICATION             = 'verification';
    const VERIFICATION_ERROR_CODES = 'verification_error_codes';
    const REQUIRED_FIELDS          = 'required_fields';

    const DUMMY_ACTIVATION_FILE = '100000000Dummy';

    const LOCK_COMMON_FIELDS = 'lock_common_fields';

    // For Route no doc KYC feature.
    const UNREGISTERED_AND_PROPRIETORSHIP   = 'unregistered_and_proprietorship';
    const REGISTERED                        = 'registered';

    //kyc integration constants
    const ENTITY_ID              = 'entity_id';
    const KYC_ID                 = 'kyc_id';
    const STATUS_CODE            = 'status_code';
    const PAN_NAME_FROM_NSDL     = 'pan_name_from_nsdl';
    const INTERNAL_ERROR         = 'internal_error';
    const INTERNAL_ERROR_CODE    = 'internal_error_code';
    const INTERNAL_ERROR_MESSAGE = 'internal_error_message';
    const CODE                   = 'code';
    const MESSAGE                = 'message';
    const NAME                   = 'name';
    const DOCUMENTS              = 'documents';
    const CONTEXT                = 'context';
    const METHOD                 = 'method';
    const OCR_RESPONSE           = 'ocr_response';
    const VERIFICATION_RESULT    = 'verification_result';

    const ENTITY_NAME            = 'entity_name';
    const CASE_TYPE              = 'case_type';
    const CASE_TYPE_ACTIVATION   = 'activation';
    const CLARIFICATION_DATA     = 'clarification_data';
    const AGENT_ID               = 'agent_id';
    const AGENT_NAME             = 'agent_name';

    const EVENT_TYPE                                    = 'event_type';
    const CMMA_EVENT_NEEDS_CLARIFICATION                = 'case_needs_clarification';
    const CMMA_CASE_EVENTS_KAFKA_TOPIC_ENV_VARIABLE_KEY = 'CMMA_CASE_EVENTS_TOPIC_NAME';

    const ACTIVATION_FORM_SUBMISSION_EVENTS_KAFKA_TOPIC_ENV_VARIABLE_KEY = 'ACTIVATION_FORM_SUBMISSION_EVENTS_TOPIC_NAME';

    const CMMA_METRO_MIGRATE_OUT_EXPERIMENT_ID_KEY      = 'app.cmma_metro_migrate_out_experiment_id';
    const ENABLE                                        = 'enable';

    const OLD_ACTIVATION_DATA                           = 'old_activation_data';
    const UPDATED_ACTIVATION_DATA                       = 'updated_activation_data';

    const ACTIVATION_FORM_SUBMISSION_KAFKA               = 'activation_form_submission_kafka_event';

    const ONBOARDING_MILESTONE                           = 'onboarding_milestone';


    //gstin integration constants
    const COMPANY_NAME        = 'company_name';
    const LEGAL_NAME          = 'legal_name';
    const TRADE_NAME          = 'trade_name';
    const OPERATIONAL_ADDRESS = 'operational_address';
    const REGISTERED_ADDRESS  = 'registered_address';
    const ADDRESS             = 'address';

    // GSTIN update self serve flow related constants
    const GSTIN_SELF_SERVE_INPUT_CACHE_TTL        = 6 * 60 * 60; // 6 hours (in seconds)
    const GSTIN_SELF_SERVE_INPUT_CACHE_KEY_FORMAT = 'gstin_self_serve_input_%s';
    const GSTIN_SELF_SERVE_STATUS_NOT_STARTED     = 'not_started';
    const GSTIN_SELF_SERVE_STATUS_IN_PROGRESS     = 'in_progress';
    const GSTIN_SELF_SERVE_CERTIFICATE            = 'gstin_self_serve_certificate';
    const GSTIN_CERTIFICATE_FILE_ID               = 'gstin_certificate_file_id';
    const REJECTION_REASON                        = 'rejection_reason';
    const STATUS                                  = 'status';
    const IS_ADD_GSTIN_OPERATION                  = 'is_add_gstin_operation';
    const GSTIN_OPERATION                         = 'gstin_operation';
    const ADDED                                   = 'added';
    const UPDATED                                 = 'updated';
    const CACHE_KEY                               = 'cache_key';
    const CACHE_DATA                              = 'cache_data';

    const UPI                      = 'UPI';
    const CREATE                   = 'create';
    const ONLINE                   = 'online';
    const UPI_INSTRUMENT           = 'pg.upi.onboarding.online.upi';
    const EVENT_TYPE_ONBOARDING    = 'onboarding';

    const BLOCKED_GSTIN_LIST = [
        '29AAGCR4375J1ZU'
    ];

    const DOCUMENT_VERIFICATION_STATUS          = 'document_verification_status';
    const OCR_MATCHING_PERCENTAGE_WITH_PAN_NAME = 'ocr_match_percentage_with_pan_name';

    const WORKFLOW_MAKER_ADMIN_ID = 'workflow_maker_id';

    const DOCUMENT_TYPES = [
        self::PERSONAL_PAN    => 'personal_pan',
        self::BUSINESS_PAN    => 'business_pan',
        self::AADHAAR         => 'aadhaar',
        self::PASSPORT        => 'passport',
        self::VOTERS_ID       => 'voters_id',
        self::DRIVERS_LICENSE => 'drivers_license',
        self::GSTIN           => 'gstin',
        self::CIN             => 'cin',
    ];

    const BANK_DETAIL_FIELDS = [
        Entity::BANK_ACCOUNT_NAME,
        Entity::BANK_ACCOUNT_NUMBER,
        Entity::BANK_BRANCH_IFSC
    ];

    const SENSITIVE_FIELDS_FOR_LOGGING = [
        Entity::BANK_ACCOUNT_NUMBER,
        Entity::BANK_ACCOUNT_NAME,
        Entity::PROMOTER_PAN,
        Entity::COMPANY_PAN,
        Entity::PROMOTER_PAN_NAME,
        Entity::COMPANY_PAN_NAME
    ];

    const DOCUMENTS_LIST_FOR_NEEDS_CLARIFICATION_NOTIFICATION = [
        'Partnership deed in pdf format',
        'CA professional certificate',
        'Clarity on the business model and products/services offered by you',
        'Certificate of incorporation',
        'Website must be live with all the sections updated - About us, Contact us, Refund/Cancellation policy, Terms and Conditions, Privacy policy',
        'Business Proof: Kindly provide us all the pages of the Trust deed in pdf format',
        'Reseller agreement or bulk purchase invoice',
        'Other'
    ];

    // Using this blacked listed banks to block bank account details
    // update in merchant details
    const BLACKLISTED_BANKS = [];

    const KYC_API_TYPES = ['AUTH' => 'auth', 'OCR' => 'ocr'];

    const DOCUMENT_FILE_ID = 'document_file_id';

    // kyc service error codes
    const  VALIDATION_ERROR = 'VALIDATION_ERROR';
    const  NO_DATA_FOUND    = 'NO_DATA_FOUND';
    const  BAD_REQUEST      = 'BAD_REQUEST';
    const  UNAUTHORIZED     = 'UNAUTHORIZED';


    // kyc service processor type
    const PROCESSOR_TYPE = "processorType";
    const POI            = 'POI';
    const POA            = 'POA';
    const REGISTER       = 'REGISTER';
    const CIN            = 'CIN';
    const GSTIN          = 'GSTIN';

    // kyc service document type
    const PERSONAL_PAN    = 'PERSONAL_PAN';
    const AADHAAR         = 'AADHAAR';
    const PASSPORT        = 'PASSPORT';
    const VOTERS_ID       = 'VOTERS_ID';
    const DRIVERS_LICENSE = 'DRIVERS_LICENSE';

    // flows used during activation
    const ACTIVATION               = 'activaiton';
    const INTERNATIONAL_ACTIVATION = 'international_activation';

    const ACTIVATION_FLOWS = [self::ACTIVATION, self::INTERNATIONAL_ACTIVATION];

    // Gstin self serve related constants
    const GSTIN_UPDATE_SELF_SERVE_ROUTE_NAME          = 'merchant_gstin_self_serve_update';
    const GSTIN_UPDATE_SELF_SERVE_WORKFLOW_CONTROLLER = 'RZP\Http\Controllers\MerchantController@postGstinUpdateWorkflow';
    const GSTIN_CERTIFICATE_WORKFLOW_COMMENT          = 'GstIn Certificate : %sadmin/entity/ufh.files/live/file_%s';

    // Kyc Events constant
    const RESPONSE_BODY             = 'response_body';
    const RESPONSE_TIME             = 'response_time';
    const API_STATUS_CODE           = 'api_status_code';
    const VERIFIED                  = 'verified';
    const VERIFICATION_THRESHOLD    = 'verification_threshold';
    const API_CALL_SUCCESSFUL       = 'api_call_successful';
    const API_ERROR_CODE            = 'api_error_code';
    const VERIFICATION_STATUS       = 'verification_status';
    const COMPARISION               = 'comparisons';
    const MATCH_PERCENTAGE          = 'match_percentage';
    const MATCH_THRESHOLD           = 'match_threshold';
    const DETAILS_FROM_API_RESPONSE = 'detail_from_api';
    const DETAILS_FROM_USER         = 'detail_from_user';
    const MATCH_TYPE                = 'match_type';
    const NOT_AVAILABLE             = 'not_available';
    const NOT_VERIFIED              = 'not_verified';

    const SEARCH_STRING = 'search_string';

    const SPAM_DETECTED = 'SPAM_DETECTED';

    const L1_SUBMISSION = 'L1';
    const L2_SUBMISSION = 'L2';
    const X_SUBMISSION  = 'X';
    const RX            = 'rx';

    const INITIATED         = 'initiated';
    const DUMMY_REQUEST_ID  = 'KdsshbadDzab81';

    const CONSENT = 'consent';
    const DOCUMENTS_DETAIL = 'documents_detail';
    const URL = 'url';
    const TYPE = 'type';
    const IP_ADDRESS = 'ip_address';
    const USER_ID = 'user_id';
    const DOCUMENTS_ACCEPTANCE_TIMESTAMP = 'documents_acceptance_timestamp';

    /*
     * Allowed activation form milestones
     */
    const ALLOWED_MILESTONES = [
        self::L1_SUBMISSION,
        self::L2_SUBMISSION
    ];

    const ACTIVATION_ROUTE_NAME = 'merchant_activation_status';
    const ACTIVATION_CONTROLLER = 'RZP\Http\Controllers\MerchantController@updateActivationStatus';

    const  UPDATE_BUSINESS_WEBSITE_CONTROLLER = 'RZP\Http\Controllers\MerchantController@putBusinessWebsiteUpdatePostWorkflow';
    const  UPDATE_CONTACT_CONTROLLER          = 'RZP\Http\Controllers\MerchantController@putMerchantContactUpdatePostWorkflow';

    const ADD_ADDITIONAL_WEBSITE_CONTROLLER = 'RZP\Http\Controllers\MerchantController@putAddAdditionalWebsiteSelfServePostWorkflowApproval';

    const ADDITIONAL_WEBSITE_MAIN_PAGE       = 'additional_website_main_page';
    const ADDITIONAL_WEBSITE_ABOUT_US        = 'additional_website_about_us';
    const ADDITIONAL_WEBSITE_CONTACT_US      = 'additional_website_contact_us';
    const ADDITIONAL_WEBSITE_PRICING_DETAILS = 'additional_website_pricing_details';
    const ADDITIONAL_WEBSITE_PRIVACY_POLICY  = 'additional_website_privacy_policy';
    const ADDITIONAL_WEBSITE_TNC             = 'additional_website_tnc';
    const ADDITIONAL_WEBSITE_REFUND_POLICY   = 'additional_website_refund_policy';
    const ADDITIONAL_WEBSITE_TEST_USERNAME   = 'additional_website_test_username';
    const ADDITIONAL_WEBSITE_TEST_PASSWORD   = 'additional_website_test_password';
    const ADDITIONAL_WEBSITE_REASON          = 'additional_website_reason';
    const ADDITIONAL_WEBSITE_PROOF_URL       = 'additional_website_proof_url';

    const ADDITIONAL_DOMAIN_WHITELISTING_LIMIT  = 5;

    const DEDUPE_STATUS       = 'dedupe_status';
    const DEDUPE_FLAGGED_MIDS = 'dedupe_flagged_MIDs';
    const DEDUPE_STATUS_FALSE = 'Dedupe Status: false';

    const ADDITIONAL_APP_URL           = 'additional_app_url';
    const ADDITIONAL_APP_TEST_USERNAME = 'additional_app_test_username';
    const ADDITIONAL_APP_TEST_PASSWORD = 'additional_app_test_password';
    const ADDITIONAL_APP_REASON        = 'additional_app_reason';

    const COMMENT = 'comment';
    const INPUT   = 'input';

    const ADD_ADDITIONAL_WEBSITE_WORKFLOW_PAGES_COMMENT_STRUCTURE            = 'Main Page: %s, About Us Page: %s, Contact Us Page: %s, Pricing Details Page: %s, Privacy Policy Page: %s,Terms and Condition Page: %s, Refund Policy Page: %s';
    const ADD_ADDITIONAL_APP_WORKFLOW_PAGE_COMMENT_STRUCTURE                 = 'App URL: %s';
    const ADD_ADDITIONAL_WEBSITE_WORKFLOW_DEDUPE_COMMENT_STRUCTURE           = 'Dedupe Status: true, Dedupe Flagged MIDs: %s';
    const ADD_ADDITIONAL_WEBSITE_WORKFLOW_TEST_CREDENTIALS_COMMENT_STRUCTURE = 'Test Username: %s, Test Password: %s';
    const ADD_ADDITIONAL_WEBSITE_WORKFLOW_REASON_COMMENT_STRUCTURE           = 'Reason for adding: %s';
    const ADD_ADDITIONAL_WEBSITE_WORKFLOW_URL_COMMENT_STRUCTURE              = 'Domain Registration/Ownership Proof URL: %sadmin/entity/ufh.files/live/file_%s';

    const ENCRYPTED_ADDITIONAL_WEBSITE_CREDENTIALS_IDENTIFIER = 'additional_website_credentials_.';

    const COMPANY_SEARCH_ATTEMPT_COUNT_REDIS_KEY_PREFIX = 'company_search_attempt_count';
    const COMPANY_SEARCH_ATTEMPT_COUNT_TTL_IN_SEC       = 10800;
    const COMPANY_SEARCH_MAX_ATTEMPT                    = 30;

    const GET_GST_DETAILS_MAX_ATTEMPT                    = 30;
    const GET_PROMOTER_PAN_DETAILS_MAX_ATTEMPT           = 5;
    const GET_COMPANY_PAN_DETAILS_MAX_ATTEMPT            = 5;

    const ACCOUNT_PREFIX = "account_prefix";
    const IFSC_PREFIX    = "ifsc_prefix";

    const VIRTUAL_BANK_ACCOUNTS_PREFIX = [
        [
            self::ACCOUNT_PREFIX => "222333",
            self::IFSC_PREFIX    => IFSC::YESB
        ],
        [
            self::ACCOUNT_PREFIX => "787878",
            self::IFSC_PREFIX    => IFSC::YESB
        ],
        [
            self::ACCOUNT_PREFIX => "456456",
            self::IFSC_PREFIX    => IFSC::YESB
        ],
        [
            self::ACCOUNT_PREFIX => "2233",
            self::IFSC_PREFIX    => IFSC::ICIC
        ],
        [
            self::ACCOUNT_PREFIX => "2244",
            self::IFSC_PREFIX    => IFSC::ICIC
        ],
        [
            self::ACCOUNT_PREFIX => "5656",
            self::IFSC_PREFIX    => IFSC::ICIC
        ],
        [
            self::ACCOUNT_PREFIX => "3434",
            self::IFSC_PREFIX    => IFSC::ICIC
        ],
        [
            self::ACCOUNT_PREFIX => "2224",
            self::IFSC_PREFIX    => IFSC::RATN
        ],
        [
            self::ACCOUNT_PREFIX => "222333",
            self::IFSC_PREFIX    => IFSC::RATN
        ],
        [
            self::ACCOUNT_PREFIX => "2223",
            self::IFSC_PREFIX    => IFSC::RATN
        ],
        [
            self::ACCOUNT_PREFIX => "567890",
            self::IFSC_PREFIX    => IFSC::RATN
        ]
    ];

    const TNC_ORG_ID_EXP_MAP = [
        ORG_ENTITY::AXIS_ORG_ID     => RazorxTreatment::MERCHANT_TNC,
        ORG_ENTITY::RAZORPAY_ORG_ID => RazorxTreatment::RAZORPAY_TNC,
    ];

    const PUBLIC_TNC_DETAILS = [
        'websiteDetail'            => [
            WebsiteEntity::DELIVERABLE_TYPE,
            WebsiteEntity::SHIPPING_PERIOD,
            WebsiteEntity::REFUND_REQUEST_PERIOD,
            WebsiteEntity::REFUND_PROCESS_PERIOD,
            WebsiteEntity::WARRANTY_PERIOD,
            WebsiteEntity::UPDATED_AT,
        ],
        'merchantDetail' => [
            Entity::BUSINESS_NAME,
            Entity::BUSINESS_REGISTERED_ADDRESS,
            Entity::BUSINESS_REGISTERED_PIN,
            Entity::BUSINESS_REGISTERED_CITY,
            Entity::BUSINESS_REGISTERED_STATE,
            Entity::BUSINESS_CATEGORY,
            Entity::BUSINESS_SUBCATEGORY,
            Entity::BUSINESS_MODEL,
        ],
        'merchantEmail'  => [
            EmailEntity::EMAIL,
        ]
    ];

    const COMMON_FIELDS_WITH_PARTNER_ACTIVATION = [
        BusinessType::NOT_YET_REGISTERED => [
            Entity::CONTACT_NAME,
            Entity::CONTACT_MOBILE,
            Entity::CONTACT_EMAIL,
            Entity::BUSINESS_TYPE,
            Entity::BANK_ACCOUNT_NAME,
            Entity::BANK_ACCOUNT_NUMBER,
            Entity::BANK_BRANCH_IFSC,
            Entity::PROMOTER_PAN,
            Entity::PROMOTER_PAN_NAME
        ],
        BusinessType::PROPRIETORSHIP     => [
            Entity::CONTACT_NAME,
            Entity::CONTACT_MOBILE,
            Entity::CONTACT_EMAIL,
            Entity::BUSINESS_TYPE,
            Entity::BANK_ACCOUNT_NAME,
            Entity::BANK_ACCOUNT_NUMBER,
            Entity::BANK_BRANCH_IFSC,
            Entity::PROMOTER_PAN,
            Entity::PROMOTER_PAN_NAME,
            Entity::GSTIN
        ],
        MerchantConstants::DEFAULT       => [
            Entity::CONTACT_NAME,
            Entity::CONTACT_MOBILE,
            Entity::CONTACT_EMAIL,
            Entity::BUSINESS_TYPE,
            Entity::BANK_ACCOUNT_NAME,
            Entity::BANK_ACCOUNT_NUMBER,
            Entity::BANK_BRANCH_IFSC,
            Entity::COMPANY_PAN,
            Entity::BUSINESS_NAME,
            Entity::GSTIN
        ]
    ];

    // For both app url and business website url we use business_website key.
    // Following identifiers identifies type of url business_website/app_url.
    const URL_TYPE_APP = 'app';

    const URL_TYPE_WEBSITE = 'website';

    const MERCHANT_APP_URL_COMMENT = ' app_url : %s,        dedupe_flagged_MIDs : %s';

    const MERCHANT_BUSINESS_WEBSITE_COMMENT = ' main_page : %s,      about_us : %s,      contact_us : %s,        pricing_details : %s,       privacy_policy : %s,        tnc : %s,       refund_policy : %s,     dedupe_flagged_MIDs : %s';

    const MERCHANT_WEBSITE_TEST_CREDENTIAL_COMMENT = " website_username : %s,       website_username's_password : %s,";

    const MERCHANT_APP_TEST_CREDENTIAL_COMMENT = " app_username : %s,       app_username's_password : %s,";

    const BUSINESS_WEBSITE_MAIN_PAGE       = 'business_website_main_page';
    const BUSINESS_WEBSITE_ABOUT_US        = 'business_website_about_us';
    const BUSINESS_WEBSITE_CONTACT_US      = 'business_website_contact_us';
    const BUSINESS_WEBSITE_PRICING_DETAILS = 'business_website_pricing_details';
    const BUSINESS_WEBSITE_PRIVACY_POLICY  = 'business_website_privacy_policy';
    const BUSINESS_WEBSITE_TNC             = 'business_website_tnc';
    const BUSINESS_WEBSITE_REFUND_POLICY   = 'business_website_refund_policy';
    const BUSINESS_WEBSITE_USERNAME        = 'business_website_username';
    const BUSINESS_WEBSITE_PASSWORD        = 'business_website_password';

    const URL_TYPE = 'url_type';

    const VERIFICATION_TYPE = 'verification_type';

    const BUSINESS_APP_URL      = 'business_app_url';
    const BUSINESS_APP_USERNAME = 'business_app_username';
    const BUSINESS_APP_PASSWORD = 'business_app_password';

    //Encryper business website comment on workflow request start with following
    const ENCRYPTED_WEBSITE_DETAILS_IDENTIFIER = 'business_website_credentials_.';

    const KYC_FORM_SUBMIT_SEGMENT_PROPERTIES = [
        Entity::BUSINESS_TYPE,
        Entity::BUSINESS_CATEGORY,
        Entity::BUSINESS_SUBCATEGORY,
        Entity::BUSINESS_DBA,
        Entity::BUSINESS_NAME,
        Entity::BUSINESS_WEBSITE,
        Entity::BUSINESS_DOE,
        Entity::BUSINESS_MODEL,
        Entity::BUSINESS_OPERATION_STATE,
        Entity::BUSINESS_OPERATION_CITY,
        Entity::BUSINESS_OPERATION_PIN,
        Entity::BUSINESS_REGISTERED_STATE,
        Entity::BUSINESS_REGISTERED_CITY,
        Entity::BUSINESS_REGISTERED_PIN,
        Entity::PROMOTER_PAN,
        Entity::PROMOTER_PAN_NAME
    ];

    const SUPPORTED_VERIFICATION_RESPONSE_TYPES = [
        BVSConstants::AADHAAR,
        BVSConstants::BANK_ACCOUNT
    ];

    const VERIFICATION_RESPONSE_KEYS = [
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF      => Entity::POA_VERIFICATION_STATUS,
        BVSConstants::AADHAAR . BvsValidationConstants::IDENTIFIER => Entity::POA_VERIFICATION_STATUS,
    ];

    const VERIFICATION_RESPONSE_ERROR_CODES = [
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'Karza gateway request failed with http code - 504 error - Internal Server Error' => 'AADHAAR_KARZA_GATEWAY_TIMEOUT',
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'Karza gateway request failed with http code - 502 error - Internal Server Error' => 'AADHAAR_KARZA_BAD_GATEWAY',
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'Karza gateway request failed with http code - 503 error - Service Unavailable'   => 'AADHAAR_KARZA_SERVICE_UNAVAILABLE',
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'Karza gateway request failed with http code - 400 error - Bad Request'           => 'AADHAAR_KARZA_BAD_REQUEST',
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'Karza gateway request failed with http code - 500 error - Internal Server Error' => 'AADHAAR_KARZA_INTERNAL_ERROR',
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'bad request sent to karza'                                                       => 'AADHAAR_KARZA_BAD_REQUEST_SENT',
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'Karza gateway call failed with http code 400'                                    => 'AADHAAR_KARZA_BAD_REQUEST',
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'Karza gateway call failed with http code 502'                                    => 'AADHAAR_KARZA_BAD_GATEWAY',
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'Karza gateway call failed with http code 504'                                    => 'AADHAAR_KARZA_GATEWAY_TIMEOUT',
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'Karza gateway call failed with http code 500'                                    => 'AADHAAR_KARZA_INTERNAL_ERROR',
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'REQUEST_FAILED'                                                                  => 'AADHAAR_EXTERNAL_SERVICE_REQUEST_FAILED',
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'input document does not match  AadhaarBack document'                             => 'AADHAAR_BACK_NOT_MATCHED',
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'input document does not match  AadhaarFrontBottom document'                      => 'AADHAAR_FRONT_NOT_MATCHED',
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'input document is not a valid AadhaarFrontBottom document'                       => 'AADHAAR_FRONT_INVALID',
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'input document is not a valid AadhaarBack document'                              => 'AADHAAR_BACK_INVALID',
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'parameter 1 of equals is not string type - rule - 0 failed'                      => 'AADHAAR_NAME_MISMATCH',
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'parameter 2 in fuzzy_suzzy is not a string type - rule - 0 failed'               => 'AADHAAR_NUMBER_MISMATCH',
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'input image type and artefact type doesn\'t match'                               => 'AADHAAR_NOT_VALID',
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'hystrix: timeout'                                                                => 'AADHAAR_HYSTRIX_TIMEOUT',
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'name: cannot be blank.'                                                          => 'AADHAAR_NAME_MISMATCH',
        BVSConstants::AADHAAR . BvsValidationConstants::PROOF . 'invalid image submitted'                                                         => 'AADHAAR_NUMBER_MISMATCH',
        BVSConstants::BANK_ACCOUNT . BvsValidationConstants::IDENTIFIER . BvsValidationConstants::INPUT_DATA_ISSUE . 'KC03'                       => 'INVALID_BENEFICIARY_NUMBER_OR_IFSC',
        BVSConstants::BANK_ACCOUNT . BvsValidationConstants::IDENTIFIER . BvsValidationConstants::INPUT_DATA_ISSUE . 'KC05'                       => 'ACCOUNT_BLOCKED_OR_FROZEN',
        BVSConstants::BANK_ACCOUNT . BvsValidationConstants::IDENTIFIER . BvsValidationConstants::INPUT_DATA_ISSUE . 'KC06'                       => 'NRE_ACCOUNT',
        BVSConstants::BANK_ACCOUNT . BvsValidationConstants::IDENTIFIER . BvsValidationConstants::INPUT_DATA_ISSUE . 'KC07'                       => 'ACCOUNT_CLOSED',
        BVSConstants::BANK_ACCOUNT . BvsValidationConstants::IDENTIFIER . BvsValidationConstants::INPUT_DATA_ISSUE . 'KC27'                       => 'INVALID_ACCOUNT',
        BVSConstants::BANK_ACCOUNT . BvsValidationConstants::IDENTIFIER . BvsValidationConstants::INPUT_DATA_ISSUE . 'KC40'                       => 'INVALID_BENEFICIARY_IFSC_CODE_OR_NBIN',
        BVSConstants::BANK_ACCOUNT . BvsValidationConstants::IDENTIFIER . BvsValidationConstants::RULE_EXECUTION_FAILED                           => 'NOT_MATCHED',
    ];

    const LINKED_ACCOUNT_VERIFICATION_RESPONSE_ERROR_CODES = [
        BVSConstants::BANK_ACCOUNT . BvsValidationConstants::IDENTIFIER . BvsValidationConstants::SPAM_DETECTED_ERROR . 'max '                    => 'SPAM_DETECTED',
    ];

    const NO_DOC_ONBOARDING_DEDUPE_CHECK_FIELDS_REGISTERED = [
        ENTITY::CONTACT_MOBILE,
        ENTITY::COMPANY_PAN,
        ENTITY::BANK_ACCOUNT_NUMBER,
    ];

    const NO_DOC_ONBOARDING_DEDUPE_CHECK_FIELDS_UNREGISTERED = [
        ENTITY::CONTACT_MOBILE,
        ENTITY::PROMOTER_PAN,
        ENTITY::BANK_ACCOUNT_NUMBER,
    ];

    const NO_DOC_ONBOARDING_TAG = 'no_doc_onboarding';
    const ONBOARDING_SOURCE = 'onboarding_source';
    const XPRESS_ONBOARDING = 'xpress_onboarding';
    const RETRY_COUNT = 'retryCount';
    const DEDUPE = 'dedupe';
    const VALUE = 'value';
    const CURRENT_INDEX = 'current_index';
    const FAILURE_REASON_CODE = 'failure_reason_code';

    const DEDUPE_CHECK_KEY = 'dedupe_check_key';
    const DEDUPE_ES_INDEX = "merchant_v3_index";

    const NO_DOC_ONBOARDED_MERCHANT_DETAILS_TO_STORE_IN_DEDUPE = [
        Entity::ACTIVATION_STATUS,
        Entity::CONTACT_MOBILE,
        Entity::MERCHANT_ID,
        Entity::GSTIN,
        Entity::BANK_ACCOUNT_NUMBER,
        Entity::BANK_BRANCH_IFSC,
        Entity::COMPANY_PAN,
        Entity::CONTACT_EMAIL,
        Entity::PROMOTER_PAN,
        Entity::BUSINESS_TYPE,
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
        Entity::ARCHIVED_AT
    ];

    const CA_PAGES = [
        'razorpay.com/x/current-accounts/',
        'razorpay.com/x/',
        'razorpay.com/x/payout-links/',
        'razorpay.com/x/tax-payments/',
        'razorpay.com/x/mobile-app/',
        'razorpay.com/x/accounting-payouts/',
        'razorpay.com/x/accounting-payouts/quickbooks/',
        'razorpay.com/x/accounting-payouts/tally-payouts/',
        'razorpay.com/docs/x/',
        'razorpay.com/blog/category/business-banking/',
        'razorpay.com/x/tds-online-payment/',
        'razorpay.com/watch-banking/',
        'razorpay.com/current-account-for-startups/',
        'razorpay.com/x/neobank-report-for-smes-in-india/',
        'razorpay.com/x/for-yc-startups/',
        'razorpay.com/demo/'
    ];

    const POPULAR_SOCIAL_MEDIA = [
        '\bgoogle.com\b',
        '\bfacebook.com\b',
        '\bfb.com\b',
        '\binstagram.com\b',
        '\byoutube.com\b',
        '\btwitter.com\b',
        '\bpaytm.com\b',
        '\bphonepe.com\b',
        '\bpay.google.com\b',
        '\bbharatpe.com\b',
        '\brazorpay.com\b',
    ];

    const OWNER_NAME                    = 'owner_name';
    const SIGNATORY_NAME                = 'signatory_name';
    const CONTENT                       = 'content';
    const PARTNER_AUTH_CONSENT_TEMPLATE = "<html lang=\"en\">\n<head>\n<title>Document</title>\n</head>\n<body>\n<div>\n<h4>Allow {partnerName} to access your merchant account on Razorpay?</h4>\n<p>This will allow {partnerName} to take the following actions by using APIs &amp; dashboard</p>\n<div>\n<ul>\n<li>\n<h6>To create payment links and QR - codes</h6>\n</li>\n<li>\n<h6>To create Payment Via payment gateway</h6>\n</li>\n<li>\n<h6>Initiate Refunds</h6>\n</li>\n<li>\n<h6>Gain read only access to transaction, refund history, dispute flow</h6>\n</li>\n<li>\n<h6>Manage account end to end</h6>\n</li>\n</ul>\n</div>\n</div>\n</body>\n</html>";

    const FURTHER_QUERY_TEXT_DATA = [
        'MY' => [
            'type' => 'email',
            'email' => 'success@curlec.com'
        ],
        'IN' => [
            'type' => 'website',
            'website_link' => 'https://razorpay.com/support'
        ]
    ];

    const INPUT_WEBSITE_STATUS_UNDETERMINED_COUNT = 'input_website_status_undetermined_count';
    const UNDETERMINED                            = 'undetermined';
    const NO                                      = 'no';
    const YES                                     = 'yes';
}


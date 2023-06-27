<?php

namespace RZP\Models\User;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Environment;

class Constants
{
    const CTA           = 'cta';

    const PAGE          = 'page';

    const WEBSITE       = 'website';

    const FC_SOURCE     = 'fc_source';

    const LC_SOURCE     = 'lc_source';

    const UTM_SOURCE    = "utm_source";

    const UTM_CAMPAIGN  = "utm_campaign";

    const UTM_MEDIUM    = "utm_medium";

    const UTM_TERM      = "utm_term";

    const UTM_CONTENT   = "utm_content";

    const UTM_ADGROUP   = "utm_adgroup";

    const TIMESTAMP     = "timestamp";

    const ATTRIBUTIONS  = "attributions";

    const SET_PASSWORD  = "set_password";

    const SALESFORCE_EVENT  = "salesforce_event";

    const PERMISSIONS   = 'permissions';

    const BANNER_ID = 'banner_id';

    const BANNER_CLICKSOURCE = 'banner_clicksource';

    const BANNER_CLICKTIME = 'banner_clicktime';

    const MERCHANT = 'merchant';

    const METHOD                = 'method';
    const MEDIUM                = 'medium';
    const ACTION                = 'action';

    // Possible Login/Signup methods
    const PASSWORD                    = 'password';
    const OTP                         = 'otp';

    const RECEIVER                    = 'receiver';
    const UNIQUE_ID                   = 'unique_id';

    //when user does a normal or OTP login
    const EMAIL                       = 'email';
    const CONTACT_MOBILE              = 'contact_mobile';

    // oauth constants start
    const OAUTH_SOURCE = 'oauth_source';
    const CLIENT_ID    = 'client_id';
    const PAYLOAD      = 'payload';

    const IOS           = 'ios';
    const EPOS          = 'epos';
    const ANDROID       = 'android';
    const DASHBOARD     = 'dashboard';
    const X_ANDROID     = 'x_android';
    const X_IOS         = 'x_ios';

    const EMAIL_VERIFIED        = 'email_verified';
    const ID_TOKEN              = 'id_token';
    const INVALIDATE_SESSIONS   = 'invalidate_sessions';
    const BROWSER_DETAILS       = 'browser_details';

    const OAUTH_MERCHANT_OAUTH_CLIENT_ID            = 'oauth.merchant_oauth_client_id';
    const OAUTH_MERCHANT_OAUTH_CLIENT_ID_EPOS       = 'oauth.merchant_oauth_client_id_epos';
    const OAUTH_MERCHANT_OAUTH_CLIENT_ID_ANDROID    = 'oauth.merchant_oauth_client_id_android';
    const OAUTH_MERCHANT_OAUTH_CLIENT_ID_IOS        = 'oauth.merchant_oauth_client_id_ios';
    const OAUTH_MERCHANT_OAUTH_CLIENT_ID_X_ANDROID  = 'oauth.merchant_oauth_client_id_x_android';
    const OAUTH_MERCHANT_OAUTH_CLIENT_ID_X_IOS      = 'oauth.merchant_oauth_client_id_x_ios';
    const OAUTH_MERCHANT_OAUTH_MOCK                 = 'oauth.merchant_oauth_mock';

    const BULK_PAYOUT_APPROVE_TO_BULK_APPROVE_PAYOUT = 'bulk_payout_approve_to_bulk_approve_payout';

    // payout links constants
    const CONTACT                   = 'contact';
    const ACCOUNT_NUMBER            = 'account_number';
    const AMOUNT                    = 'amount';
    const CONFIG                    = 'config';

    // OTP Actions

    const CREATE_PAYOUT                                 = 'create_payout';
    const APPROVE_PAYOUT                                = 'approve_payout';
    const CREATE_PAYOUT_LINK                            = 'create_payout_link';

    const CREATE_COMPOSITE_PAYOUT_WITH_OTP              = 'create_composite_payout_with_otp';

    const IP_WHITELIST  =  'ip_whitelist';

    // oauth constants end

    const INCORRECT_LOGIN_TTL = 7200; // 2 hours

    // email otp login send ttl and threshold
    const EMAIL_LOGIN_OTP_SEND_TTL = 1800; // 30 mins
    const EMAIL_LOGIN_OTP_SEND_THRESHOLD = 5; // 5 times in 30 mins

    // login otp verification threshold
    const LOGIN_OTP_VERIFICATION_TTL = -1;
    const LOGIN_OTP_VERIFICATION_THRESHOLD = 9;

    // email verification otp send ttl and threshold
    const EMAIL_VERIFICATION_OTP_SEND_TTL = 1800; // 30 mins
    const EMAIL_VERIFICATION_OTP_SEND_THRESHOLD = 5; // 5 times in 30 mins

    // verification otp verification ttl and threshold
    const VERIFICATION_OTP_VERIFICATION_TTL = 1800; // 30 mins
    const VERIFICATION_OTP_VERIFICATION_THRESHOLD = 9; // 9 times in 30 mins
    const EMAIL_SIGNUP_OTP_SEND_TTL = 1800;//30 mins

    // no. of incorrect password allowed on 2fa with password
    const INCORRECT_LOGIN_2FA_PASSWORD_THRESHOLD_COUNT = 5;

    const INCORRECT_LOGIN_THRESHOLD_COUNT = 5;

    const INCORRECT_SIGNUP_THRESHOLD_COUNT = 5;

    const EMAIL_SIGNUP_OTP_SEND_THRESHOLD = 5;

    const VERIFY_SIGNUP_OTP_TTL = 7200; //120 mins

    const SIGNUP_OTP_VERIFICATION_THRESHOLD = 9;

    const CHANGE_PASSWORD_RATE_LIMIT_TTL = 1800; // 30 mins
    const CHANGE_PASSWORD_RATE_LIMIT_THRESHOLD = 5;

    const RESET_PASSWORD_RATE_LIMIT_THRESHOLD = 5;
    const RESET_PASSWORD_RATE_LIMIT_TTL = 7200;

    // bulk payouts v2 constants
    const CREATE_PAYOUT_BATCH = 'create_payout_batch';
    const CREATE_PAYOUT_BATCH_V2 = 'create_payout_batch_v2';
    const BATCH_FILE_ID = 'batch_file_id';


    public static $attributionList = [
        self::UTM_SOURCE,
        self::UTM_CAMPAIGN,
        self::UTM_MEDIUM,
        self::UTM_TERM,
        self::UTM_CONTENT,
        self::UTM_ADGROUP,
        self::TIMESTAMP,
    ];

    public static $utmDecider = [
        self::UTM_SOURCE,
        self::UTM_CAMPAIGN,
        self::UTM_MEDIUM,
    ];

    const GC_LID   = 'gclid';
    const FB_CLID  = 'fbclid';
    const MS_CLKID = 'msclkid';

    public static $clickIdentifier = [
        self::PAGE,
        self::GC_LID,
        self::FB_CLID,
        self::MS_CLKID
    ];

    const CA_STATIC_PAGE = 'razorpay.com/x/current-accounts/';

    const CAPITAL_LOC_SIGNUP_STATIC_PAGE = 'razorpay.com/x/line-of-credit/';

    const PASSWORD_RESET_TOKEN_EXPIRY_TIME =  86400; //24 hour

    const CO_CREATED_CREATE_PASSWORD_TOKEN_EXPIRY_TIME = 604800; //7 Days

    const LINKED_ACCOUNT_CREATE_PASSOWRD_TOKEN_EXPIRY_TIME =  86400; //24 hours

    const SUBMERCHANT_ACCOUNT_CREATE_PASSOWRD_TOKEN_EXPIRY_TIME =  7776000; //3 months

    const MAX_PASSWORD_TO_RETAIN                                = 5;

    const USER_EMAIL_NOT_FOUND  = 'USER_EMAIL_NOT_FOUND';

    const USER_CONTACT_MOBILE_NOT_FOUND  = 'USER_CONTACT_MOBILE_NOT_FOUND';

    const LOCK   = 'lock';
    const UNLOCK = 'unlock';
    const UN_VERIFY = 'un_verify';

    const ACTIVE = 'active';

    // This is a temporary hack to remove captcha on the below user emails.
    const WHITELIST_CAPTCHA_EMAILS = [
        "gaurav.morajkar@concentrix.com",
        "abdulr.s1@concentrix.com",
        "jayesh.poojary@concentrix.com",
        "ruchita.chamiyal@concentrix.com",
        "mohd.saud@concentrix.com",
        "faustina.maroli@concentrix.com",
        "sukanya.swami@concentrix.com",
        "regina.dsouza@concentrix.com",
        "ankit.dubbler@concentrix.com",
        "rahul.gilbert@concentrix.com",
        "prabjit.kaur@concentrix.com",
        "teekshan.sharma@concentrix.com",
        "gunjan.pruthi@concentrix.com",
        "taania.majumdar@concentrix.com",
        "varun.sharda@concentrix.com",
        "brian.brian@concentrix.com",
        "pinki.pinki11@concentrix.com",
        "muneshwar.rai@concentrix.com",
        "mitika.sharma@concentrix.com",
        "arun.kumar2@concentrix.com",
        "nitin.nitin2@concentrix.com",
        "shivkumar.maurya@concentrix.com",
        "vishranti.patil@concentrix.com",
        "singh.lokpati@concentrix.com",
        "mufaddalah.bohra@concentrix.com",
        "alax.sikam@concentrix.com",
        "synthiya.lakri@concentrix.com",
        "bharati.parab@concentrix.com",
        "remy.coutinho@concentrix.com",
        "priti.rokde@concentrix.com",
        "pratibha.puraya@concentrix.com",
        "tejal.chavda@concentrix.com",
        "prathamesh.navlu@concentrix.com",
        "rakhi.chamiyal@concentrix.com",
        "john.lobo@concentrix.com",
        "dharmistha.nakum@concentrix.com",
        "ravinder.singh5@concentrix.com",
        "sanjeev.vij@concentrix.com",
        "kanika.ahuja@concentrix.com",
        "gurleen.kaur@concentrix.com",
        "nameeta.luthra@concentrix.com",
        "liza.bansal@concentrix.com",
        "nidhi.nidhi1@concentrix.com",
        "sonam.s@concentrix.com",
        "aditi.sharma1@concentrix.com",
        "nikhil.k@concentrix.com",
        "jasmine.kaur1@concentrix.com",
        "nivea.garodi@concentrix.com",
        "bhupendra.kothari@concentrix.com",
        "meenakshi.ghade@concentrix.com",
        "shahbaz.sayyed@concentrix.com",
        "zahir.khan@concentrix.com",
        "nicholas.chetty@concentrix.com",
        "siddiq.hakam@concentrix.com",
        "misba.khan@concentrix.com",
        "sameer.ali1@concentrix.com",
        "aaman.khan@concentrix.com",
        "mohd.rizwan1@concentrix.com",
        "isabel.rodrigues@concentrix.com",
        "dheeraj.thakur@concentrix.com",
        "siddharth.sharma@concentrix.com",
        "shyana.palsara@concentrix.com",
        "vibha.vibha@concentrix.com",
        "harneet.bhogal@concentrix.com",
        "nitish.sharma2@concentrix.com",
        "aman.sharma9@concentrix.com",
        "anshul.gupta@concentrix.com",
        "rahul.r12@concentrix.com",
        "amit.kum5@concentrix.com",
        "aman.sharma7@concentrix.com",
        "sandeep.kaur@concentrix.com",
        "kamlesh.kaur@concentrix.com",
        "ruchi.verma1@concentrix.com",
        "jayansh.jayansh@concentrix.com",
        "anil.kumar17@concentrix.com",
        "harpreet.kaur9@concentrix.com",
        "diksha.gurung1@concentrix.com",
        "rahul.rahul9@concentrix.com",
        "vijay.kumar9@concentrix.com",
        "preeti.preeti7@concentrix.com",
        "ankit.chauhan@concentrix.com",
        "sagar.verma@concentrix.com",
        "suman.kumari@concentrix.com",
        "rudar.chauhan@concentrix.com",
        "veenu.veenu@concentrix.com",
        "suman.suman1@concentrix.com",
        "sunayna.sunayna@concentrix.com",
        "aman.sethi1@concentrix.com",
        "poonam.kumari2@concentrix.com",
        "mridula.mridula@concentrix.com",
        "hitesh.kumar1@concentrix.com",
        "heena.mehra@concentrix.com",
        "sweety.sweety@concentrix.com",
        "isha.kumari@concentrix.com",
        "deepti.mahajan@concentrix.com",
        "kimi.k@concentrix.com",
        "anuj.rana@concentrix.com",
        "namita.sharma@concentrix.com",
        "jyoti.verma@concentrix.com",
        "rajwinder.randhawa@concentrix.com",
        "coegcwkum@gmail.com",
        "alumni@srmist.edu.in",
        "jayaganp@srmist.edu.in",
        "satyen.doshi@hdfcsec.com",
        "qa+dashboard@razorpay.com",
        "qa+uiautomation@razorpay.com",
        "qa+rzp@razorpay.com",
        "hdbfinservices@gmail.com",
        "ramakishore.sankranthi@hdbfs.com",
        "reshma.sultana@hdbfs.com",
        "annapurna.pal+008@razorpay.com",
        "annapurna.pal+007@razorpay.com",
        "qa.testing+workflow@razorpay.com",
        "qa+uiautomation+1@razorpay.com",
        "qa+uiautomation+2@razorpay.com",
        Constants::BANKING_DEMO_USER_EMAILS[0],
        Constants::BANKING_DEMO_USER_EMAILS[1]
    ];

    const WHITELIST_CAPTCHA_CONTACT_MOBILE = [];

    // Only in these environments we will verify the captcha repsonse with google
    const WHITELIST_ENVIRONMENT_CAPTCHA_VALIDATION = [
        Environment::PRODUCTION,
        Environment::AXIS,

    ];

    const SEND_EMAIL_SIGNUP_OTP_RATE_LIMIT_SUFFIX   = "_signup_otp_send_count";
    const VERIFY_SIGNUP_OTP_RATE_LIMIT_SUFFIX       = "_signup_otp_verify_count";
    const SIGNUP_OTP_ACTION                         = 'signup_otp';
    const LOGIN_OTP_ACTION                          = 'login_otp';
    const VERIFY_USER_ACTION                        = 'verify_user';
    const VERIFY_SALESFORCE_USER_ACTION             = 'verify_salesforce_user';

    const CHANGE_PASSWORD_RATE_LIMIT_SUFFIX         = '_change_password_count';
    const SEND_EMAIL_LOGIN_OTP_RATE_LIMIT_SUFFIX    = '_login_otp_send_count';
    const TWO_FA_PASSWORD_RATE_LIMIT_SUFFIX         =  '_2fa_password_count';
    const VERIFY_LOGIN_OTP_RATE_LIMIT_SUFFIX        =  '_login_otp_verification_count';
    const VERIFY_OTP_VERIFICATION_RATE_LIMIT_SUFFIX = '_verification_otp_verification_count';
    const SEND_EMAIL_OTP_VERIFICATION_RATE_LIMIT_SUFFIX   = '_verification_otp_send_count';

    const THROTTLE_UPDATE_CONTACT_MOBILE_CACHE_KEY_PREFIX       = 'update_contact_mobile_attempts_%s';
    const THROTTLE_UPDATE_CONTACT_MOBILE_LIMIT                  = 3;
    const THROTTLE_UPDATE_CONTACT_MOBILE_SEND_OTP_PREFIX        = 'update_contact_mobile_send_otp_attempts:';
    const THROTTLE_UPDATE_CONTACT_MOBILE_SEND_OTP_LIMIT         = 9;
    const THROTTLE_UPDATE_CONTACT_MOBILE_SEND_OTP_LIMIT_TTL     = 1800;

    const RESET_PASSWORD_RATE_LIMIT_SUFFIX          = '_reset_password_count';

    //Email id used for banking demo mode
    const BANKING_DEMO_USER_EMAILS = [
        "razorpayx.demo@gmail.com",
        "acmecorporation2021@gmail.com"
    ];

    const RATE_LIMIT_LOGIN_SIGNUP_MAP = [
        self::SEND_EMAIL_SIGNUP_OTP_RATE_LIMIT_SUFFIX => [
            "thresholdTraceCode"        => TraceCode::EMAIL_SIGNUP_OTP_SEND_THRESHOLD_EXHAUSTED,
            "thresholdErrorCode"        => ErrorCode::BAD_REQUEST_EMAIL_SIGNUP_OTP_SEND_THRESHOLD_EXHAUSTED,
            "redisTraceCode"            => TraceCode::EMAIL_SIGNUP_OTP_REDIS_ERROR,
            "redisErrorCode"            => ErrorCode::SERVER_ERROR_EMAIL_SIGNUP_OTP_REDIS_ERROR,
            "redisErrorDescription"     => "An error occurred while interacting with redis on email otp signup route.",
        ],
        self::VERIFY_SIGNUP_OTP_RATE_LIMIT_SUFFIX => [
            "thresholdTraceCode"        => TraceCode::SIGNUP_OTP_VERIFICATION_THRESHOLD_EXHAUSTED,
            "thresholdErrorCode"        => ErrorCode::BAD_REQUEST_SIGNUP_OTP_VERIFICATION_THRESHOLD_EXHAUSTED,
            "redisTraceCode"            => TraceCode::VERIFY_SIGNUP_OTP_REDIS_ERROR,
            "redisErrorCode"            => ErrorCode::SERVER_ERROR_VERIFY_SIGNUP_OTP_REDIS_ERROR,
            "redisErrorDescription"     => "An error occurred while interacting with redis on signup otp verification route.",
        ],
        self::CHANGE_PASSWORD_RATE_LIMIT_SUFFIX => [
            "thresholdTraceCode"        => TraceCode::CHANGE_PASSWORD_THRESHOLD_EXHAUSTED,
            "thresholdErrorCode"        => ErrorCode::BAD_REQUEST_CHANGE_PASSWORD_THRESHOLD_EXHAUSTED,
            "redisTraceCode"            => TraceCode::CHANGE_PASSWORD_REDIS_ERROR,
            "redisErrorCode"            => ErrorCode::SERVER_ERROR_CHANGE_PASSWORD_REDIS_ERROR,
            "redisErrorDescription"     => "An error occurred while interacting with redis while changing the password.",
        ],
        self::SEND_EMAIL_LOGIN_OTP_RATE_LIMIT_SUFFIX => [
            "thresholdTraceCode"        => TraceCode::EMAIL_LOGIN_OTP_SEND_THRESHOLD_EXHAUSTED,
            "thresholdErrorCode"        => ErrorCode::BAD_REQUEST_EMAIL_LOGIN_OTP_SEND_THRESHOLD_EXHAUSTED,
            "redisTraceCode"            => TraceCode::EMAIL_LOGIN_OTP_REDIS_ERROR,
            "redisErrorCode"            => ErrorCode::SERVER_ERROR_EMAIL_LOGIN_OTP_REDIS_ERROR,
            "redisErrorDescription"     => "An error occurred while interacting with redis on email otp login route.",
        ],
        self::TWO_FA_PASSWORD_RATE_LIMIT_SUFFIX => [
            "thresholdTraceCode"        => TraceCode::LOGIN_2FA_PASSWORD_SUSPENDED,
            "thresholdErrorCode"        => ErrorCode::BAD_REQUEST_2FA_LOGIN_PASSWORD_SUSPENDED,
            "redisTraceCode"            => TraceCode::INCORRECT_2FA_PASSWORD_REDIS_ERROR,
            "redisErrorCode"            => ErrorCode::SERVER_ERROR_2FA_INCORRECT_PASSWORD_REDIS_ERROR,
            "redisErrorDescription"     => "An error occurred while interacting with redis on 2fa with password route.",
        ],
        self::VERIFY_LOGIN_OTP_RATE_LIMIT_SUFFIX => [
            "thresholdTraceCode"        => TraceCode::LOGIN_OTP_VERIFICATION_THRESHOLD_EXHAUSTED,
            "thresholdErrorCode"        => ErrorCode::BAD_REQUEST_LOGIN_OTP_VERIFICATION_THRESHOLD_EXHAUSTED,
            "redisTraceCode"            => TraceCode::LOGIN_OTP_VERIFICATION_REDIS_ERROR,
            "redisErrorCode"            => ErrorCode::SERVER_ERROR_LOGIN_OTP_VERIFICATION_REDIS_ERROR,
            "redisErrorDescription"     => "An error occurred while interacting with redis on login otp verification route.",
        ],
        self::VERIFY_OTP_VERIFICATION_RATE_LIMIT_SUFFIX => [
            "thresholdTraceCode"        => TraceCode::VERIFICATION_OTP_VERIFICATION_THRESHOLD_EXHAUSTED,
            "thresholdErrorCode"        => ErrorCode::BAD_REQUEST_VERIFICATION_OTP_VERIFICATION_THRESHOLD_EXHAUSTED,
            "redisTraceCode"            => TraceCode::VERIFY_OTP_VERIFICATION_REDIS_ERROR,
            "redisErrorCode"            => ErrorCode::SERVER_ERROR_VERIFY_OTP_VERIFICATION_REDIS_ERROR,
            "redisErrorDescription"     => "An error occurred while interacting with redis on email otp login verify route.",
        ],
        self::SEND_EMAIL_OTP_VERIFICATION_RATE_LIMIT_SUFFIX => [
            "thresholdTraceCode"        => TraceCode::EMAIL_VERIFICATION_OTP_SEND_THRESHOLD_EXHAUSTED,
            "thresholdErrorCode"        => ErrorCode::BAD_REQUEST_EMAIL_VERIFICATION_OTP_SEND_THRESHOLD_EXHAUSTED,
            "redisTraceCode"            => TraceCode::EMAIL_VERIFICATION_OTP_REDIS_ERROR,
            "redisErrorCode"            => ErrorCode::SERVER_ERROR_EMAIL_VERIFICATION_OTP_REDIS_ERROR,
            "redisErrorDescription"     => "'An error occurred while interacting with redis on email otp login route.'",
        ],
        self::RESET_PASSWORD_RATE_LIMIT_SUFFIX => [
            "thresholdTraceCode"        => TraceCode::RESET_PASSWORD_THRESHOLD_EXHAUSTED,
            "thresholdErrorCode"        => ErrorCode::BAD_REQUEST_RESET_PASSWORD_THRESHOLD_EXHAUSTED,
            "redisTraceCode"            => TraceCode::RESET_PASSWORD_REDIS_ERROR,
            "redisErrorCode"            => ErrorCode::SERVER_ERROR_RESET_PASSWORD_REDIS_ERROR,
            "redisErrorDescription"     => "An error occurred while interacting with redis while resetting the password.",
        ],
        "default" => [
            "thresholdTraceCode"        => TraceCode::REDIS_KEY_THRESHOLD_EXCEEDED,
            "thresholdErrorCode"        => ErrorCode::BAD_REQUEST_REDIS_KEY_THRESHOLD_EXCEEDED,
            "redisTraceCode"            => TraceCode::REDIS_SESSION_STORE_ERROR,
            "redisErrorCode"            => ErrorCode::SERVER_ERROR,
            "redisErrorDescription"     => "Redis Server Error"
        ]
    ];

    const VERIFY_LOGIN_SIGNUP_OTP_METRICS = [
        self::SIGNUP_OTP_ACTION         => Metric::VERIFY_SIGNUP_INCORRECT_OTP,
        self::LOGIN_OTP_ACTION          => Metric::VERIFY_LOGIN_INCORRECT_OTP,
        self::X_LOGIN_OTP_ACTION        => Metric::VERIFY_LOGIN_INCORRECT_OTP,
        self::VERIFY_USER_ACTION        => Metric::VERIFY_LOGIN_INCORRECT_OTP,
        self::X_VERIFY_USER_ACTION      => Metric::VERIFY_LOGIN_INCORRECT_OTP,
        self::SIGNUP_OTP_ACTION_V2      => Metric::VERIFY_SIGNUP_INCORRECT_OTP,
        self::LOGIN_OTP_ACTION_V2       => Metric::VERIFY_LOGIN_INCORRECT_OTP,
        self::LOGIN_OTP_ACTION_V2       => Metric::VERIFY_LOGIN_INCORRECT_OTP,
        self::VERIFY_SALESFORCE_USER_ACTION => Metric::VERIFY_SIGNUP_INCORRECT_OTP,
    ];

    // Ras Signup
    const RAS_SIGN_UP_CATEGORY      = 'sign_up_checker';
    const RAS_SIGN_UP_SOURCE        = 'sign_up_service';
    const RAS_SIGN_UP_EVENT_TYPE    = 'sign_up_success';

    // Country Code for Indian mobile numbers
    const SUPPORTED_COUNTRY_CODES_SIGNUP    = [
        'IN'
    ];

    const X_SECOND_FACTOR_AUTH_ACTION   = 'x_second_factor_auth';
    const X_LOGIN_OTP_ACTION            = 'x_login_otp';
    const X_VERIFY_USER_ACTION          = 'x_verify_user';
    const CREATE_WORKFLOW_CONFIG        = 'create_workflow_config';
    const UPDATE_WORKFLOW_CONFIG        = 'update_workflow_config';
    const DELETE_WORKFLOW_CONFIG        = 'delete_workflow_config';
    const BULK_APPROVE_PAYOUT           = 'bulk_approve_payout';
    const BULK_PAYOUT_APPROVE           = 'bulk_payout_approve';

    const SEND_SMS_VIA_STORK            = [
        self::X_SECOND_FACTOR_AUTH_ACTION,
        self::X_LOGIN_OTP_ACTION,
        self::X_VERIFY_USER_ACTION,
        self::CREATE_WORKFLOW_CONFIG,
        self::UPDATE_WORKFLOW_CONFIG,
        self::DELETE_WORKFLOW_CONFIG,
        self::BULK_APPROVE_PAYOUT
    ];

    const LOGIN_OTP_ACTION_V2                      = 'login_otp_v2';
    const SIGNUP_OTP_ACTION_V2                     = 'signup_otp_v2';

    const WORKFLOW_SELF_SERVE_ACTION_CREATE = 'create';
    const WORKFLOW_SELF_SERVE_ACTION_UPDATE = 'update';
    const WORKFLOW_SELF_SERVE_ACTION_DELETE = 'delete';

    const THROW_SMS_EXCEPTION_IN_STORK     = 'THROW_SMS_EXCEPTION_IN_STORK';

    const STORK_RESOURCE_EXHAUSTED_MESSAGE = 'twirp error resource_exhausted: request to send sms has been denied. maximum limit reached';

    const API_STORK_RX_SEND_SMS_RAZORX_EXP = 'api_stork_rx_send_sms';

    const UPDATE_LOGIN_SIGNUP_TEMPLATE_RAZORX_EXP    = 'update_login_signup_template';

    const FETCH_USER_EMAIL_CASE_INSENSITIVE = 'fetch_email_case_insensitive';

    // Constants related to product-switch for user_fetch
    const PRODUCT_SWITCH          = 'product_switch';
    const PRODUCT_SWITCH_REQUIRED = 'product_switch_required';

    const USER_FETCH_GUEST_BLACKLISTED_ROUTES = [
        'user_fetch'
    ];

    const VERIFY_CONTACT = 'verify_contact';

    const VERIFY_USER = 'verify_user';

    const ACTIONS_FOR_OTP_CONTACT_VERIFICATION = [
        self::VERIFY_CONTACT,
        self::VERIFY_USER,
    ];
}

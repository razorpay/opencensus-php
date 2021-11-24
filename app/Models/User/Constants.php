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

    const PERMISSIONS   = 'permissions';

    const BANNER_ID = 'banner_id';

    const BANNER_CLICKSOURCE = 'banner_clicksource';

    const BANNER_CLICKTIME = 'banner_clicktime';

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

    // oauth constants end

    const INCORRECT_LOGIN_TTL = 7200; // 2 hours

    // email otp login send ttl and threshold
    const EMAIL_LOGIN_OTP_SEND_TTL = 1800; // 30 mins
    const EMAIL_LOGIN_OTP_SEND_THRESHOLD = 5; // 5 times in 30 mins

    // login otp verification threshold
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

    const PASSWORD_RESET_TOKEN_EXPIRY_TIME =  86400; //24 hour

    const LINKED_ACCOUNT_CREATE_PASSOWRD_TOKEN_EXPIRY_TIME =  86400; //24 hours

    const SUBMERCHANT_ACCOUNT_CREATE_PASSOWRD_TOKEN_EXPIRY_TIME =  7776000; //3 months

    const USER_EMAIL_NOT_FOUND  = 'USER_EMAIL_NOT_FOUND';

    const LOCK   = 'lock';
    const UNLOCK = 'unlock';
    const UN_VERIFY = 'un_verify';

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
        self::VERIFY_USER_ACTION        => Metric::VERIFY_LOGIN_INCORRECT_OTP,
    ];
}

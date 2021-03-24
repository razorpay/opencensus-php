<?php

namespace App\User;

class Constants
{
    // Google Auth Constants
    const ID = 'id';

    const DATA = 'data';

    const ERROR = 'error';

    const EMAIL = 'email';

    const USER_ID = 'user_id';

    const SESSION = 'session';

    const PAYLOAD = 'payload';

    const POST_METHOD = 'POST';

    const ID_TOKEN = 'id_token';

    const INTERNAL = '_internal';

    const CLIENT_ID = 'client_id';

    const DESCRIPTION = 'description';

    const MERCHANT_ID = 'merchant_id';

    const MERCHANT_IDS = 'merchantIds';

    const USER_DETAILS = 'user_details';

    const OAUTH_PROVIDER_GOOGLE = 'google';

    const OAUTH_PROVIDER = 'oauth_provider';

    const RZP_USER_EMAIL = 'rzp_user_email';

    const EMAIL_VERIFIED = 'email_verified';

    const OAUTH_LOGIN = 'oauth_login';

    const OAUTH_MERCHANT_OAUTH_CLIENT_ID = 'oauth.merchant_oauth_client_id';

    /**
     *  Different Google Oauth Client Ids for epos, android, ios applications
     */
    const OAUTH_MERCHANT_OAUTH_CLIENT_ID_EPOS = 'oauth.merchant_oauth_client_id_epos';

    const OAUTH_MERCHANT_OAUTH_CLIENT_ID_ANDROID = 'oauth.merchant_oauth_client_id_android';

    const OAUTH_MERCHANT_OAUTH_CLIENT_ID_IOS = 'oauth.merchant_oauth_client_id_ios';

    const IOS = 'ios';

    const EPOS = 'epos';

    const ANDROID = 'android';

    const DASHBOARD = 'dashboard';


    /**
     * determines the 2fa verification state of user in session
     * If true, means routes requiring 2fa in API will pass
     */
    const TWO_FA_VERIFIED = 'two_fa_verified';

    const INTERNAL_ERROR_CODE = 'internal_error_code';

    const NO_DB_RECORDS_FOUND = 'No db records found.';

    const DASHBOARD_USER_PAYLOAD = 'dashboard_user_payload';

    // Routes
    const OAUTH_LOGIN_ROUTE = 'users/oauth-login';

    const OAUTH_REGISTER_ROUTE = 'users/oauth-register';

    const OAUTH_UNLOCK_ROUTE = 'users/oauth-login/no2fa';

    // Error Codes
    const GOOGLE_SIGN_IN_ERROR = 'Error connecting to Google. Please try again';

    const LOGIN_FAILED_CHECK_CREDENTIALS = 'User Login Failed, Please check your login credentials.';

    const ACCOUNT_DOES_NOT_EXIST = 'Razorpay Account Not Found.';

    const NETWORK_ISSUE_RELOAD_PAGE = 'Network issue, please reload the page.';

    const USER_ID_DEBUG_ACTIVATION_ISSUE = 'DqVrGiqepp5gxF';

    // RazorX Experiments and config keys
    // Experiments


    const REQUEST_ORIGIN = 'request_origin';
    // if a merchant signs up after configured `timestamp_threshold`,
    // the merchant is considered a new user. The timestamp
    // is usally around after when the code is deployed.
    const TIMESTAMP_THRESHOLD = 'timestamp_threshold';
    const DEFAULT_RESULT = 'default_result';

    const WHITELIST_CAPTCHA_EMAILS = [
        "qa+dashboard@razorpay.com",
        "qa+uiautomation@razorpay.com",
        "qa+rzp@razorpay.com",
        "annapurna.pal+008@razorpay.com"
    ];


}

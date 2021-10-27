<?php
namespace App\Http\Controllers;

use App\User\Constants;
use Illuminate\Support\Facades\Crypt;

use Auth;
use Input;
use Cookie;
use Session;

use App\User;
use App\Admin;
use App\Merchant;
use App\Http\ApiUrl;
use App\Trace\TraceCode;
use App\Http\AppResponse;
use App\User\RecoverableException;
use App\Metrics\Constants as MetricConstants;
use App\Merchant\Constants as MerchantConstants;

const EVENT_TRIGGER_COUNT = 1;
class UserController extends Controller
{

    const ROOT_PATH = '/';

    protected $guard = 'users';

    protected $app;

    protected $trace;

    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->metrics = $app['metrics'];
    }

    /**
     * Returns the base template for angular.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $domain = \Request::server('SERVER_NAME');
        $currentRouteName = \Route::currentRouteName();

        list($orgError, $org) = (new Admin\Service)->getOrg($domain);
        list($userError, $details) = (new User\Service)->getUserDetails();

        $data = [
            'isAuthenticated'       => false,
            'isConfirmed'           => false,
            'preSignupData'         => [],
            'isPreSignupComplete'   => false,
            'org'                   => json_encode($org),
            'session_id'            => Session::getId(),
        ];

        if (empty($userError) and empty($orgError))
        {
            $data = [
                'isAuthenticated'       => (bool) $details['user'],
                'isConfirmed'           => $details['user']['confirmed'],
                'preSignupData'         => $details['pre_signup'],
                'isPreSignupComplete'   => $details['pre_signup_complete'],
                'user'                  => json_encode($details),
                'org'                   => json_encode($org),
                'api_host'              => ApiUrl::getCheckoutApi(),
                'session_id'            => Session::getId(),
            ];
        }

        $data['cdnDashboardUrl'] = \Config::get('app.cdn_dashboard_url');
        $data['cdnBaseUrl'] = \Config::get('app.cdn_base_url');
        $data['ljKey'] = \Config::get('app.lj_key');
        $data['env'] = \Config::get('app.env');

        $baseUrl = $this->getDashboardBaseUrl();
        $requestPath = \Request::path();

        $data['redirectUrl']    = $baseUrl . '?next=' . $requestPath;
        $data['requestPath']     = $requestPath;
        $data['rootPath']       = self::ROOT_PATH;

        $data['newAuthFlow'] = false;
        $data['newAuthRoute'] = 'signup';

        if (empty($currentRouteName) === false and ($currentRouteName === "signup" || $currentRouteName === "signin"))
        {
            $data['newAuthFlow'] = true; // new signup/signin flow
            if ($currentRouteName === 'signin') {
                $data['newAuthRoute'] = 'signin'; // new signin flow
            }
        }
        
        // $data is used to run diferent pieces of JS
        if (isset($data['user']) === true and isset($details['linked_account']) === true and $details['linked_account'] === true)
        {
            return view('merchant.la', $data);
        }
        else
        {
            if (empty($details) === false)
            {
                $oldNotification = (new Merchant\Notifications\Service)->getOldNotificationsForUser($details, $org);
                $newNotification = (new Merchant\Notifications\Service)->getNewNotificationsForUser($details);
                $data['old_notifications'] = json_encode($oldNotification);
                $data['new_notifications'] = json_encode($newNotification);
                //TODO: Remove this in next release
                $data['notifications']     = json_encode(array_merge($oldNotification,$newNotification));
            }

            $currentMerchantId = $details['current'];

            if(is_null($currentMerchantId) === false)
            {
                $data['custom_notes'] = json_encode((new Merchant\CustomNotes\Service)->getNotesForPaymentLinksForMerchant($currentMerchantId));
                $data['pl_expiry_in_hrs'] = json_encode((new Merchant\PaymentLinkCustomization\Service)->getDefaultExpiryTimeForPaymentLinksForMerchant($currentMerchantId));
                $data['pl_extra_fields'] = json_encode((new Merchant\PaymentLinkCustomization\Service)->getExtraFormFieldsByMID($currentMerchantId));
                $data['pl_customized_form_fields'] = json_encode((new Merchant\PaymentLinkCustomization\Service)->getCustomizedFormFieldsByMID($currentMerchantId));
                $data['is_pl_customer_name_field_enabled'] = json_encode((new Merchant\PaymentLinkCustomization\Service)->getIsCustomerNameFieldEnabledByMID($currentMerchantId));
            }
            else {
                $data['custom_notes'] = null;
                $data['pl_expiry_in_hrs'] = null;
                $data['pl_extra_fields'] = null;
                $data['pl_customized_form_fields'] = null;
                $data['is_pl_customer_name_field_enabled'] = null;
            }

            // If a user accesses PG dashboard using X demo account, then we logout and redirect to signin
            // Since this is a PG dashboard route, no need to check product origin explicitly
            if (in_array($currentMerchantId,MerchantConstants::X_DEMO_MERCHANT_IDS,true)){
                $this->getLogout();
                $data['isAuthenticated'] = false;
                $data['isConfirmed'] = false;
            }

            return view('merchant.index', $data);
        }
    }

    public function getTnc()
    {
        $domain = \Request::server('SERVER_NAME');
        list($orgError, $org) = (new Admin\Service)->getOrg($domain);

        $data = [
            'env'              => \Config::get('app.env'),
            'cdnBaseUrl'       => \Config::get('app.cdn_base_url'),
            'cdnDashboardUrl'  => \Config::get('app.cdn_dashboard_url'),
            'api_host'         => ApiUrl::getCheckoutApi(),
            'org'              => json_encode($org),
        ];

        return view('merchant.tnc', $data);
    }

    /**
     * Returns an empty success to keep the user session active..
     *
     * @return \Illuminate\Http\Response
     */
    public function getKeepAlive()
    {
        return AppResponse::jsonResponse([]);
    }

    protected function checkCaptchaDisableInPayload($input)
    {
        $env = \App::environment();

        if ($env === 'production' and isset($input['captcha_disable']) === true)
        {
            $this->trace->info(TraceCode::CAPTCHA_DISABLE_INVALID_PAYLOAD_ERROR, ['email' => $input['email'] ?? null]);

            throw new \Razorpay\Api\Errors\BadRequestError(
                'invalid payload',
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }
    }

    protected function checkCaptchaDisableInPayloadForLogin($input)
    {
        $email = $input['email'] ?? null;

        if (in_array($email, Constants::WHITELIST_CAPTCHA_EMAILS, true) === true)
        {
            return;
        }

        $mobileApp = \Request::header('X-Razorpay-App');

        if (empty($mobileApp) === false)
        {
            return;
        }

        $this->checkCaptchaDisableInPayload($input);
    }

    public function postRegister()
    {
        $input = Input::all();

        $data = null;

        $error = [];

        try
        {

            $this->checkCaptchaDisableInPayload($input);

            list($error, $data) = (new User\Service)->register($input);

            if (empty($error))
            {
                $credentials = [
                    'email'             => $input['email'],
                    'password'          => $input['password'],
                    'captcha_disable'   => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
                ];

                $loginResult = Auth::attempt($credentials, false, true);

                app('trace')->info(TraceCode::USER_REGISTER_LOGIN_ATTEMPT, ["email" => $input['email'], "login_result" => $loginResult]);

                $twoFaDuringSignup = false;

                if ($loginResult === false)
                {
                    list($error, $data) = (new User\Service)->login($credentials);

                    if (empty($error) === false and
                        isset($error['internal_error_code']) === true and
                        $error['internal_error_code'] === 'BAD_REQUEST_USER_LOGIN_2FA_SETUP_REQUIRED')
                    {
                        $twoFaDuringSignup = true;
                    }
                }

                // push metrics on no error or if error is about two_fa_setup
                if (empty($error) === true or $twoFaDuringSignup === true)
                {
                    $this->metrics->count(MetricConstants::USER_SIGNUP_COUNT,
                        EVENT_TRIGGER_COUNT,
                        [
                            MetricConstants::TWO_FA_DURING_SIGNUP   => $twoFaDuringSignup,
                        ]);
                }
            }
        }
        catch (User\RecoverableException $e)
        {
            $error = [$e->getMessage()];
        }

        return AppResponse::jsonResponse($error, $data);
    }

    public function postOauthRegister()
    {
        $input = Input::all();

        try
        {
            list($error, $data) = (new User\Service)->oauthRegisterAndSignIn($input);
        }
        catch (RecoverableException $e)
        {
            $error = [$e->getMessage()];
            $data  = null;
        }

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Handle the authentication request from the user.
     *
     * @return \Illuminate\Http\Response
     */
    public function postSignin()
    {
        $timeStart = microtime(true);

        $input = Input::all();

        // Lowercasing emails for consistency
        if (isset($input['email']))
        {
            $input['email'] = mb_strtolower($input['email']);
        }

        $this->trace->info(TraceCode::USER_LOGIN_KEYS, [
            'captcha' => $input['captcha'] ?? null,
            'email'   => $input['email'] ?? null,
        ]);

        $this->checkCaptchaDisableInPayloadForLogin($input);

        list($error, $data) = (new User\Service)->login($input);

        if (empty($error) === true)
        {
            $this->metrics->count(MetricConstants::USER_LOGIN_COUNT,
                EVENT_TRIGGER_COUNT,
                [
                    MetricConstants::LOGIN_METHOD => MetricConstants::PASSWORD,
                    MetricConstants::LOGIN_ACTION => MetricConstants::NORMAL_LOGIN,
                ]);
        }

        $result = AppResponse::jsonResponse($error, $data);

        $timeEnd = microtime(true);
        $timeTaken = $timeEnd - $timeStart;
        $this->traceDuration($timeTaken, TraceCode::USER_LOGIN_DURATION);

        return $result;
    }

    /**
     * Handle demoLogin for X Demo Account
     *
     * @return \Illuminate\Http\Response
     */
    public function postDemoSignin()
    {
        list($error, $data) = (new User\Service)->demoLogin();

        $result = AppResponse::jsonResponse($error, $data);

        return $result;
    }

    /**
     * Handle the authentication request from the user for OTP logins and send OTP.
     *
     * @return \Illuminate\Http\Response
     */
    public function postSendLoginOtp()
    {
        $timeStarted = microtime(true);

        $input = Input::all();

        // Lowercasing emails for consistency
        if (isset($input['email']))
        {
            $input['email'] = mb_strtolower($input['email']);
        }

        list($error, $data) = (new User\Service)->otpLogin($input);

        $timeEnd = microtime(true);

        $timeTaken = $timeEnd - $timeStarted;

        $this->traceDuration($timeTaken, TraceCode::SEND_LOGIN_OTP_DURATION);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Handle the request to verify the user and send OTP.
     *
     * @return \Illuminate\Http\Response
     */
    public function postSendVerifyUserOtp()
    {
        $timeStarted = microtime(true);

        $input = Input::all();

        // Lowercasing emails for consistency
        if (isset($input['email']))
        {
            $input['email'] = mb_strtolower($input['email']);
        }

        list($error, $data) = (new User\Service)->otpVerifyUser($input);

        $timeEnd = microtime(true);

        $timeTaken = $timeEnd - $timeStarted;

        $this->traceDuration($timeTaken, TraceCode::SEND_USER_VERIFY_OTP_DURATION);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Handle the authentication request from the user for OTP logins and send OTP.
     *
     * @return \Illuminate\Http\Response
     */
    public function postVerifyLoginOtp()
    {
        $timeStarted = microtime(true);

        $input = Input::all();

        // Lowercasing emails for consistency
        if (isset($input['email']))
        {
            $input['email'] = mb_strtolower($input['email']);
        }

        list($error, $data) = (new User\Service)->verifyOtpLogin($input);

        if (empty($error) === true)
        {
            $this->metrics->count(MetricConstants::USER_LOGIN_COUNT,
                EVENT_TRIGGER_COUNT,
                [
                    MetricConstants::LOGIN_METHOD => MetricConstants::OTP,
                    MetricConstants::LOGIN_ACTION => MetricConstants::OTP_LOGIN,
                ]);
        }

        $timeEnd = microtime(true);

        $timeTaken = $timeEnd - $timeStarted;

        $this->traceDuration($timeTaken, TraceCode::VERIFY_LOGIN_OTP_DURATION);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Handle the authentication request from the user for OTP logins and send OTP.
     *
     * @return \Illuminate\Http\Response
     */
    public function postVerifyUserOtp()
    {
        $timeStarted = microtime(true);

        $input = Input::all();

        // Lowercasing emails for consistency
        if (isset($input['email']))
        {
            $input['email'] = mb_strtolower($input['email']);
        }

        list($error, $data) = (new User\Service)->verifyVerificationOtp($input);

        if (empty($error) === true)
        {
            $this->metrics->count(MetricConstants::USER_VERIFY_COUNT,
                EVENT_TRIGGER_COUNT,
                [
                    MetricConstants::LOGIN_METHOD => MetricConstants::OTP,
                    MetricConstants::LOGIN_ACTION => MetricConstants::OTP_LOGIN,
                ]);
        }

        $timeEnd = microtime(true);

        $timeTaken = $timeEnd - $timeStarted;

        $this->traceDuration($timeTaken, TraceCode::VERIFY_VERIFICATION_OTP_DURATION);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postOauthSignIn()
    {
        $input = Input::all();

        try
        {
            list($error, $data) = (new User\Service)->oauthSignIn($input);
        }
        catch (RecoverableException $e)
        {
            $error = [$e->getMessage()];
            $data  = null;
        }

        if (empty($error) === true)
        {
            $this->metrics->count(MetricConstants::USER_LOGIN_COUNT,
                EVENT_TRIGGER_COUNT,
                [
                    MetricConstants::LOGIN_METHOD => MetricConstants::OAUTH,
                    MetricConstants::LOGIN_ACTION => MetricConstants::NORMAL_LOGIN,
                ]);
        }

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Handle the authentication request from the user.
     *
     * @return \Illuminate\Http\Response
     */
    public function postSetup2faVerifyOtp()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->postSetup2faVerifyOtp($input);

        if (empty($error) === true)
        {
            $this->metrics->count(MetricConstants::USER_LOGIN_COUNT,
                EVENT_TRIGGER_COUNT,
                [
                    MetricConstants::LOGIN_METHOD => $this->getLoginMethodFromSession(),
                    MetricConstants::LOGIN_ACTION => MetricConstants::TWO_FA_OTP_VERIFICATION,
                ]);
        }

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Forwards OTP verification request to API
     */
    public function verifyUserViaOtp()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)
            ->verifyOtpAndMarkUserTwoFactorVerified($input);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Same operation as above using different URL
     */
    public function verifyContact()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)
            ->verifyUserContactAndMarkUserTwoFactorVerified($input);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * @return \Illuminate\Http\Response
    */
    public function postUpdate2faContact()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->postUpdate2faContact($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function post2faOtp()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->post2faOtp($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postResendOtp()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->postResendOtp($input);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Log out the currently authenticated user.
     *
     * @return \Illuminate\Http\Response
     */
    public function getLogout()
    {
        $user = Auth::guard('user');

        $userDetails = $user->user();

        $traceData = [
            'id'          => $userDetails->id,
        ];

        $this->trace->info(TraceCode::USER_LOGOUT, $traceData);

        $this->metrics->count(MetricConstants::USER_LOGOUT_COUNT,
            EVENT_TRIGGER_COUNT,
            [
                MetricConstants::LOGIN_METHOD => $this->getLoginMethodFromSession(),
            ]);

        $user->logout();

        Session::forget(User\Constants::OAUTH_LOGIN);

        Session::forget(User\Constants::TWO_FA_VERIFIED);

        return AppResponse::jsonResponse([]);
    }

    /**
     * Handle the request from user to change his current password.
     *
     * @return \Illuminate\Http\Response
     */
    public function postPassword()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->changePassword($input);

        return AppResponse::jsonResponse($error);
    }

    /**
     * Switch the merchant the user is currently viewing.
     *
     * @param  string  $merchantId
     * @return \Illuminate\Http\Response
     */
    public function switchCurrentMerchant($merchantId)
    {
        $input = Input::all();

        $user = Auth::user();

        $error = (new User\Service)->switchCurrentMerchantForUser($merchantId, $user);

        return AppResponse::jsonResponse($error);
    }

    public function resendEmailOtp()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->resendEmailOtp($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function verifyEmailOtp()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->verifyEmailOtp($input);

        return AppResponse::jsonResponse($error, $data);
    }

    public function getUserDetailsV2()
    {
        $timeStart = microtime(true);

        $params = Input::all();

        list($error, $data) = (new User\Service)->getUserDetails($params);

        $result = AppResponse::jsonResponse($error, $data);

        $timeEnd = microtime(true);
        $timeTaken = $timeEnd - $timeStart;
        $this->traceDuration($timeTaken, TraceCode::GET_USER_DURATION);

        return $result;
    }

    public function traceDuration($timeTaken, $traceCode){

        $user = Auth::user();

        if ($user !== null){
            $currentMerchantId = $user->currentMerchant() ? $user->currentMerchant()->id : null;

            $this->trace->info($traceCode, [
                "duration"    => $timeTaken, // seconds
                "user_id"     => $user->id,
                "merchant_id" => $currentMerchantId,
            ]);
        }
    }

    public function getUserDetailsForMobile()
    {
        list($error, $data) = (new User\Service)->getUserDetailsForMobile();

        return AppResponse::jsonResponse($error, $data);
    }

    public function postUpgradeUserToMerchant()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->upgradeUserToMerchant($input);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Fetch data for the currently active user session
     *
     * @return mixed
     */
    public function getSessionData()
    {
        $queryParams = Input::all();

        list($error, $data) = (new User\Service)->getSessionData($queryParams);

        return AppResponse::jsonResponse($error, $data);
    }

    /**
     * Creates an identity token for the logged in user and share the same via redirect URL
     *
     * @param string $clientId
     * @return mixed
     */
    public function getIdentityToken(string $clientId)
    {
        $queryParams = Input::all();

        list($error, $url) = (new User\Service)->generateIdentityToken($clientId, $queryParams);

        //
        // if there are no errors then do a 302 redirect to the service providers
        // callback url with the token
        //
        // if there is an error then we should redirect the user to login page
        // with the ?next param as the user/identifier. So that once the user
        // is authenticated successfully we can pass the token to service provider
        //
        if (empty($error) === true)
        {
            return redirect($url);
        }

        return AppResponse::jsonResponse($error, null);
    }

    /**
     * The auth-service gets details of the currently logged in user
     * using this route (once it has the token)
     *
     * @param string $token
     *
     * @return \Illuminate\Http\Response
     */
    public function getDetailsFromToken(string $token)
    {
        list($error, $data) = (new User\Service)->getDetailsFromSessionToken($token);

        $response = AppResponse::jsonResponse($error, $data);

        return $response;
    }

    /**
     * Return base template for browser extensions
     *
     * @return \Illuminate\Http\Response
     */
     public function getBrowserExtensionIndex()
     {
        $dashboardCdn = \Config::get('app.cdn_dashboard_url');
        $data['cdnUrl'] = $dashboardCdn ? substr($dashboardCdn, 0, -10) : "http://static.razorpay.in";
        return view('extension.index', $data);
     }

    /**
     * Generates a JWT token with the context and returns the token to the client.
     * https://github.com/lcobucci/jwt
     */
    public function generateJWT()
    {
        list($error, $result) = (new User\Service)->generateJWT();

        return AppResponse::jsonResponse($error, $result);
    }

    /**
     * On extension logout if their is any user available we should log the user out.
     *
     * @return mixed
     */
    public function getExtensionLogout()
    {
        $user = Auth::guard('user');

        if (empty($user->user()) === false)
        {
            $userDetails = $user->user();

            $traceData = [
                'id'          => $userDetails->id,
            ];

            $this->trace->info(TraceCode::USER_LOGOUT, $traceData);

            $user->logout();
        }

        return AppResponse::jsonResponse([]);
    }

    public function validateJWT()
    {
        return ['success' => true];
    }


    private function getDashboardBaseUrl()
    {
        $isSessionSecure = \Config::get('session.secure');

        if ($isSessionSecure === true)
        {
            $connection = 'https://';
        }
        else
        {
            $connection = 'http://';
        }

        return $connection . \Request::server('SERVER_NAME');
    }

    private function getLoginMethodFromSession()
    {
        $isOauthLogin = Session::get(User\Constants::OAUTH_LOGIN, false);

        return $isOauthLogin === true ? MetricConstants::OAUTH : MetricConstants::PASSWORD;
    }
}

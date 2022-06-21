<?php
namespace App\Http\Controllers;

use Auth;
use Input;
use Cookie;
use Session;
use App\User;
use App\Admin;
use App\Merchant;
use App\Lib\Util;
use App\Http\ApiUrl;
use App\User\Helper;
use App\User\Constants;
use App\Trace\TraceCode;
use App\Http\AppResponse;
use App\User\RecoverableException;
use Illuminate\Support\Facades\Crypt;
use Razorpay\Api\Errors\BadRequestError;
use App\Metrics\Constants as MetricConstants;
use App\Merchant\Constants as MerchantConstants;
use App\User\Constants as UserConstants;

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
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector
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
            'isMobileConfirmed'     => false,
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
                'isMobileConfirmed'     => $details['user']['contact_mobile_verified'],
                'preSignupData'         => $details['pre_signup'],
                'isPreSignupComplete'   => $details['pre_signup_complete'],
                'user'                  => json_encode($details),
                'org'                   => json_encode($org),
                'api_host'              => ApiUrl::getCheckoutApi(),
                'session_id'            => Session::getId(),
            ];

            if ($this->isRedirectionApplicable($details) === true)
            {
                $ttl = 12 * 60;

                return redirect(env('EASY_DASHBOARD_URL'))->withCookies([
                    Cookie::make('rzp_merchant_id', $details['id'], $ttl, null, env('SECOND_LEVEL_DOMAIN'), true, false),
                    Cookie::make('rzp_user_id', $details['user']['id'], $ttl, null, env('SECOND_LEVEL_DOMAIN'), true, false),
                ]);
            }

            if ($this->canCookieSetForEasyOnboardingPostL1Submit($details) === true)
            {
                $ttl = 12 * 60;

                Cookie::queue('rzp_merchant_id', $details['id'], $ttl, null, env('SECOND_LEVEL_DOMAIN'), true, false);
                Cookie::queue('rzp_user_id', $details['user']['id'], $ttl, null, env('SECOND_LEVEL_DOMAIN'), true, false);
            }
        }

        $data['cdnDashboardUrl'] = \Config::get('app.cdn_dashboard_url');
        $data['cdnBaseUrl'] = \Config::get('app.cdn_base_url');
        $data['ljKey'] = \Config::get('app.lj_key');
        $data['env'] = \Config::get('app.env');

        $baseUrl = $this->getDashboardBaseUrl();
        $requestPath = \Request::path();

        $data['redirectUrl']    = $baseUrl . '?next=' . $requestPath;
        $data['requestPath']    = $requestPath;
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
                $newNotification = (new Merchant\Notifications\Service)->getNewNotificationsForUser($details, $org);
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

            // If a user accesses PG dashboard using X demo account, then we log out and redirect to sign-in
            // Since this is a PG dashboard route, no need to check product origin explicitly
            if (in_array($currentMerchantId,MerchantConstants::X_DEMO_MERCHANT_IDS,true))
            {
                $this->getLogout();
                $data['isAuthenticated'] = false;
                $data['isConfirmed'] = false;
                $data['isMobileConfirmed'] = false;
            }

            return view('merchant.index', $data);
        }
    }

    private function isEasyOnboardingExperimentEnable(): bool
    {
        try
        {
            $experiment = (new Merchant\Service)->getTreatment('easy_onboarding') === ['result' => 'on'];
        }
        catch (BadRequestError $e)
        {
            $this->trace->info(TraceCode::RAZORX_CALL_FAILED, [
                'error' => $e
            ]);

            return false;
        }

        return $experiment;
    }

    private function isAuthSourceHasWebsite(): bool
    {
        $queryParams = Input::all();

        $authSource = $queryParams['auth_source'] ?? null;

        if ($authSource === 'website' or $authSource === 'website_homepage')
        {
            return true;
        }

        return false;
    }

    private function canCookieSetForEasyOnboardingPostL1Submit($details): bool
    {
        if ($this->isAuthSourceHasWebsite() === true)
        {
            return false;
        }

        if ($this->isEasyOnboardingExperimentEnable() === false)
        {
            return false;
        }

        $signupCampaign = $details['user']['signup_campaign'] ?? null;

        $activationFormMilestone = $details['activation_form_milestone'] ?? null;

        if (($signupCampaign === 'easy_onboarding') and 
            ($activationFormMilestone == 'L1' or $activationFormMilestone == 'L2'))
        {
            return true;
        }

        return false;

    }

    private function isRedirectionApplicable($details): bool
    {
        if ($this->isEasyOnboardingExperimentEnable() === false)
        {
            return false;
        }

        if ($this->isAuthSourceHasWebsite() === true)
        {
            return false;
        }

        $signupCampaign = $details['user']['signup_campaign'] ?? null;

        if (($signupCampaign === 'easy_onboarding') and
            (empty($details['activation_form_milestone']) === true))
        {
            return true;
        }

        return false;
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

            $this->trace->info(
                TraceCode::CAPTCHA_DISABLE_INVALID_PAYLOAD_ERROR,
                ['data' => Util::maskLoginSignupInput($input)]
            );

            $this->metrics->count(MetricConstants::USER_REGISTER_REQUEST_WITHOUT_CAPTCHA_COUNT ,
                EVENT_TRIGGER_COUNT,
                [
                    MetricConstants::PRODUCT => ApiUrl::isBankingOriginRequest() ? MetricConstants::BANKING : MetricConstants::PRIMARY,
                ]);

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

    public function postRegisterSendOtp()
    {
        $timeStarted = microtime(true);

        $input = Input::all();

        // Lowercasing emails for consistency
        if (isset($input['email']))
        {
            $input['email'] = mb_strtolower($input['email']);
        }

        list($error, $data, $httpCode) = (new User\Service)->registerWithOtp($input);

        $timeTaken = microtime(true) - $timeStarted;

        $this->traceDuration($timeTaken, TraceCode::SEND_SIGNUP_OTP_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::SEND_SIGNUP_OTP, $input, $error, $timeTaken);

        if(
            isset($error["internal_error_code"]) and
            in_array($error['internal_error_code'], Admin\ApiRequestAny::INTERNAL_ERROR_CODES)
        )
        {
            $error = [$error];
        }

        return AppResponse::jsonResponse($error, $data, $httpCode);
    }

    public function postRegisterVerifyOtp()
    {
        $timeStarted = microtime(true);

        $input = Input::all();

        // Lowercasing emails for consistency
        if (isset($input['email']))
        {
            $input['email'] = mb_strtolower($input['email']);
        }

        $this->checkCaptchaDisableInPayload($input);

        list($error, $data, $httpCode) = (new User\Service)->registerWithOtpVerify($input);

        if (isset($input['email']))
        {
            $signupMedium = MetricConstants::EMAIL;
        }
        else
        {
            $signupMedium = MetricConstants::CONTACT_MOBILE;
        }

        $res = [];

        if (empty($error) === true)
        {
            $genericUser = (new Helper)->createdGenericUser($data);
            Auth::login($genericUser, false);
            $this->app[Constants::SESSION]->put(Constants::DASHBOARD_USER_PAYLOAD, $genericUser);

            $user = Auth::user();

            $res = [
                "id"                => $user->currentMerchant() ? $user->currentMerchant()->id : null,
                "name"              => $user->currentMerchant() ? $user->currentMerchant()->name : null,
                "email"             => $user->email,
                "contact_mobile"    => $user->contact_mobile,
                "user_id"           => $user->id,
                "logged_in_via"     => (isset($input[UserConstants::EMAIL]) === true) ? UserConstants::EMAIL : UserConstants::CONTACT_MOBILE
            ];
        }

        $timeTaken = microtime(true) - $timeStarted;

        $this->traceDuration($timeTaken, TraceCode::VERIFY_SIGNUP_OTP_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::VERIFY_SIGNUP_OTP, $input, $error, $timeTaken);

        if(
            isset($error["internal_error_code"]) and
            in_array($error['internal_error_code'], Admin\ApiRequestAny::INTERNAL_ERROR_CODES)
        )
        {
            $error = [$error];
        }

        return AppResponse::jsonResponse($error, $res, $httpCode);
    }

    public function postRegister()
    {
        $timeStart = microtime(true);
        $input = Input::all();
        $data = null;
        $error = [];

        try
        {
            $this->checkCaptchaDisableInPayload($input);

            list($error, $data, $httpCode) = (new User\Service)->register($input);

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
                        isset($error[UserConstants::INTERNAL_ERROR_CODE]) === true and
                        $error[UserConstants::INTERNAL_ERROR_CODE] === 'BAD_REQUEST_USER_LOGIN_2FA_SETUP_REQUIRED')
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

                if(isset($input["email"]) === true)
                {
                    $data["logged_in_via"] = UserConstants::EMAIL;
                }
                else
                {
                    $data["logged_in_via"] = UserConstants::CONTACT_MOBILE;
                }
            }
        }
        catch (User\RecoverableException $e)
        {
            $error = [$e->getMessage()];
        }

        $timeTaken = microtime(true) - $timeStart;

        $this->traceDuration($timeTaken, TraceCode::USER_SIGNUP_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::USER_SIGNUP, $input, $error, $timeTaken);

        return AppResponse::jsonResponse($error, $data, $httpCode);
    }

    public function postOauthRegister()
    {
        $timeStart = microtime(true);
        $input = Input::all();

        try
        {
            list($error, $data, $httpCode) = (new User\Service)->oauthRegisterAndSignIn($input);
        }
        catch (RecoverableException $e)
        {
            $error = [$e->getMessage()];
            $data  = null;
            $httpCode = 400;
        }

        $timeTaken = microtime(true) - $timeStart;

        $this->traceDuration($timeTaken, TraceCode::USER_OAUTH_SIGNUP_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::USER_OAUTH_SIGNUP, $input, $error, $timeTaken);

        return AppResponse::jsonResponse($error, $data, $httpCode);
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

        $userService = (new User\Service());

        $userService->addUserBrowserDetails($input);

        list($error, $data, $httpCode) = $userService->login($input);

        $timeTaken = microtime(true) - $timeStart;

        $this->traceDuration($timeTaken, TraceCode::USER_LOGIN_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::USER_LOGIN, $input, $error, $timeTaken);

        return AppResponse::jsonResponse($error, $data, $httpCode);
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

        list($error, $data, $httpCode) = (new User\Service)->otpLogin($input);

        $timeTaken = microtime(true) - $timeStarted;

        $this->traceDuration($timeTaken, TraceCode::SEND_LOGIN_OTP_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::SEND_LOGIN_OTP, $input, $error, $timeTaken);

        if((isset($error[UserConstants::INTERNAL_ERROR_CODE]) === true) and
            (in_array($error[UserConstants::INTERNAL_ERROR_CODE], Admin\ApiRequestAny::INTERNAL_ERROR_CODES) === true))
        {
            $error = [$error];
        }

        return AppResponse::jsonResponse($error, $data, $httpCode);
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

        if((isset($error[UserConstants::INTERNAL_ERROR_CODE]) === true) and
            (in_array($error[UserConstants::INTERNAL_ERROR_CODE], Admin\ApiRequestAny::INTERNAL_ERROR_CODES) === true))
        {
            $error = [$error];

            $login_medium  = isset($input['email']) ? MetricConstants::EMAIL : MetricConstants::CONTACT_MOBILE;

            $this->metrics->count(MetricConstants::LOGIN_OTP_FAILED,
                EVENT_TRIGGER_COUNT,
                [
                    MetricConstants::LOGIN_MEDIUM => $login_medium,
                    MetricConstants::LOGIN_METHOD => MetricConstants::OTP,
                ]);
        }

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

        list($error, $data, $httpCode) = (new User\Service)->verifyOtpLogin($input);

        $timeTaken = microtime(true) - $timeStarted;

        $this->traceDuration($timeTaken, TraceCode::VERIFY_LOGIN_OTP_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::VERIFY_LOGIN_OTP, $input, $error, $timeTaken);

        return AppResponse::jsonResponse($error, $data, $httpCode);
    }

    /**
     * Handle the 2FA with password on OTP based login
     *
     * @return \Illuminate\Http\Response
     */
    public function postOtpLogin2faPassword()
    {
        $timeStarted = microtime(true);
        $input = Input::all();

        list($error, $data) = (new User\Service)->verify2FAMode($input, UserConstants::LOGIN_2FA_WITH_PASSWORD);

        $timeTaken = microtime(true) - $timeStarted;

        $this->traceDuration($timeTaken, TraceCode::OTP_LOGIN_2FA_PASSWORD_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::OTP_LOGIN_2FA_PASSWORD, $input, $error, $timeTaken);

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

        $dimensions = [
            MetricConstants::LOGIN_METHOD => MetricConstants::OTP,
            MetricConstants::LOGIN_MEDIUM => MetricConstants::EMAIL,
            MetricConstants::LOGIN_ACTION => MetricConstants::OTP_LOGIN,
        ];

        if (empty($error) === true)
        {
            $this->metrics->count(MetricConstants::USER_VERIFY_COUNT,
                EVENT_TRIGGER_COUNT,
                $dimensions
            );
        } else {
            $this->metrics->count(MetricConstants::USER_LOGIN_VERIFY_OTP_FAIL_COUNT,
                EVENT_TRIGGER_COUNT,
                $dimensions
            );
        }

        $timeEnd = microtime(true);

        $timeTaken = $timeEnd - $timeStarted;

        $this->traceDuration($timeTaken, TraceCode::VERIFY_VERIFICATION_OTP_DURATION);

        return AppResponse::jsonResponse($error, $data);
    }

    public function postOauthSignIn()
    {
        $timeStart = microtime(true);
        $input = Input::all();

        try
        {
            list($error, $data, $httpCode) = (new User\Service)->oauthSignIn($input);
        }
        catch (RecoverableException $e)
        {
            $error = [$e->getMessage()];
            $data  = null;
            $httpCode = 400;
        }

        $timeTaken = microtime(true) - $timeStart;

        $this->traceDuration($timeTaken, TraceCode::USER_OAUTH_LOGIN_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::USER_OAUTH_LOGIN, $input, $error, $timeTaken);

        return AppResponse::jsonResponse($error, $data, $httpCode);
    }

    /**
     * Handle the authentication request from the user.
     *
     * @return \Illuminate\Http\Response
     */
    public function postSetup2faVerifyOtp()
    {
        $timeStarted = microtime(true);
        $input = Input::all();

        list($error, $data) = (new User\Service)->verify2FAMode($input, UserConstants::LOGIN_2FA_WITH_OTP);

        $timeTaken = microtime(true) - $timeStarted;

        $this->traceDuration($timeTaken, TraceCode::PASSWORD_LOGIN_2FA_OTP_DURATION);

        Helper::pushSignUpLoginMetrics(Constants::PASSWORD_LOGIN_2FA_OTP, $input, $error, $timeTaken);

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

    public function postSetPassword()
    {
        $input = Input::all();

        list($error, $data) = (new User\Service)->postSetPassword($input);

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

        $currentMerchantId = Session::get('current_merchant_id','');

        if (in_array($currentMerchantId, MerchantConstants::X_DEMO_MERCHANT_IDS, true))
        {
            // Clearing all session data as session keys like current_merchant_id are persisted even after logout
            $this->trace->info(TraceCode::FORCE_SESSION_CLEAR_AFTER_X_DEMO_LOGOUT, []);

            Session::forget('current_merchant_id');
            Session::forget('dashboard_user_payload');
        }

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

<?php

namespace App\User;


use Auth;
use GuzzleHttp\Client as Guzzle;
use Trace;
use Cookie;
use Session;
use Request;
use Config;
use App\Base;
use DateTimeZone;
use App\Merchant;
use App\Razorx;
use App\Lib\Util;
use App\Http\ApiUrl;
use App\Http\Headers;
use App\Edge\EdgeClient;
use Lcobucci\JWT\Token;
use App\MerchantDetails;
use App\Trace\TraceCode;
use App\Trace\SpanTrace;
use App\Constants\Tracing;
use Lcobucci\JWT\Signer\Key;
use App\Admin\ApiRequestAny;
use App\Providers\GenericUser;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Configuration;
use Lcobucci\Clock\SystemClock;
use App\Session as SessionTable;
use App\Merchant\GenericMerchant;
use Razorpay\Api\Errors\ErrorCode;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Illuminate\Contracts\Cache\Store;
use Lcobucci\JWT\Encoding\JoseEncoder;
use App\Admin\Service as AdminService;
use Illuminate\Foundation\Application;
use Lcobucci\JWT\Validation\Constraint;
use Razorpay\Api\Errors\BadRequestError;
use App\Splitz\Service as SplitzService;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use App\Metrics\Constants as MetricConstants;
use App\Merchant\Constants as MerchantConstants;
use Illuminate\Auth\Access\AuthorizationException;
use hisorange\BrowserDetect\Parser as BrowserDetect;
use App\Constants\Constants as AppConstants;
use App\Admin\ApiPromiseAny as ApiPromiseAny;
use function PHPUnit\Framework\at;

const EVENT_TRIGGER_COUNT = 1;
class Service extends Base\Service
{
    const OAUTH_SESSION_TOKEN = 'oauth_session_token';

    const BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP = 'BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP';

    const MERCHANT_ID         = 'merchant_id';

    const USER_ID             = 'user_id';

    const EXTENSION           = 'extension';

    const MERCHANT_LOGO       = 'merchant_logo';

    const MERCHANT_NAME       = 'merchant_name';

    const MERCHANT_ACTIVATED  = 'merchant_activated';

    const CAPTCHA_MODE_HEADER = 'X-RECAPTCHA-MODE';

    const LOGIN_UNAUTHENTICATED = 'LOGIN_UNAUTHENTICATED';

    const LOGIN_UNREGISTERED = 'LOGIN_UNREGISTERED';

    // Users who signed up before this date
    // are not exposed to the pre signup flow
    const PRE_SIGNUP_TIMESTAMP = 1488306600;

    const SOURCE              = 'source';

    const OAUTH_ACTION        = 'oauth_action';

    const EXPERIMENT_PROMISE = 'experiments';
    const SPLITZ_EXPERIMENT_PROMISE = 'splitz_experiments';
    const PARTNER_INTENT_PROMISE = 'partner_intent';
    const CONFIG_PROMISE = 'configs';
    const SALES_FORCE_LEADS_PROMISE = 'create_lead_sales_force';

    const RZP_ACCESS_TOKEN = 'rzp_access_token';
    const RZP_REFRESH_TOKEN = 'rzp_refresh_token';

    const PROMISES_PARALLEL_API_CALL = [
        self::EXPERIMENT_PROMISE,
        self::SPLITZ_EXPERIMENT_PROMISE,
        self::PARTNER_INTENT_PROMISE,
        self::CONFIG_PROMISE,
    ];

    /**
     * @var Application
     */
    protected $app;

    protected $trace;

    /**
     * @var Store
     */
    protected $cache;

    /**
     * @var \GuzzleHttp\Client|null
     */
    private ?Guzzle $httpClient;

    /**
     * @var \GuzzleHttp\Client|null
     */
    private $edgeClient;

    public function __construct(array $options = [])
    {
        $app = \App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->cache = $app['cache'];

        $this->metrics = $app['metrics'];

        $this->httpClient = array_get($options, AppConstants::HTTP_CLIENT);

        $this->edgeClient = new EdgeClient();
    }

    /**
     * Main registration method. Contains most business logic for deciding what to
     * register and as what (user|merchant) and with what details. See
     * HACKING.md for a bit more details.
     *
     * @param  array  $input [description]
     *
     * @return array
     *
     * @throws BadRequestError
     */
    public function register($input): array
    {
        // PG FE is sending this header
        // we are forwarding this header to PG backend so we can send otp for verify email
        $options['headers']['X-Send-Email-Otp'] = Request::header('X-Send-Email-OTP') ?? 'false';
        $options['headers'][self::CAPTCHA_MODE_HEADER] = Request::header(self::CAPTCHA_MODE_HEADER);

        $request = new ApiRequestAny($options);

        $this->checkOauthProviderInPayload(
            $input,
            TraceCode::USER_REGISTER_OAUTH_PROVIDER_ERROR,
            MetricConstants::USER_REGISTER_REQUEST_WITH_OAUTH_PROVIDER_COUNT
        );

        $this->traceApiTrigger(
            $input,
            TraceCode::USER_SIGNUP_TRIGGERED,
            MetricConstants::USER_SIGNUP_TRIGGERED_COUNT,
            MetricConstants::PASSWORD,
            false
        );

        list($error, $data, $httpCode) = $request->processInput($input)->send('users/register', 'POST');

        /*if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }*/

        return [$error, $data, $httpCode];
    }

    /**
     * @param  array  $input [description]
     *
     * @return array
     */
    public function registerWithOtp(array $input): array
    {
        $this->traceApiTrigger(
            $input,
            TraceCode::SEND_SIGNUP_OTP_TRIGGERED,
            MetricConstants::SEND_SIGNUP_OTP_TRIGGERED_COUNT,
            MetricConstants::OTP,
            false
        );

        unset($input[Constants::REQUEST_SOURCE]);

        return $this->requestAPI($input,'users/register/otp', 'POST');
    }

    public function sendOtpForSalesForceUser(array $input): array {
        $options['headers'][self::CAPTCHA_MODE_HEADER] = Request::header(self::CAPTCHA_MODE_HEADER);
        return $this->requestAPI($input,'users/salesforce/otp', 'POST', $options);
    }

    public function verifyOtpForSalesForceUser(array $input): array {
        return $this->requestAPI($input,'users/salesforce/otp/verify', 'POST');
    }

    public function traceApiTrigger(array $input, string $traceCode, string $metricConstant, string $method, bool $isLogin)
    {

        $span = SpanTrace::startSpan([
            'name' => $metricConstant
        ]);

        $scope = SpanTrace::withSpan($span);
        try
        {
            $product        = ApiUrl::isBankingOriginRequest() ? 'banking' : 'primary';
            $methodLabel    = $isLogin ? MetricConstants::LOGIN_METHOD : MetricConstants::SIGNUP_METHOD;
            $mediumLabel    = $isLogin ? MetricConstants::LOGIN_MEDIUM : MetricConstants::SIGNUP_MEDIUM;
            $medium         = "MEDIUM_NA";
            $mediumValue    = "UNKNOWN";
            $signupSource   = $input[Constants::SIGNUP_SOURCE] ?? "NA";
            $requestSource  = $input[Constants::REQUEST_SOURCE] ?? "NA";

            if(isset($input[Constants::EMAIL]) === true)
            {
                $medium         = MetricConstants::EMAIL;
                $mediumValue    = Util::mask_email($input[Constants::EMAIL]);
            }
            elseif (isset($input[Constants::CONTACT_MOBILE]) === true)
            {
                $medium         = MetricConstants::CONTACT_MOBILE;
                $mediumValue    = Util::mask_phone($input[Constants::CONTACT_MOBILE]);
            }

            $traceInfo =  [
                $medium                                              => $mediumValue,
                'medium'                                             => $medium,
                'product'                                            => $product,
                'signup_source'                                      => $signupSource,
                'request_source'                                     => $requestSource,
                $methodLabel                                         => $method,
                $mediumLabel                                         => $medium,
                MetricConstants::LABEL_API_BASE_URL                  => ApiUrl::getApiHost(),
            ];

            $this->trace->info($traceCode, $traceInfo);

            // don't want to push mobile number to metrics
            unset($traceInfo[$medium]);

            $this->metrics->count(
                $metricConstant ,
                EVENT_TRIGGER_COUNT,
                $traceInfo
            );
            $span->addAttributes($traceInfo);
            $span->addAttribute(Tracing::SPAN_KIND ,Tracing::INTERNAL);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                TraceCode::LOGIN_SIGNUP_METRIC_TRACE_PUSH_FAILED,
                [
                    "exception" => $e->getMessage()
                ]
            );
        } finally {
            $scope->close();
        }
    }

    /**
     * @param array $input
     * @return array
     * @throws BadRequestError
     */
    public function registerWithOtpVerify(array $input): array
    {
        $options['headers']['X-Send-Email-Otp'] = 'false';
        $options['headers'][self::CAPTCHA_MODE_HEADER] = Request::header(self::CAPTCHA_MODE_HEADER);

        $this->traceApiTrigger(
            $input,
            TraceCode::VERIFY_SIGNUP_OTP_TRIGGERED,
            MetricConstants::VERIFY_SIGNUP_OTP_TRIGGERED_COUNT,
            MetricConstants::OTP,
            false
        );

        unset($input[Constants::REQUEST_SOURCE]);

        list($error, $data, $httpCode) = $this->requestAPI($input,'users/register/otp/verify', 'POST', $options);

        return [$error, $data, $httpCode];
    }

    /**
     *  For security reasons, checking explicitly for oauth_provider key in the payload.
     *  If exists, not allowing to hit users/register route.
     *
     * @param $input
     *
     * @throws BadRequestError
     */
    protected function checkOauthProviderInPayload($input, $traceCode, $metricConstant)
    {
        if (isset($input[Constants::OAUTH_PROVIDER]) === true)
        {
            $this->trace->info($traceCode, ['data' => Util::maskLoginSignupInput($input)]);

            $this->metrics->count($metricConstant ,
                EVENT_TRIGGER_COUNT,
                [
                    MetricConstants::PRODUCT                    => ApiUrl::isBankingOriginRequest() ? MetricConstants::BANKING : MetricConstants::PRIMARY,
                    MetricConstants::LABEL_API_BASE_URL         => ApiUrl::getApiBaseUrl(),
                ]);

            throw new \Razorpay\Api\Errors\BadRequestError(
                'invalid payload',
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }
    }

    /**
     * @param $input
     *
     * @return array
     * @throws BadRequestError
     */
    public function oauthRegisterAndSignIn($input): array
    {
        $input[Constants::OAUTH_SOURCE] = Request::header(Headers::OAUTH_SOURCE) ?? Constants::DASHBOARD;

        $oauthProviderInput = $input[Constants::OAUTH_PROVIDER];

        $input[Constants::OAUTH_PROVIDER] = json_encode(array($input[Constants::OAUTH_PROVIDER]));

        list($error, $data, $httpCode) = $this->oauthRegister($input);

        if ((empty($error) === true))
        {
            $credentials = [
                Constants::EMAIL          => $input[Constants::EMAIL],
                Constants::ID_TOKEN       => $input[Constants::ID_TOKEN],
                Constants::OAUTH_PROVIDER => $oauthProviderInput,
                Constants::OAUTH_SOURCE   => $input[Constants::OAUTH_SOURCE],
            ];

            list($error, $data, $httpCode) = $this->oauthSignIn($credentials);
        }

        return [$error, $data, $httpCode];
    }

    /**
     * @param $input
     *
     * @return array
     * @throws BadRequestError
     */
    public function oauthRegister($input): array
    {
        $request = new ApiRequestAny();

        list($error, $data, $httpCode) = $request->processInput($input)->send(
            Constants::OAUTH_REGISTER_ROUTE, Constants::POST_METHOD);

        /*if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }*/

        if (empty($error) === true) Session::put(Constants::OAUTH_LOGIN, true);

        return [$error, $data, $httpCode];
    }

    public function oauthSignIn($input): array
    {
        list($error, $genericUser, $httpCode) = $this->oauthLoginOnApiOnRoute($input,
            Constants::OAUTH_LOGIN_ROUTE,
            Constants::POST_METHOD);

        return $this->handleOauthLoginResponse($error, $genericUser, "email", $httpCode);
    }

    /**
     * @param  array  $input [description]
     *
     * @return array
     */
    public function verify2FAMode(array $input, string $mode = Constants::LOGIN_2FA_WITH_OTP)
    {
        $userFromGuard = Auth::guard('user')->user();

        $userIdFromSession = Session::get('user_id', "");

        if (app('request.ctx')->isOauthRequest() === true)
        {
            $userIdFromSession = app('request.ctx')->getUserId();
        }

        if (app('request.ctx')->isOauthRequest() === true)
        {
            $userId = app('request.ctx')->getUserId();

            $this->options['headers']['X-Dashboard-User-Id'] = $userId;
        }

        $logged_in_via = Session::get('logged_in_via', null);

        if (empty($userFromGuard) === false)
        {
            $options['headers']['X-Dashboard-User-Id'] = $userFromGuard->id;
        }
        else if (empty($userIdFromSession) === false)
        {
            $options['headers']['X-Dashboard-User-Id'] = $userIdFromSession;
        }
        else
        {
            return [["User Not authenticated, please login"], []];
        }

        $options['headers']['X-Dashboard-User-Session-Id'] = Session::getId();

        $this->trace->info(TraceCode::USER_VERIFY_LOGIN_VIA_2FA, [
            'user_id'      => $userIdFromSession,
            'login_in_via' => $logged_in_via,
            'mode'         => $mode
        ]);

        switch ($mode)
        {
            case Constants::LOGIN_2FA_WITH_OTP:
                list($error, $genericUser) = $this->verify2faOtp($input, $options);
                break;
            case Constants::LOGIN_2FA_WITH_PASSWORD:
                list($error, $genericUser) = $this->verifyOtpLogin2faPasswordOnApi($input, $options);
                break;
        }

        if (empty($error) === true)
        {
            $this->markUserTwoFactorVerified();
        }

        return $this->handleLoginResponse($error, $genericUser, $logged_in_via);
    }

    public function verifyOtpAndMarkUserTwoFactorVerified(array $input)
    {
        // no need of extracting data from response
        list($error) = (new ApiRequestAny(['client_type' => 'user']))
            ->processInput($input)
            ->send('users/2fa/verify', 'POST');

        if (empty($error) === true)
        {
            $this->markUserTwoFactorVerified();
        }

        return [$error, []];
    }

    public function verifyUserContactAndMarkUserTwoFactorVerified(array $input)
    {
        list($error) = (new ApiRequestAny(['client_type' => 'merchant']))
            ->processInput($input)
            ->send('users/verify_contact', 'POST');

        if (empty($error) === true)
        {
            $this->markUserTwoFactorVerified();
        }

        return [$error, []];
    }


    public function verify2faOtp(array $input, array $options = [])
    {
        return $this->loginOnApiOnRoute($input,'users/2fa/verify', 'POST', $options);
    }

    public function requestApiWithBasicSession(array $input, $route, $httpVerb)
    {
        $userId = Session::get('user_id', "");

        if (app('request.ctx')->isOauthRequest() === true)
        {
            $userId = app('request.ctx')->getUserId();

            $options['headers']['X-Dashboard-User-Id'] = $userId;
        }
        else if (empty($userId) === false)
        {
            $options['headers']['X-Dashboard-User-Id'] = $userId;

            $options['headers']['X-Dashboard-User-Session-Id'] = Session::getId();
        }
        else
        {
            return [["User Not authenticated, please login"], []];
        }

        $this->trace->info(TraceCode::USER_VERIFY_2FA_RESEND_OTP, [
            'user_id'      => $userId,
        ]);

        $request = new \App\Admin\ApiRequestAny($options);

        list($error, $data) = $request->processInput($input)->send($route, $httpVerb);

        return [$error, $data];
    }

    public function postUpdate2faContact(array $input)
    {
        return $this->requestApiWithBasicSession($input, 'users/2fa_setup/contact_mobile', 'PATCH');
    }

    public function postSetPassword(array $input)
    {
        $userIdFromSession = Session::get('user_id', "");

        $logged_in_via = Session::get('logged_in_via', null);

        $this->trace->info(TraceCode::USER_SET_PASSWORD, [
            'user_id'      => $userIdFromSession,
            'login_in_via' => $logged_in_via,
        ]);

        $options['headers']['X-Dashboard-User-Id'] = $userIdFromSession;

        list($error, $genericUser) =  $this->loginOnApiOnRoute($input,'users/password', 'PATCH', $options);

        return $this->handleLoginResponse($error, $genericUser, $logged_in_via);
    }

    public function postUserExists(array $input)
    {
        return $this->requestAPI($input,'users/exists', 'POST');
    }

    public function postSendEmailOtp(array $input)
    {
        return $this->requestAPI($input,'users/email/send_otp', 'POST');
    }

    public function postVerifyEmailOtp(array $input)
    {
        list($error, $data, $httpCode) = $this->requestAPI($input,'users/email/verify_otp', 'POST');

        if (empty($error) === true)
        {
            $userId =  $data[Constants::USER_ID] ?? "";
            Session::put('user_id', $userId);
            Session::put('logged_in_via', Constants::EMAIL);
        }

        return [$error, [], $httpCode];
    }

    public function postUserDetailsToSalesforce(array $input)
    {
        return $this->requestAPI($input,'users/salesforce_event', 'POST');
    }

    public function post2faOtp(array $input)
    {
        // This API works properly only in live mode, in live mode we used to send the otp request.
        $request = new \App\Admin\ApiRequestAny([
            'mode'      => 'live',
        ]);

        return $request->send('users/2fa', 'POST');
    }

    public function postResendOtp(array $input)
    {
        return $this->requestApiWithBasicSession($input, 'users/2fa/otp_resend', 'POST');
    }

    public function verifyEmailOtp($input)
    {
        $request = new \App\Admin\ApiRequestAny(['client_type' => 'merchant']);

        list($error, $data) = $request->processInput($input)->send('users/verify_email', 'POST');

        if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        // Adding this check Since user would not be present in session for oauth.
        if (app('request.ctx')->isOauthRequest() === false)
        {
            $user = Auth::guard('user')->user();
            $user->confirmed = true;
        }

        return [$error, $data];
    }

    /**
     * @param $input
     *
     * @return array
     * @throws BadRequestError
     */
    public function resendEmailOtp($input): array
    {
        $request = new \App\Admin\ApiRequestAny(['client_type' => 'merchant']);

        list($error, $data) = $request->processInput($input)->send('users/resend-verification-otp', 'POST');

        if (empty($error) === false)
        {
            if ((array_key_exists(Constants::INTERNAL_ERROR_CODE, $error) === true) and
                (empty($error[Constants::INTERNAL_ERROR_CODE]) === false))
            {
                throw new BadRequestError(
                    $error[Constants::DESCRIPTION],
                    ErrorCode::BAD_REQUEST_ERROR,
                    400
                );
            }
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    public function createMerchant(array $input)
    {
        $options = [
            'client_type' => 'user'
        ];

       return $this->requestAPI($input, 'users/merchants', 'POST', $options);
    }

    /**
     * @param  array  $input [description]
     *
     * @return array
     */
    public function login(array $input)
    {

        list($error, $genericUser, $httpCode) = $this->loginOnApi($input);

        if(isset($input["email"]) === true)
        {
            $logged_in_via = Constants::EMAIL;
        }
        else
        {
            $logged_in_via = Constants::CONTACT_MOBILE;
        }

        return $this->handleLoginResponse($error, $genericUser, $logged_in_via, $httpCode);
    }

    /**
     * @return array
     */
    public function demoLogin()
    {
        /*
         * current_merchant_id does not change to new one if already exists
         * It needs to overwritten or cleared
         * Check app/User/Helper.php getCurrentMerchant()
         */
        $currentMerchantId = Session::get('current_merchant_id','');

        if (!empty($currentMerchantId) and  !in_array($currentMerchantId, MerchantConstants::X_DEMO_MERCHANT_IDS, true))
        {
            // Clearing all session data as session keys like current_merchant_id are persisted even after logout
            $this->trace->info(TraceCode::FORCE_SESSION_CLEAR_BEFORE_X_DEMO_LOGIN, [
                'current_merchant_id'   => $currentMerchantId
            ]);

            Session::invalidate();
        }

        $input = array(
            "email"    => Constants::BANKING_DEMO_USER_EMAIL,
            "password" => $this->app['config']['app.banking_demo_user_password'],
            "captcha"  => Constants::FAKE_CAPTCHA        //captcha cannot be empty even if email is whitelisted
        );

        return $this->login($input);
    }

    /**
     * @param  array  $input [description]
     *
     * @return array
     */
    public function otpLogin(array $input)
    {
        $res = null;

        return $this->otpLoginOnApi($input);
    }

    /**
     * @param  array  $input [description]
     *
     * @return array
     */
    public function otpVerifyUser(array $input)
    {
        $res = null;

        return $this->otpLoginForVerifyUser($input);
    }

    /**
     * @param  array  $input [description]
     *
     * @return array
     */
    public function verifyOtpLogin(array $input)
    {
        list($error, $genericUser, $httpCode) = $this->verifyOtpLoginOnApi($input);

        if(isset($input["email"]) === true)
        {
            $logged_in_via = Constants::EMAIL;
        }
        else
        {
            $logged_in_via = Constants::CONTACT_MOBILE;
        }

        return $this->handleLoginResponse($error, $genericUser, $logged_in_via, $httpCode);
    }

    /**
     * @param  array  $input [description]
     *
     * @return array
     */
    public function verifyVerificationOtp(array $input)
    {
        list($error, $genericUser) = $this->verifyVerificationOtpOnApi($input);

        if(isset($input[Constants::EMAIL]) === true)
        {
            $logged_in_via = Constants::EMAIL;
        }
        else
        {
            $logged_in_via = Constants::CONTACT_MOBILE;
        }

        return $this->handleLoginResponse($error, $genericUser, $logged_in_via);
    }

    protected function handleLoginResponse($error, $genericUser, $logged_in_via=null, $httpCode=null)
    {
        if (empty($error) === false)
        {
            if ((array_key_exists(Constants::INTERNAL_ERROR_CODE, $error) === true) and
                (empty($error[Constants::INTERNAL_ERROR_CODE]) === false))
            {
                $userId = $error[Constants::INTERNAL]['user_details']['user_id'] ??  "";

                if (empty($userId) === false and
                    (in_array($error[Constants::INTERNAL_ERROR_CODE], Constants::SESSION_WHITELISTED_ERROR_CODES, true) === true))
                {
                    $this->trace->info(TraceCode::USER_LOGIN_ADD_IN_SESSION, [
                        'user_id'           => $userId,
                        'login_in_via'      => $logged_in_via,
                        'user_from_session' => Session::get(Constants::USER_ID, "")
                    ]);

                    Session::put('user_id', $userId);
                    Session::put('logged_in_via', $logged_in_via);
                }
                else
                {
                    if(in_array($error[Constants::INTERNAL][Constants::INTERNAL_ERROR_CODE], ApiRequestAny::INTERNAL_ERROR_CODES) === true)
                    {
                        return [[$error, self::LOGIN_UNAUTHENTICATED], null, $httpCode];
                    }
                    return [['User Login Failed, Please check your login credentials.', self::LOGIN_UNAUTHENTICATED], null, $httpCode];
                }

                // very very nasty dirty hack to not to write lot of code.
                if ($error[Constants::INTERNAL_ERROR_CODE] === self::BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP)
                {
                    $error = $error['description'];
                }

                return [[$error, self::LOGIN_UNAUTHENTICATED], null, $httpCode];
            }

            if (in_array('Low captcha score', $error) === true)
            {
                return [['Captcha score low, Please try again.', self::LOGIN_UNAUTHENTICATED], null, $httpCode];
            }

            if (in_array('Captcha Failed', $error) === true)
            {
                return [['Captcha validation Failed, Please refresh page and try again.', self::LOGIN_UNAUTHENTICATED], null, $httpCode];
            }

            if (in_array('Incorrect Password login attempt exhausted. Please contact support or login via dashboard', $error) === true)
            {
                return [['Incorrect Password login attempt exhausted. Please contact support or login via dashboard', self::LOGIN_UNAUTHENTICATED], null, $httpCode];
            }

            if (in_array('Verification failed because of incorrect OTP.', $error) === true)
            {
                return [['Verification failed because of incorrect OTP.', self::LOGIN_UNAUTHENTICATED], null, $httpCode];
            }

            if (in_array('BAD_REQUEST_INCORRECT_OTP', $error) === true)
            {
                return [['Verification failed because of incorrect OTP.', self::LOGIN_UNAUTHENTICATED], null, $httpCode];
            }

            return [['The email or password you have entered is incorrect. Click on “Forgot?” to reset your password. ', self::LOGIN_UNAUTHENTICATED], null, $httpCode];
        }

        Auth::login($genericUser, false);

        $this->app['session']->put('dashboard_user_payload', $genericUser);

        $res = [
            'id' => $genericUser->id,
        ];

        $user = Auth::user();

        $currentMerchantId = $user->currentMerchant() ? $user->currentMerchant()->id : null;

        $isBankingRequest = ApiUrl::isBankingOriginRequest();

        // Return currentMerchantId in the response only if
        // 1. the request is from X and banking_role is present
        // 2. the request is from PG and role is present
        // Below conditions are false when a PG user logs into X for the first time or vice versa.
        if ((empty($user->currentMerchant()) === false)  and
            ((($isBankingRequest === true) and ($user->currentMerchant()->banking_role !== null)) or
                (($isBankingRequest === false) and ($user->currentMerchant()->role !== null))))
        {
            $res['currentMerchantId'] = $currentMerchantId;
            $res['otp_auth_token'] = $genericUser->otp_auth_token ?? null;
        }

        if (isset($logged_in_via))
        {
            $res["logged_in_via"] = $logged_in_via;
        }

        $shouldShowPopUp = $this->shouldShowPopUp();

        $this->app['session']->put('show_tnc_popup',$shouldShowPopUp);

        $res['show_tnc_popup'] = $shouldShowPopUp;

        $traceData = [
            'id'          => $user->id,
            'merchant_id' => $currentMerchantId,
            'shouldShowPopUp' => $shouldShowPopUp,
        ];

        $this->trace->info(TraceCode::USER_LOGIN, $traceData);

        try {
            app('edgeMismatchRecorder')->setLegacyData('users/login', 'POST', $genericUser);
        } catch (\Throwable $e) {
            app('trace')->warning(TraceCode::EDGE_USER_AUTH_MISC_CODE, [
                'trace' => $e->getTrace() ?? "unknown_trace",
                'message' => $e->getMessage() ?? "unknown_message"
            ]);
        }

        return [$error, $this->addAccessTokenAndMidToResponse($res, $genericUser), $httpCode];
    }

    protected function handleOauthLoginResponse($error, $genericUser, $logged_in_via=null, $httpCode = null)
    {
        if (empty($error) === false)
        {
            if ((array_key_exists(Constants::INTERNAL_ERROR_CODE, $error) === true) and
                (empty($error[Constants::INTERNAL_ERROR_CODE]) === false))
            {
                $userId = $error[Constants::INTERNAL][Constants::USER_DETAILS][Constants::USER_ID] ??  "";

                if (empty($userId) === false and
                    (in_array($error[Constants::INTERNAL_ERROR_CODE], Constants::SESSION_WHITELISTED_ERROR_CODES, true) === true))
                {
                    $this->trace->info(TraceCode::USER_LOGIN_ADD_IN_SESSION, [
                        'user_id'           => $userId,
                        'login_in_via'      => $logged_in_via,
                        'user_from_session' => Session::get(Constants::USER_ID, "")
                    ]);

                    Session::put(Constants::USER_ID, $userId);
                    Session::put('logged_in_via', $logged_in_via);
                }
                else
                {
                    return [[Constants::LOGIN_FAILED_CHECK_CREDENTIALS, self::LOGIN_UNAUTHENTICATED], null, $httpCode];
                }

                // very very nasty dirty hack to not to write lot of code. check handleLoginResponse function
                if ($error[Constants::INTERNAL_ERROR_CODE] === self::BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP)
                {
                    $error = $error[Constants::DESCRIPTION];
                }

                return [[$error, self::LOGIN_UNAUTHENTICATED], null, $httpCode];
            }

            //temporarily added and will be removed after the root cause is fixed.
            if (in_array(Constants::NO_DB_RECORDS_FOUND, $error) === true)
            {
                return [[Constants::ACCOUNT_DOES_NOT_EXIST, self::LOGIN_UNREGISTERED], null, $httpCode];
            }

            return [[Constants::GOOGLE_SIGN_IN_ERROR, self::LOGIN_UNAUTHENTICATED], null, $httpCode];
        }

        Auth::login($genericUser, false);

        $this->app[Constants::SESSION]->put(Constants::DASHBOARD_USER_PAYLOAD, $genericUser);

        $res = [
            Constants::ID => $genericUser->id,
        ];

        $user = Auth::user();

        $currentMerchantId = $user->currentMerchant() ? $user->currentMerchant()->id : null;

        $isBankingRequest = ApiUrl::isBankingOriginRequest();

        // Return currentMerchantId in the response only if
        // 1. the request is from X and banking_role is present
        // 2. the request is from PG and role is present
        // Below conditions are false when a PG user logs into X for the first time or vice versa.
        if ((empty($user->currentMerchant()) === false)  and
            ((($isBankingRequest === true) and ($user->currentMerchant()->banking_role !== null)) or
                (($isBankingRequest === false) and ($user->currentMerchant()->role !== null))))
        {
            $res['currentMerchantId'] = $currentMerchantId;
        }

        if (isset($logged_in_via))
        {
            $res["logged_in_via"] = $logged_in_via;
        }

        $traceData = [
            Constants::ID          => $user->id,
            Constants::MERCHANT_ID => $currentMerchantId,
        ];

        $this->trace->info(TraceCode::USER_OAUTH_LOGIN, $traceData);

        $this->deleteSessionsIfApplicable($genericUser);

        $shouldShowPopUp = $this->shouldShowPopUp();

        $this->app['session']->put('show_tnc_popup',$shouldShowPopUp);

        $res['show_tnc_popup'] = $shouldShowPopUp;

        return [$error, $this->addAccessTokenAndMidToResponse($res, $genericUser), $httpCode];
    }

    protected function addAccessTokenAndMidToResponse($res, $genericUser)
    {
        if (app('request.ctx')->isOauthRequest() === true)
        {
            $res['x_mobile_access_token'] = $genericUser->x_mobile_access_token ?? null;

            $res['x_mobile_refresh_token'] = $genericUser->x_mobile_refresh_token ?? null;

            $res['x_mobile_client_id'] = $genericUser->x_mobile_client_id ?? null;

            $res['currentMerchantId'] = $genericUser->current_merchant_id ?? null;
        }

        return $res;
    }

    public function deleteSessionsIfApplicable(GenericUser $genericUser)
    {
        $genericUser = $genericUser ? $genericUser->toArray() : null;

        if ((isset($genericUser[Constants::INVALIDATE_SESSIONS]) === true) and
            ($genericUser[Constants::INVALIDATE_SESSIONS] === true))
        {
            $currentSessionId = Session::getId();

            $sessionData = [
                Constants::USER_ID          => $genericUser[Constants::ID],
                Constants::CURRENT_SESSION  => $currentSessionId,
            ];

            $this->trace->info(TraceCode::INVALIDATE_OTHER_ACTIVE_SESSIONS, $sessionData);

            // delete other active sessions when a user login/sign-up using Google SSO for the first time
            (new SessionTable\Entity)->deleteSessionsForUser($genericUser[Constants::ID], $currentSessionId);
        }
    }

    public function changePassword(array $input)
    {
        $user = Auth::user();

        if ($user->currentMerchant() and $user->currentMerchant()->isTestAccount())
        {
            return [["Password change forbidden on this account"], null];
        }

        list($error, $data) = $this->updatePasswordOnApi($input);

        if (empty($error) === true)
        {
            Session::forget(Constants::TWO_FA_VERIFIED);
        }

        $currentSessionId = Session::getId();

        (new SessionTable\Entity)->deleteSessionsForUser($user->id, $currentSessionId);

        return [$error, $data];
    }

    public function changePasswordWithOtpVerification(array $input)
    {
        $user = Auth::user();

        if ($user->currentMerchant() and $user->currentMerchant()->isTestAccount())
        {
            return [["Password change forbidden on this account"], null];
        }

        list($error, $data) = $this->updatePasswordOnApiWithOtpVerification($input);

        if (empty($error) === true)
        {
            Session::forget(Constants::TWO_FA_VERIFIED);
        }

        $currentSessionId = Session::getId();

        (new SessionTable\Entity)->deleteSessionsForUser($user->id, $currentSessionId);

        return [$error, $data];
    }

    /**
     * reissue user token on edge
     * @param string $merchantId
     * @param GenericUser $user
     * @return ?array Edge response on success
     */
    public function reissueTokenOnEdge(string $merchantId, GenericUser $user) {
        try {
            return $this->edgeClient->reissueToken($merchantId, $user);
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::EDGE_TOKEN_REISSUE_FAILED, [
                "message"   => "Failed to reissue user session token at edge for switch merchant: " . $e->getMessage(),
            ]);

            // TODO: throw exception when we move out of shadow mode
            // return if user token can not be reissued at edge
            // we will not alter the sessions at redis unless edge tokens are reissued successfully
//            throw new ServerErrorException(
//                "Failed to switch merchant: " . $e->getMessage(),
//                \Razorpay\Api\Errors\ErrorCode::SERVER_ERROR,
//                500);
        }
    }

    /**
     * Switch the merchant the user is currently viewing.
     *
     * @param  string  $merchantId
     * @return \Illuminate\Http\Response
     */
    public function switchCurrentMerchantForUser($merchantId, GenericUser $user)
    {
        list($error) = $this->checkAccessOfUserOnMerchant($merchantId);

        if (empty($error) === true)
        {
            // revoke user token on edge using jti
            $data = $this->reissueTokenOnEdge($merchantId, $user);

            if (! empty($data)) {
                // laravel cookies allows ttl only in minutes
                $rt_ttl = $data['refresh_token_ttl']/60;
                $at_ttl = $data['access_token_ttl']/60;

                // set the reissued token to cookie
                // we are explicitly setting the cookie domain as null and path as '/'
                // similar to how it's being set for rzp_usr_session.
                Cookie::queue(self::RZP_ACCESS_TOKEN, $data['access_token'], $at_ttl, "/", null, true, true);
                Cookie::queue(self::RZP_REFRESH_TOKEN, $data['refresh_token'], $rt_ttl, "/", null, true, true);
            }

            Session::put('current_merchant_id', $merchantId);

            // Forgetting is_merchant_login session for current Merchant i.e the merchant who has done switched account.
            // so that switched merchant not go through Chunked Based streaming part in first render.

            $isMerchantLogin = $this->getMerchantLogin($merchantId);

            if ($isMerchantLogin !== false)
            {
                $key = Util::getIsMerchantLoginCacheKey($merchantId);
                $this->cache->forget($key);
            }

            $traceData = [
                'id'          => $user->id,
                'merchant_id' => $merchantId,
                'session_mid' => Session::get('current_merchant_id'),
            ];

            $this->trace->info(TraceCode::SWITCH_MERCHANT, $traceData);

            return [];
        }

        return ["Couldn't find the merchant you are looking for."];
    }

    public function upgradeUserToMerchant($input)
    {
        $authUser = Auth::user();

        $data = [
            'business_name' =>  $input['business_name'],
            'user_id'       =>  $authUser->id,
        ];

        $request = new \App\Admin\ApiRequestAny();

        list($error, $data) = $request->processInput($data)->send('users/upgrade-merchant', 'POST');

        $genericUser = null;

        if (empty($error) === true)
        {
            list($error, $genericUser) = $this->getUserFromApi($authUser->id);

            if (empty($error) === true)
            {
                Session::put('dashboard_user_payload', $genericUser);
            }
        }

        return [$error, $data];
    }

    public function updatePasswordOnApi($data)
    {
        $passwordData = [
            'password'              => $data['password'],
            'password_confirmation' => $data['password_confirmation'],
            'old_password'          => $data['old_password'],
        ];

        $request = new \App\Admin\ApiRequestAny();

        list($error, $data) = $request->processInput($passwordData)->send("users/password", 'PUT');

        if (empty($error) === false)
        {
            throw new \Razorpay\Api\Errors\BadRequestError(
                $error[0],
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    public function updatePasswordOnApiWithOtpVerification($data)
    {
        $passwordData = [
            'password'              => $data['password'],
            'password_confirmation' => $data['password_confirmation'],
            'old_password'          => $data['old_password'],
            'otp'                   => $data['otp'],
            'token'                 => $data['token'],
        ];

        $request = new \App\Admin\ApiRequestAny();

        list($error, $data) = $request->processInput($passwordData)->send("users/password/otp_verify", 'PUT');

        if (empty($error) === false)
        {
            throw new \Razorpay\Api\Errors\BadRequestError(
                $error[0],
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    /**
     * Get data from the current user session
     *
     * @param array $queryParams
     *
     * @return array
     */
    public function getSessionData(array $queryParams): array
    {
        $user = Auth::user();

        $data = $error = null;

        $merchantId = $merchantRole = $merchantName = $merchantLogo = null;

        if ($user === null)
        {
            //
            // If user is null, no active session exists
            // We simply return null, and allow the Authenticate middleware
            // to send a 401 response.
            //
            return [$error, $data];
        }

        $currentMerchant = $user->currentMerchant();

        if (empty($currentMerchant) === false)
        {
            $merchantId = $currentMerchant->id;

            $merchantRole = $currentMerchant->role ?? $currentMerchant->role;

            $merchantName = $currentMerchant->name;

            $merchantLogo = $currentMerchant->logo_url;
        }

        // Create and cache a random token tying the user to the request
        $token = str_random(30);

        $data = [
            'user_id'       => $user->id,
            'user_email'    => $user->email,
            'merchant_id'   => $merchantId,
            'role'          => $merchantRole,
            'query_params'  => $queryParams['query'] ?? []
        ];

        $cacheKey = $this->getOAuthSessionTokenCacheKey($token);

        $this->cache->put($cacheKey, $data, 60);

        $response = [
            'token'         => $token,
            'email'         => $user->email,
            'name'          => $user->name,
            'merchant_id'   => $merchantId,
            'role'          => $merchantRole,
            'merchant_name' => $merchantName,
            'logo'          => $merchantLogo,
            'user_id'       => $user->id
        ];

        $oauthAction = $this->getUserOauthAction($merchantId, $queryParams);

        if (!empty($oauthAction))
        {
            $response[self::OAUTH_ACTION] = $oauthAction;
        }

        return [$error, $response];
    }

    /**
     * Fetch cached data for a session token
     * Used in auth-service for verifying user creds, S2S
     *
     * @param string $token
     *
     * @return array
     */
    public function getDetailsFromSessionToken(string $token): array
    {
        $error = $data = null;
        $cacheKey = $this->getOAuthSessionTokenCacheKey($token);

        $data = $this->cache->get($cacheKey);

        if ($data !== null)
        {
            $user = $this->getUserFromApi($data['user_id']);

            $data['user'] = $user;
            $data['user']['merchant_id'] = $data['merchant_id'];
        }
        else
        {
            $error[] = 'User data not found';
        }

        return [$error, $data];
    }

    /**
     * Defines the cache key for OAuth session tokens
     *
     * @param string $token
     *
     * @return string
     */
    private function getOAuthSessionTokenCacheKey(string $token): string
    {
        return self::OAUTH_SESSION_TOKEN . '.' . $token;
    }

    public function addUserBrowserDetails(array &$input)
    {
        try
        {
            $browser = (new BrowserDetect())->detect();

            $browserDetails = [
                Constants::DEVICE   => $browser->deviceModel(),
                Constants::BROWSER  => $browser->browserFamily(),
                Constants::OS       => $browser->platformName(),
            ];

            $input[Constants::BROWSER_DETAILS] = $browserDetails;
        }
        catch (\Exception $e)
        {
            $this->trace->info(
                TraceCode::USER_FETCH_BROWSER_DETAILS_FAILURE,
                [
                    Constants::ERROR => $e,
                    Constants::EMAIL => $input[Constants::EMAIL],
                ]
            );
        }
    }

    public function getFirstChunkUserDetails(array $params = [])
    {
        $data = [
            'current'   =>  null
        ];

        $errors = (new Validator())->validateInput('user_fetch', $params)->messages();

        if(empty($errors) === false)
        {
            return [$errors, []];
        }

        $user = Auth::user();

        if (app('request.ctx')->isOauthRequest() === true)
        {
            $userId = app('request.ctx')->getUserId();
        }

        if (!$user &&
            empty($userId) === true)
        {
            return [['Not logged in'], null];
        }
        else if (empty($userId) === true)
        {
            $userId = $user->id;
        }

        list($error, $genericUser) = $this->getUserFromApi($userId);

        if (empty($error) === false)
        {
            return [$error, ['details' => $data, 'currentMerchant' => null, 'genericUser' => null]];
        }

        $userDetails = $genericUser->toArray();

        $userDetails[Constants::TWO_FA_VERIFIED] = Session::get(
            Constants::TWO_FA_VERIFIED,
            false); //default value is false

        //default value is false
        $userDetails[Constants::OAUTH_LOGIN] = Session::get(Constants::OAUTH_LOGIN, false);

        $data['user'] = $userDetails;

        // Default values in case no merchant is associated
        // with the user account
        $data['pre_signup'] = [];
        $data['pre_signup_complete'] = true;
        $data['experiments'] = [];
        $data['tags'] = [];
        $data['features'] = [];
        $data['campaigns'] = [];

        $currentMerchant = (new Helper([AppConstants::HTTP_CLIENT => $this->httpClient]))->getCurrentMerchant($genericUser);

        if ($currentMerchant === null)
        {
            return [[], ['details' => $data, 'currentMerchant' => null, 'genericUser' => null]];
        }

        $data = $data + $currentMerchant->toArray();

        $this->traceMerchantActivatedTruthyValue($data, __LINE__);

        if ($currentMerchant->role === 'owner')
        {
            $data['primaryOwner'] = true;
        }
        else
        {
            $data['primaryOwner'] = false;
        }

        return [[], ['details' => $data, 'currentMerchant' => $currentMerchant, 'genericUser' => $genericUser]];
    }

    static function millitime(): int
    {
        return round(microtime(true) * 1000);
    }

    private function getDataFromApiPromiseResponse($data, $globalMerchant, $apiPromiseAny, $currentMerchant, $isSplitzCachingEnabled, $isRazorxCachingEnabled = false): array
    {
        $startTime = self::millitime();

        if (empty($apiPromiseAny))
        {
            return $data;
        }

        $currentMerchantId = $currentMerchant->id;

        $allPromises =  $this->getAllApiPromises($apiPromiseAny);

        $this->setStartTimeForAllApiPromises($apiPromiseAny);

        // fire and wait for all the promises to complete
        $allApiResponses = \GuzzleHttp\Promise\Utils::settle($allPromises)->wait();

        $this->setResponseForEachApiPromises($apiPromiseAny, $allApiResponses);

        $merchantService = new Merchant\Service;

        if (empty($data[Constants::EXPERIMENTS]) && isset($allApiResponses[self::EXPERIMENT_PROMISE]))
        {
            $experiments = $merchantService->processExperimentPromiseResponse($apiPromiseAny[self::EXPERIMENT_PROMISE]);

            $data['experiments'] = $experiments;

            if($isRazorxCachingEnabled && empty($experiments) == false)
            {
                (new Razorx\Service())->setRazorxCacheByIdAsyncPromise($currentMerchantId, $experiments);
            }

            $data = $this->updateNewUsersOnlyTypeExperiments($globalMerchant, $data);

            $data = $this->updateRXCASelfServeExperiment($globalMerchant, $data);
        }

        if (empty($data[Constants::SPLITZ_EXPERIMENTS]) && isset($allApiResponses[self::SPLITZ_EXPERIMENT_PROMISE]))
        {
            $splitzExperiments = (new SplitzService([AppConstants::HTTP_CLIENT => $this->httpClient]))->processVariantBulkAsyncPromiseResponse($apiPromiseAny[self::SPLITZ_EXPERIMENT_PROMISE]);

            $data[Constants::SPLITZ_EXPERIMENTS] = $splitzExperiments;

            if($isSplitzCachingEnabled && empty($splitzExperiments) == false){

                (new SplitzService([AppConstants::HTTP_CLIENT => $this->httpClient]))->setCacheByIdAsyncPromise($currentMerchantId, $splitzExperiments);

            }
        }

        if (isset($allApiResponses[self::PARTNER_INTENT_PROMISE]))
        {
            $partnerIntent = $merchantService->processPartnerIntentPromiseResponse($apiPromiseAny[self::PARTNER_INTENT_PROMISE]);

            $data['partner_intent'] = $partnerIntent;
        }

        if (isset($allApiResponses[self::CONFIG_PROMISE]))
        {
            $configs = $merchantService->processPartnerConfigPromiseResponse($apiPromiseAny[self::CONFIG_PROMISE]);

            if (empty($configs) === false)
            {
                foreach ($configs as $config)
                {
                    if ($config[Merchant\Constants::COMMISSION_MODEL] === Merchant\Constants::COMMISSION)
                    {
                        $data['merchants'][$globalMerchant['id']]['partner']['has_commission_configs'] = true;
                    }
                    else if ($config[Merchant\Constants::COMMISSION_MODEL] === Merchant\Constants::SUBVENTION)
                    {
                        $data['merchants'][$globalMerchant['id']]['partner']['has_subvention_configs'] = true;
                    }
                }
            }
        }

        $endTime = self::millitime();

        $this->trace->info(TraceCode::API_PROMISE_RESPONSE_TIME_TAKEN, [
            'promises_response_time_taken'  =>  $endTime - $startTime,
            'concurrent_call'               =>  true,
        ]);

        return $data;
    }

    private function getGuzzleClient(): Guzzle
    {
        $guzzleClient = new Guzzle([
            'base_uri' => ApiUrl::getApiBaseUrl(),
            'defaults' => [
                'timeout' => Config::get('api.request_timeout'),
            ]
        ]);

        return $guzzleClient;
    }
    /**
     * @throws BadRequestError
     */
    private function getApiPromiseAnyForParallelApiCall($params, $currentMerchant, $merchant, $data): array
    {
        $startTime = self::millitime();

        $apiPromiseAny = [];

        $currentRouteName = \Route::currentRouteName();

        $serverName = \Request::server('SERVER_NAME');

        $splitzExperiments = $params[Constants::SPLITZ_EXPERIMENTS] ?? "1";
        $experiments = $params[Constants::EXPERIMENTS] ?? "1";

        $isBankingRequest = ApiUrl::isBankingOriginRequest();

        $merchantService = new Merchant\Service([AppConstants::HTTP_CLIENT => $this->httpClient]);

        $currentMerchantId = $currentMerchant->id;

        // API 1.1
        if ((($this->isPgRenderCall($currentRouteName, $serverName) === false) or
                ($this->isFieldExcluededInPgRendering(Constants::EXPERIMENTS) === false)) and
            ($experiments === "1") and empty($data[Constants::EXPERIMENTS]))
        {
            $start = self::millitime();
            $promise = $merchantService->getExperimentPromise();
            $apiPromiseAny[self::EXPERIMENT_PROMISE] = new ApiPromiseAny('razorx/bulkevaluate','GET');
            $apiPromiseAny[self::EXPERIMENT_PROMISE]->setPromise($promise);
            $this->trace->info(TraceCode::PROMISE_BUILT_TIME, [
                'uri'                       => 'razorx/bulkevaluate',
                'promises_built_time_taken' =>  self::millitime() - $start
            ]);
        }

        // API 1.2
        if ((($this->isPgRenderCall($currentRouteName, $serverName) === false)  or
                ($this->isFieldExcluededInPgRendering(Constants::SPLITZ_EXPERIMENTS) === false)) and
            ($splitzExperiments === "1") and empty($data[Constants::SPLITZ_EXPERIMENTS]))
        {
            $start = self::millitime();
            $promise = (new SplitzService([AppConstants::HTTP_CLIENT => $this->httpClient]))->getSplitzVariantBulkAsyncPromise($currentMerchantId);

            if (!empty($promise))
            {
                $apiPromiseAny[self::SPLITZ_EXPERIMENT_PROMISE] =  new ApiPromiseAny('splitz/bulkEvaluateProxy','POST');
                $apiPromiseAny[self::SPLITZ_EXPERIMENT_PROMISE]->setPromise($promise);
                $this->trace->info(TraceCode::PROMISE_BUILT_TIME, [
                    'uri'                       => 'splitz/bulkEvaluateProxy',
                    'promises_built_time_taken' =>  self::millitime() - $start
                ]);
            }
        }

        // API 1.3
        if (($isBankingRequest === false) and
            (new Helper)->isOwner($currentMerchant))
        {
            $start = self::millitime();

            $promise = $merchantService->getPartnerIntentAsyncPromise();

            $apiPromiseAny[self::PARTNER_INTENT_PROMISE] =  new ApiPromiseAny('merchant/partner-intent','GET');
            $apiPromiseAny[self::PARTNER_INTENT_PROMISE]->setPromise($promise);

            $this->trace->info(TraceCode::PROMISE_BUILT_TIME, [
                'uri'                       => 'merchant/partner-intent',
                'promises_built_time_taken' =>  self::millitime() - $start
            ]);
        }

        // API 1.4
        if (($isBankingRequest === false) and
            (empty($data['merchants'][$merchant['id']]['partner_type']) === false))
        {
            $start = self::millitime();

            // if the merchant is a partner
            $data['merchants'][$merchant['id']]['partner'] = [];

            // API 1.4.1
            $promise = $merchantService->fetchPartnerConfigsAsyncPromise();
            $apiPromiseAny[self::CONFIG_PROMISE] =  new ApiPromiseAny('merchants/me/partner/configs','GET');
            $apiPromiseAny[self::CONFIG_PROMISE]->setPromise($promise);

            $this->trace->info(TraceCode::PROMISE_BUILT_TIME, [
                'uri'                       => 'merchants/me/partner/configs',
                'promises_built_time_taken' =>  self::millitime() - $start
            ]);
        }

        $endTime = self::millitime();

        $this->trace->info(TraceCode::API_PROMISE_BUILT_TIME_TAKEN, [
            'promises_built_time_taken'  =>  $endTime - $startTime,
            'concurrent_call'               =>  true,
        ]);

        return $apiPromiseAny;
    }

    public function getSecondChunkUserDetailsConcurrent(array $chunkData = [], array $params = [])
    {
        $currentRouteName = \Route::currentRouteName();

        $serverName = \Request::server('SERVER_NAME');

        $tags = $params[Constants::TAGS] ?? "1";
        $features = $params[Constants::FEATURES] ?? "1";
        $payouts = $params[Constants::PAYOUTS] ?? "1";
        $fetchMerchantDetails = $params[Constants::MERCHANT_DETAILS] ?? "1";

        $user = Auth::user();

        $data = $chunkData['details'];

        $merchants = $chunkData['details']['user']['merchants'];

        $genericUser = $chunkData['genericUser'] ?? null;

        $currentMerchant =  $chunkData['currentMerchant'] ?? null;

        $activated = false;

        $currentMerchantId = null;

        if(!is_null($currentMerchant))
        {
            $currentMerchantId = $currentMerchant->id;
        }

        $merchantService = new Merchant\Service([AppConstants::HTTP_CLIENT => $this->httpClient]);

        // If the user is logged in as someone
        if ($currentMerchantId)
        {
            //
            // This is temporary code to get merchant waitlist
            // This code will be removed, in few weeks
            //
            $data['current_account_waitlist_number'] = Merchant\Constants::MERCHANT_WAITLIST[$currentMerchantId] ?? null;

            /**
             * Currently we are assigning $activated = true, even if one merchant associated to the user is activated.
             * The same $activated flag is being used to fill pre_signup_complete. (UI uses this flag to render pre signup page)
             * Propagating the same to updateMerchantDetails() function so that it will not impact the existing functionality*
             * Slack thread: https://razorpay.slack.com/archives/C2CP46QBW/p1639979762325500
             */
            foreach ($merchants as $merchant)
            {
                if (((bool)$merchant['activated']) === true)
                {
                    $activated = true;
                }
            }

            // Fetch merchant details for current merchant
            if ($fetchMerchantDetails === "1")
            {
                $data = (new MerchantDetails\Service())->updateMerchantDetails($data, $activated, $currentMerchant, $genericUser);
            }

            $this->traceMerchantActivatedTruthyValue($data, __LINE__);

            foreach ($merchants as $merchant)
            {
                $data['merchants'][$merchant['id']] = $merchant;

                if ($merchant['id'] === $currentMerchantId)
                {
                    $globalMerchant = $merchant;

                    $isBankingRequest = ApiUrl::isBankingOriginRequest();

                    if ($payouts === "1")
                    {
                        $data = $this->appendBankingDetails($data);
                    }

                    $data['current'] = $currentMerchantId;

                    if ((($this->isPgRenderCall($currentRouteName, $serverName) === false) or
                            ($this->isFieldExcluededInPgRendering(Constants::TAGS) === false)) and
                        ($tags === "1"))
                    {
                        $data['tags'] = $merchantService->getMerchantTags($currentMerchantId);
                    }

                    if ((($this->isPgRenderCall($currentRouteName, $serverName) === false) or
                            ($this->isFieldExcluededInPgRendering(Constants::FEATURES) === false)) and
                        ($features === "1"))
                    {
                        $data['features'] = $merchantService->getMerchantFeatures();
                    }

                    if ((($this->isPgRenderCall($currentRouteName, $serverName) === false) or
                            ($this->isFieldExcluededInPgRendering(Constants::CAMPAIGNS) === false)) and
                        ($isBankingRequest === false))
                    {
                        // adding this only for PG, if moving campaigns to X, an extra parameter merchant=x is being sent
                        // which is causing validation failure
                        // refer this: https://razorpay.slack.com/archives/C6QPQKVLZ/p1599729634355800
                        $data['campaigns'] = $merchantService->getMerchantActiveCampaigns();
                    }

                    // Make switch product call only if
                    // 1. Request is banking request and banking_role is null
                    // 2. Request is pg request and role is null
                    //
                    if ((($isBankingRequest === true) and ($data['banking_role'] === null)) or
                        (($isBankingRequest === false) and ($data['role'] === null)))
                    {
                        $data = $this->switchProduct($data, $user);
                    }

                    $isSplitzCachingEnabled = $params[Constants::SPLITZ_API_CACHING_ENABLED];

                    $data[Constants::SPLITZ_EXPERIMENTS] = [];

                    if($isSplitzCachingEnabled) {

                        $splitzExperiments = (new SplitzService([AppConstants::HTTP_CLIENT => $this->httpClient]))->getCacheByIdAsyncPromise($currentMerchantId);

                        if($splitzExperiments) {

                            $data[Constants::SPLITZ_EXPERIMENTS] = $splitzExperiments;

                        }
                    }

                    $isRazorxCachingEnabled = $params[Constants::RAZORX_CACHING_ENABLED];

                    $data[Constants::EXPERIMENTS] = [];

                    if($isRazorxCachingEnabled) {

                        $razorxExperiments = (new Razorx\Service())->getRazorxCacheByIdAsyncPromise($currentMerchantId);

                        if($razorxExperiments) {

                            $data[Constants::EXPERIMENTS] = $razorxExperiments;

                            $data = $this->updateNewUsersOnlyTypeExperiments($globalMerchant, $data);

                            $data = $this->updateRXCASelfServeExperiment($globalMerchant, $data);

                        }
                    }

                    $apiPromiseAny = $this->getApiPromiseAnyForParallelApiCall($params, $currentMerchant, $merchant, $data);

                    $data = $this->getDataFromApiPromiseResponse($data, $globalMerchant, $apiPromiseAny, $currentMerchant, $isSplitzCachingEnabled, $isRazorxCachingEnabled);
                }
            }
        }

        // This is to stop leads assigning to sales poc on salesforce
        if ($data['pre_signup_complete'] === false and array_key_exists('rx_ca_self_serve_flow_neo', $data['experiments']) === true)
        {
            if ($data['experiments']['rx_ca_self_serve_flow_neo'] === ['result' => 'on'])
            {
                $payload = [
                    'merchant_id' => $currentMerchantId,
                    'x_onboarding_category'   => 'self_serve'
                ];

                $this->createLeadToSalesforce($payload, $currentMerchantId);
            }
        }

        $this->traceMerchantActivatedTruthyValue($data, __LINE__);

        if (isset($data['activated']) === true) {
            $data['activated'] = (int) $data['activated'];
        }

        return [[], ['details' => $data]];
    }

    public function setResponseForEachApiPromises(array $apiPromiseAny, array $allApiResponses): void
    {
        foreach ($allApiResponses as $key => $apiResponse)
        {
            if (in_array($key,self::PROMISES_PARALLEL_API_CALL,true))
            {
                $apiPromiseAny[$key]->setApiResponse(array_get($apiResponse,'value'), array_get($apiResponse,'reason'));
            }
        }
    }

    public function setStartTimeForAllApiPromises(array $apiPromiseAny): void
    {
        $startTime = round(microtime(true) * 1000);

        foreach ($apiPromiseAny as $key => $apiPromise)
        {
            if (in_array($key,self::PROMISES_PARALLEL_API_CALL,true))
            {
                $apiPromise->setStartTime($startTime);
            }
        }
    }

    public function getAllApiPromises(array $apiPromiseAny): array
    {
        $allPromise = [];

        foreach ($apiPromiseAny as $key => $apiPromise)
        {
            if (in_array($key,self::PROMISES_PARALLEL_API_CALL,true))
            {
                $allPromise[$key] = $apiPromise->getPromise();
            }
        }

        return $allPromise;
    }

    public function getSecondChunkUserDetails(array $chunkData = [], array $params = [])
    {
        $currentRouteName = \Route::currentRouteName();

        $serverName = \Request::server('SERVER_NAME');

        $tags = $params[Constants::TAGS] ?? "1";
        $features = $params[Constants::FEATURES] ?? "1";
        $splitzExperiments = $params[Constants::SPLITZ_EXPERIMENTS] ?? "1";
        $experiments = $params[Constants::EXPERIMENTS] ?? "1";
        $payouts = $params[Constants::PAYOUTS] ?? "1";
        $fetchMerchantDetails = $params[Constants::MERCHANT_DETAILS] ?? "1";

        $user = Auth::user();

        $data = $chunkData['details'];

        $merchants = $chunkData['details']['user']['merchants'];

        $genericUser = $chunkData['genericUser'] ?? null;

        $currentMerchant =  $chunkData['currentMerchant'] ?? null;

        $activated = false;

        $currentMerchantId = null;

        if(!is_null($currentMerchant))
        {
            $currentMerchantId = $currentMerchant->id;
        }

        // If the user is logged in as someone
        if ($currentMerchantId)
        {
            //
            // This is temporary code to get merchant waitlist
            // This code will be removed, in few weeks
            //
            $data['current_account_waitlist_number'] = Merchant\Constants::MERCHANT_WAITLIST[$currentMerchantId] ?? null;

            $merchantService = new Merchant\Service;

            /**
             * Currently we are assigning $activated = true, even if one merchant associated to the user is activated.
             * The same $activated flag is being used to fill pre_signup_complete. (UI uses this flag to render pre signup page)
             * Propagating the same to updateMerchantDetails() function so that it will not impact the existing functionality*
             * Slack thread: https://razorpay.slack.com/archives/C2CP46QBW/p1639979762325500
             */
            foreach ($merchants as $merchant)
            {
                if (((bool)$merchant['activated']) === true) {
                    $activated = true;
                }
            }

//           Fetch merchant details for current merchant
            if($fetchMerchantDetails === "1")
            {
                $data = (new MerchantDetails\Service())->updateMerchantDetails($data, $activated, $currentMerchant, $genericUser);
            }

            $this->traceMerchantActivatedTruthyValue($data, __LINE__);

            foreach ($merchants as $merchant) {

                $data['merchants'][$merchant['id']] = $merchant;

                if ($merchant['id'] === $currentMerchantId)
                {
                    if ((($this->isPgRenderCall($currentRouteName, $serverName) === false) or
                            ($this->isFieldExcluededInPgRendering(Constants::EXPERIMENTS) === false)) and
                        ($experiments === "1"))
                    {
                        $this->trace->info(
                            TraceCode::MERCHANT_EXPERIMENTS,
                            [
                                'action' => 'FetchStarted'
                            ]
                        );

                        $experiments = $merchantService->getExperiments($params[Constants::RAZORX_CACHING_ENABLED], $currentMerchantId);

                        $data['experiments'] = $experiments;

                        $this->trace->info(TraceCode::USER_LOGIN, [
                            'experiments' =>  $experiments,
                        ]);

                        $data = $this->updateNewUsersOnlyTypeExperiments($merchant, $data);

                        $data = $this->updateRXCASelfServeExperiment($merchant, $data);

                        $this->trace->info(
                            TraceCode::MERCHANT_EXPERIMENTS, [
                                'action' => 'FetchEnded',
                                'data' => $data['experiments']
                            ]
                        );
                    }

                    $isBankingRequest = ApiUrl::isBankingOriginRequest();

                    if($payouts === "1")
                    {
                        $data = $this->appendBankingDetails($data);
                    }

                    $data['current'] = $currentMerchantId;

                    if ((($this->isPgRenderCall($currentRouteName, $serverName) === false) or
                            ($this->isFieldExcluededInPgRendering(Constants::TAGS) === false)) and
                        ($tags === "1"))
                    {
                        $data['tags'] = $merchantService->getMerchantTags($currentMerchantId);
                    }

                    if ((($this->isPgRenderCall($currentRouteName, $serverName) === false) or
                            ($this->isFieldExcluededInPgRendering(Constants::FEATURES) === false)) and
                        ($features === "1"))
                    {
                        $data['features'] = $merchantService->getMerchantFeatures();
                    }

                    if ((($this->isPgRenderCall($currentRouteName, $serverName) === false)  or
                            ($this->isFieldExcluededInPgRendering(Constants::SPLITZ_EXPERIMENTS) === false)) and
                        ($splitzExperiments === "1"))
                    {
                        $data[Constants::SPLITZ_EXPERIMENTS] = (new SplitzService())->getSplitzVariantBulk($currentMerchantId, $params[Constants::SPLITZ_API_CACHING_ENABLED]);
                    }

                    //
                    // Make switch product call only if
                    // 1. Request is banking request and banking_role is null
                    // 2. Request is pg request and role is null
                    //
                    if ((($isBankingRequest === true) and ($data['banking_role'] === null)) or
                        (($isBankingRequest === false) and ($data['role'] === null)))
                    {
                        $data = $this->switchProduct($data, $user);
                    }

                    if (($isBankingRequest === false))
                    {
                        if (($this->isPgRenderCall($currentRouteName, $serverName) === false) or
                            ($this->isFieldExcluededInPgRendering(Constants::CAMPAIGNS) === false))

                        {
                            // adding this only for PG, if moving campaigns to X, an extra parameter merchant=x is being sent
                            // which is causing validation failure
                            // refer this: https://razorpay.slack.com/archives/C6QPQKVLZ/p1599729634355800
                            $data['campaigns'] = $merchantService->getMerchantActiveCampaigns();
                        }

                        // Fetch partner intent incase current merchant has owner role
                        if ((new Helper)->isOwner($currentMerchant))
                        {
                            $data['partner_intent'] = $merchantService->getPartnerIntent();
                        }

                        // if the merchant is a partner
                        if (empty($data['merchants'][$merchant['id']]['partner_type']) === false)
                        {
                            $data['merchants'][$merchant['id']]['partner'] = [];

                            $configs = $merchantService->fetchPartnerConfigs();

                            if (empty($configs) === false)
                            {
                                foreach ($configs as $config)
                                {
                                    if ($config[Merchant\Constants::COMMISSION_MODEL] === Merchant\Constants::COMMISSION)
                                    {
                                        $data['merchants'][$merchant['id']]['partner']['has_commission_configs'] = true;
                                    }
                                    else if ($config[Merchant\Constants::COMMISSION_MODEL] === Merchant\Constants::SUBVENTION)
                                    {
                                        $data['merchants'][$merchant['id']]['partner']['has_subvention_configs'] = true;
                                    }
                                }
                            }

                        }
                    }
                }
            }
        }

        // This is to stop leads assigning to sales poc on salesforce
        if ($data['pre_signup_complete'] === false and array_key_exists('rx_ca_self_serve_flow_neo', $data['experiments']) === true)
        {
            if ($data['experiments']['rx_ca_self_serve_flow_neo'] === ['result' => 'on'])
            {
                $payload = [
                    'merchant_id' => $currentMerchantId,
                    'x_onboarding_category'   => 'self_serve'
                ];

                $this->createLeadToSalesforce($payload, $currentMerchantId);
            }
        }

        $this->traceMerchantActivatedTruthyValue($data, __LINE__);

        if (isset($data['activated']) === true)
        {
            $data['activated'] = (int) $data['activated'];
        }

        return [[], ['details' => $data]];
    }

    public function getUserDetails(array $params = [])
    {
        $currentRouteName = \Route::currentRouteName();

        $serverName = \Request::server('SERVER_NAME');

        $data = [
            'current'   =>  null
        ];

        $errors = (new Validator())->validateInput('user_fetch', $params)->messages();

        if(empty($errors) === false)
        {
            return [$errors, []];
        }

        $tags = $params[Constants::TAGS] ?? "1";
        $features = $params[Constants::FEATURES] ?? "1";
        $splitzExperiments = $params[Constants::SPLITZ_EXPERIMENTS] ?? "1";
        $experiments = $params[Constants::EXPERIMENTS] ?? "1";
        $payouts = $params[Constants::PAYOUTS] ?? "1";
        $fetchMerchantDetails = $params[Constants::MERCHANT_DETAILS] ?? "1";

        $user = Auth::user();

        if (app('request.ctx')->isOauthRequest() === true)
        {
            $userId = app('request.ctx')->getUserId();
        }

        if (!$user &&
            empty($userId) === true)
        {
            return [['Not logged in'], null];
        }
        else if (empty($userId) === true)
        {
            $userId = $user->id;
        }

        list($error, $genericUser) = $this->getUserFromApi($userId);

        if (empty($error) === false)
        {
            return [$error, $data];
        }

        $userDetails = $genericUser->toArray();

        $userDetails[Constants::TWO_FA_VERIFIED] = Session::get(
            Constants::TWO_FA_VERIFIED,
            false); //default value is false

        //default value is false
        $userDetails[Constants::OAUTH_LOGIN] = Session::get(Constants::OAUTH_LOGIN, false);

        $merchants = $userDetails['merchants'];

        $data['user'] = $userDetails;

        $activated = false;

        // Default values in case no merchant is associated
        // with the user account
        $data['pre_signup'] = [];
        $data['pre_signup_complete'] = true;
        $data['experiments'] = [];
        $data['tags'] = [];
        $data['features'] = [];
        $data['campaigns'] = [];

        $currentMerchant = (new Helper)->getCurrentMerchant($genericUser);

        if ($currentMerchant === null)
        {
            return [[], $data];
        }

        $data = $data + $currentMerchant->toArray();

        $this->traceMerchantActivatedTruthyValue($data, __LINE__);

        if ($currentMerchant->role === 'owner')
        {
            $data['primaryOwner'] = true;
        }
        else
        {
            $data['primaryOwner'] = false;
        }

        $currentMerchantId = $currentMerchant->id;

        // If the user is logged in as someone
        if ($currentMerchantId)
        {
            //
            // This is temporary code to get merchant waitlist
            // This code will be removed, in few weeks
            //
            $data['current_account_waitlist_number'] = Merchant\Constants::MERCHANT_WAITLIST[$currentMerchantId] ?? null;

            $merchantService = new Merchant\Service;


            /**
             * Currently we are assigning $activated = true, even if one merchant associated to the user is activated.
             * The same $activated flag is being used to fill pre_signup_complete. (UI uses this flag to render pre signup page)
             * Propagating the same to updateMerchantDetails() function so that it will not impact the existing functionality*
             * Slack thread: https://razorpay.slack.com/archives/C2CP46QBW/p1639979762325500
             */
            foreach ($merchants as $merchant)
            {
                if (((bool)$merchant['activated']) === true) {
                    $activated = true;
                }
            }

            // Fetch merchant details for current merchant
            if($fetchMerchantDetails === "1")
            {
                $data = (new MerchantDetails\Service())->updateMerchantDetails($data, $activated, $currentMerchant, $genericUser);
            }

            $this->traceMerchantActivatedTruthyValue($data, __LINE__);

            foreach ($merchants as $merchant) {

                $data['merchants'][$merchant['id']] = $merchant;

                if ($merchant['id'] === $currentMerchantId)
                {
                    if ((($this->isPgRenderCall($currentRouteName, $serverName) === false) or
                            ($this->isFieldExcluededInPgRendering(Constants::EXPERIMENTS) === false)) and
                        ($experiments === "1"))
                    {
                        $this->trace->info(
                            TraceCode::MERCHANT_EXPERIMENTS,
                            [
                                'action' => 'FetchStarted'
                            ]
                        );

                        $experiments = $merchantService->getExperiments();

                        $data['experiments'] = $experiments;

                        $this->trace->info(TraceCode::USER_LOGIN, [
                            'experiments' =>  $experiments,
                        ]);

                        $data = $this->updateNewUsersOnlyTypeExperiments($merchant, $data);

                        $data = $this->updateRXCASelfServeExperiment($merchant, $data);

                        $this->trace->info(
                            TraceCode::MERCHANT_EXPERIMENTS, [
                                'action' => 'FetchEnded',
                                'data' => $data['experiments']
                            ]
                        );
                    }

                    $isBankingRequest = ApiUrl::isBankingOriginRequest();

                    if($payouts === "1")
                    {
                        $data = $this->appendBankingDetails($data);
                    }

                    $data['current'] = $currentMerchantId;

                    if ((($this->isPgRenderCall($currentRouteName, $serverName) === false) or
                            ($this->isFieldExcluededInPgRendering(Constants::TAGS) === false)) and
                        ($tags === "1"))
                    {
                        $data['tags'] = $merchantService->getMerchantTags($currentMerchantId);
                    }

                    if ((($this->isPgRenderCall($currentRouteName, $serverName) === false) or
                            ($this->isFieldExcluededInPgRendering(Constants::FEATURES) === false)) and
                        ($features === "1"))
                    {
                        $data['features'] = $merchantService->getMerchantFeatures();
                    }

                    if ((($this->isPgRenderCall($currentRouteName, $serverName) === false)  or
                            ($this->isFieldExcluededInPgRendering(Constants::SPLITZ_EXPERIMENTS) === false)) and
                        ($splitzExperiments === "1"))
                    {
                        $data[Constants::SPLITZ_EXPERIMENTS] = (new SplitzService())->getSplitzVariantBulk($currentMerchantId);
                    }

                    //
                    // Make switch product call only if
                    // 1. Request is banking request and banking_role is null
                    // 2. Request is pg request and role is null
                    //
                    if ((($isBankingRequest === true) and ($data['banking_role'] === null)) or
                        (($isBankingRequest === false) and ($data['role'] === null)))
                    {
                        $data = $this->switchProduct($data, $user);
                    }

                    if (($isBankingRequest === false))
                    {
                        if (($this->isPgRenderCall($currentRouteName, $serverName) === false) or
                            ($this->isFieldExcluededInPgRendering(Constants::CAMPAIGNS) === false))

                        {
                            // adding this only for PG, if moving campaigns to X, an extra parameter merchant=x is being sent
                            // which is causing validation failure
                            // refer this: https://razorpay.slack.com/archives/C6QPQKVLZ/p1599729634355800
                            $data['campaigns'] = $merchantService->getMerchantActiveCampaigns();
                        }

                        // Fetch partner intent incase current merchant has owner role
                        if ((new Helper)->isOwner($currentMerchant))
                        {
                            $data['partner_intent'] = $merchantService->getPartnerIntent();
                        }

                        // if the merchant is a partner
                        if (empty($data['merchants'][$merchant['id']]['partner_type']) === false)
                        {
                            $data['merchants'][$merchant['id']]['partner'] = [];

                            $configs = $merchantService->fetchPartnerConfigs();

                            if (empty($configs) === false)
                            {
                                foreach ($configs as $config)
                                {
                                    if ($config[Merchant\Constants::COMMISSION_MODEL] === Merchant\Constants::COMMISSION)
                                    {
                                        $data['merchants'][$merchant['id']]['partner']['has_commission_configs'] = true;
                                    }
                                    else if ($config[Merchant\Constants::COMMISSION_MODEL] === Merchant\Constants::SUBVENTION)
                                    {
                                        $data['merchants'][$merchant['id']]['partner']['has_subvention_configs'] = true;
                                    }
                                }
                            }

                            if(in_array($data['merchants'][$merchant['id']]['partner_type'], Constants::PARTNER_ACTIVATION_APPLICABLE_TYPES))
                            {
                                $data['merchants'][$merchant['id']]['partner']['activation_status'] = $merchantService->fetchPartnerActivationStatus();
                            }
                        }
                    }
                }
            }
        }

        // This is to stop leads assigning to sales poc on salesforce
        if ($data['pre_signup_complete'] === false and array_key_exists('rx_ca_self_serve_flow_neo', $data['experiments']) === true)
        {
            if ($data['experiments']['rx_ca_self_serve_flow_neo'] === ['result' => 'on'])
            {
                $payload = [
                    'merchant_id' => $currentMerchantId,
                    'x_onboarding_category'   => 'self_serve'
                ];

                $this->createLeadToSalesforce($payload, $currentMerchantId);
            }
        }

        $this->traceMerchantActivatedTruthyValue($data, __LINE__);

        if (isset($data['activated']) === true)
        {
            $data['activated'] = (int) $data['activated'];
        }

        return [[], $data];
    }

    /**
     * It fetches all the user info, user details and pre-signup information
     * specifically for mobile app. Since the latency is high for getUserDetails.
     * This route is being used by mobile app.
     *
     * @return array
     */
    public function getUserDetailsForMobile()
    {
        $data = [];

        $user = Auth::user();

        if (!$user)
        {
            return [['Not logged in'], null];
        }

        list($error, $genericUser) = $this->getUserFromApi($user->id);

        if (empty($error) === false)
        {
            return [$error, $data];
        }

        $userDetails = $genericUser->toArray();

        $userDetails[Constants::TWO_FA_VERIFIED] = Session::get(
            Constants::TWO_FA_VERIFIED,
            false); //default value is false

        //default value is false
        $userDetails[Constants::OAUTH_LOGIN] = Session::get(Constants::OAUTH_LOGIN, false);

        $data['user'] = $userDetails;

        $currentMerchant = (new Helper)->getCurrentMerchant($genericUser);

        if ($currentMerchant === null)
        {
            return [[], $data];
        }

        if ($currentMerchant->role === 'owner')
        {
            $data['primaryOwner'] = true;
        }
        else
        {
            $data['primaryOwner'] = false;
        }

        $currentMerchantId = $currentMerchant->id;

        // Default values in case no merchant is associated
        // with the user account
        $data['pre_signup']          = [];
        $data['pre_signup_complete'] = true;

        // If the user is logged in as someone
        if ($currentMerchantId)
        {
            $merchantService = new Merchant\Service;

            $data["pre_signup"] = $merchantService->getPreSignupDetails($currentMerchantId);

            // with mobile signup going live, only contact name is used
            // as a pre_signup completeness check
            // This is same as on UserController
            $data['pre_signup_complete'] = (
                isset($data['pre_signup'][Merchant\Entity::CONTACT_NAME])
                AND (strlen($data['pre_signup'][Merchant\Entity::CONTACT_NAME]) !== 0)
            );

            $merchantDetailService = new MerchantDetails\Service;
            // for non-registered check if pre_signup_complete done or not;

            if ($merchantDetailService->isExperimentOnAndIsUnregisteredBusinessType($data) === true)
            {
                if (($merchantDetailService->isPreSignupDetailsSetForNotRegisteredBusiness($data['pre_signup'])) === true)
                {
                    $data['pre_signup_complete'] = true;
                }
            }

            if ((isset($data['activation_status']) === true) and ($data['activation_status'] !== null))
            {
                $data['pre_signup_complete'] = true;
            }

            if (($currentMerchant->role !== 'owner') and
                ($currentMerchant->banking_role !== 'owner'))
            {
                $data['pre_signup_complete'] = true;
            }
        }

        return [[], $data];
    }

    /**
     * On Page load when a user doens't have role for a particular product which he is trying to access.
     * User Product sync will sync the roles and roles need to be updated on html view.
     *
     * @param $data
     * @param $user
     *
     * @return array
     */
    private function updateUserDetails($data, $user)
    {
        list($error, $genericUser) = $this->getUserFromApi($user->id);

        if (empty($error) === false)
        {
            return [$error, $data];
        }

        $data['user'] = $genericUser->toArray();

        $currentMerchant = (new Helper([AppConstants::HTTP_CLIENT => $this->httpClient]))->getCurrentMerchant($genericUser);

        if ($currentMerchant === null)
        {
            return [[], $data];
        }

        $data =  $currentMerchant->toArray() + $data;

        $data['merchants'][$currentMerchant->id] = $currentMerchant->toArray();

        return $data;
    }

    public function loginOnApiOnRoute(array $input, string $route, string $httpVerb, array $options=[])
    {
        $request = new \App\Admin\ApiRequestAny($options);

        list($error, $data, $httpCode) = $request->processInput($input)->send($route, $httpVerb);

        $genericUser = null;

        if (empty($error) === true)
        {
            $genericUser = (new Helper)->createdGenericUser($data);
        }
        else
        {
            $email = $input['email'] ?? '';
            $this->trace->info(
                TraceCode::USER_LOGIN_FAILURE,
                ['error' => $error, 'email' => $email]);
        }


        return [$error, $genericUser, $httpCode];
    }

    public function loginOtpRoute(array $input, string $route, string $httpVerb, array $options=[])
    {
        $request = new \App\Admin\ApiRequestAny($options);

        list($error, $data, $httpCode) = $request->processInput($input)->send($route, $httpVerb);

        return [$error, $data, $httpCode];
    }

    public function requestAPI(array $input, string $route, string $httpVerb, array $options=[])
    {
        $request = new ApiRequestAny($options);

        list($error, $data, $httpCode) = $request->processInput($input)->send($route, $httpVerb);

        return [$error, $data, $httpCode];
    }

    public function userVerificationRoute(array $input, string $route, string $httpVerb, array $options=[])
    {
        $request = new \App\Admin\ApiRequestAny($options);

        list($error, $data) = $request->processInput($input)->send($route, $httpVerb);

        return [$error, $data];
    }

    /**
     * @param array  $input
     * @param string $route
     * @param string $httpVerb
     *
     * @return array
     */
    public function oauthLoginOnApiOnRoute(array $input, string $route, string $httpVerb): array
    {
        /**
         * This is a temporary fix
         * for more info look 👉🏻 https://razorpay.atlassian.net/browse/MCOB-3309
         *  */
        if(isset($input['email']) === true
            and (in_array($input['email'], Constants::BLOCKED_EMAILS_FOR_LOGIN, true) === true))
        {
            return [
                [
                    "No db records found."
                ], null, 400
            ];
        }

        $input[Constants::OAUTH_SOURCE] = Request::header(Headers::OAUTH_SOURCE) ?? Constants::DASHBOARD;
        $input[Constants::OAUTH_PROVIDER] = json_encode(array(array_get($input, Constants::OAUTH_PROVIDER,'')));

        $request = new ApiRequestAny();

        $genericUser = null;

        $credentials = [
            Constants::EMAIL          => $input[Constants::EMAIL],
            Constants::ID_TOKEN       => $input[Constants::ID_TOKEN],
            Constants::OAUTH_PROVIDER => $input[Constants::OAUTH_PROVIDER],
            Constants::OAUTH_SOURCE   => $input[Constants::OAUTH_SOURCE],
        ];

        if (isset($input[Constants::DEFAULT_MERCHANT_ID])) {
            $credentials[Constants::DEFAULT_MERCHANT_ID] = $input[Constants::DEFAULT_MERCHANT_ID];
        }

        if(empty($input[Constants::REFERRAL_CODE]) === false)
        {
            $credentials[Constants::REFERRAL_CODE] = $input[Constants::REFERRAL_CODE];
        }

        list($error, $data, $httpCode) = $request->processInput($credentials)->send($route, $httpVerb);

        if (empty($error) === true)
        {
            $genericUser = (new Helper)->createdGenericUser($data);

            Session::put(Constants::OAUTH_LOGIN, true);
        }
        else
        {
            $email = $input[Constants::EMAIL] ?? '';

            $this->trace->info(
                TraceCode::USER_LOGIN_FAILURE,
                [Constants::ERROR => $error, Constants::EMAIL => $email]);
        }

        return [$error, $genericUser, $httpCode];
    }

    public function loginOnApi(array $input)
    {
        /**
         * This is a temporary fix
         * for more info look 👉🏻 https://razorpay.atlassian.net/browse/MCOB-3309
         *  */
        if(isset($input['email']) === true
            and (in_array($input['email'], Constants::BLOCKED_EMAILS_FOR_LOGIN, true) === true))
        {
            return [
                [
                    "No db records found."
                ], null, 400
            ];
        }

        $headers = [
            self::CAPTCHA_MODE_HEADER   => Request::header(self::CAPTCHA_MODE_HEADER),
        ];

        $this->checkOauthProviderInPayload(
            $input,
            TraceCode::USER_LOGIN_OAUTH_PROVIDER_ERROR,
            MetricConstants::USER_LOGIN_REQUEST_WITH_OAUTH_PROVIDER_COUNT
        );

        $this->traceApiTrigger(
            $input,
            TraceCode::USER_LOGIN_TRIGGERED,
            MetricConstants::USER_LOGIN_TRIGGERED_COUNT,
            MetricConstants::PASSWORD,
            true
        );

        return $this->loginOnApiOnRoute($input,'users/login', 'POST', [ 'headers' => $headers ]);
    }

    public function otpLoginOnApi(array $input)
    {
        $this->traceApiTrigger(
            $input,
            TraceCode::SEND_LOGIN_OTP_TRIGGERED,
            MetricConstants::SEND_LOGIN_OTP_TRIGGERED_COUNT,
            MetricConstants::OTP,
            true
        );

        unset($input[Constants::REQUEST_SOURCE]);

        /**
         * This is a temporary fix
         * for more info look 👉🏻 https://razorpay.atlassian.net/browse/MCOB-3309
         *  */

        if(isset($input['contact_mobile']) === true)
        {
            foreach (Constants::PHONE_NUMBER_EXTENSIONS as $ext)
            {
                foreach (Constants::BLOCKED_NUMBERS_FOR_LOGIN as $number)
                {
                    $phoneNumber = $ext.$number;
                    if($input['contact_mobile'] === $phoneNumber)
                    {
                        return [null, [
                            "token" => "LybyQbDV9CjBFb"
                        ], 200
                        ];
                    }
                }
            }
        }

        return $this->loginOtpRoute($input,'users/login/otp', 'POST');
    }

    public function otpLoginForVerifyUser(array $input)
    {
        return $this->userVerificationRoute($input, 'users/login/verification-otp', 'POST');
    }

    public function verifyOtpLogin2faPasswordOnApi(array $input, $options)
    {

        $options['headers'][self::CAPTCHA_MODE_HEADER] = Request::header(self::CAPTCHA_MODE_HEADER);

        return $this->loginOnApiOnRoute($input,'users/login/otp/2fa', 'POST', $options);
    }

    public function verifyOtpLoginOnApi(array $input)
    {
        $headers = [
            self::CAPTCHA_MODE_HEADER   => Request::header(self::CAPTCHA_MODE_HEADER),
        ];

        $this->traceApiTrigger(
            $input,
            TraceCode::VERIFY_LOGIN_OTP_TRIGGERED,
            MetricConstants::VERIFY_LOGIN_OTP_TRIGGERED_COUNT,
            MetricConstants::OTP,
            true
        );

        unset($input[Constants::REQUEST_SOURCE]);

        return $this->loginOnApiOnRoute(
            $input,'users/login/otp/verify',
            'POST',
            [ 'headers' => $headers ]
        );
    }

    public function verifyVerificationOtpOnApi(array $input)
    {
        $headers = [
            self::CAPTCHA_MODE_HEADER   => Request::header(self::CAPTCHA_MODE_HEADER),
        ];

        return $this->loginOnApiOnRoute(
            $input,
            'users/login/verification-otp/verify',
            'POST',
            [ 'headers' => $headers ]
        );
    }

    // Another route for a successful login. If a uses 2fa is not setup
    // this will allow to set up 2fa while logging in. And if setup is success
    // api returns user object. And dashboard needs to start the session.
    public function loginOnApiBy2faSetupSuccessful(array $input)
    {
        return $this->loginOnApiOnRoute($input,'users/login/2fa_setup/verify-mobile', 'POST');
    }


    public function getUserFromApi($userId)
    {
        $adminUser = Auth::guard('api')->user();

        $startTime = microtime(true) * 1000;

        $this->trace->info(TraceCode::GET_USER_ROUTE_INFO, [
            'action'                => 'FetchStarted',
            'start_time'            => $startTime
        ]);

        $currentMerchantId = Session::get('current_merchant_id');

        if (app('request.ctx')->isOauthRequest() === true)
        {
            $currentMerchantId = app('request.ctx')->getMerchantId();
        }

        if (empty($adminUser) === false)
        {
            $request = new \App\Admin\ApiRequestAny([
                'client_type'           => 'admin',
                AppConstants::HTTP_CLIENT => $this->httpClient
            ]);

            list($error, $data) = $request->send("users-admin/$userId", "GET");
        }
        else
        {
            $request = new \App\Admin\ApiRequestAny([
                'client_type'           => 'user',
                AppConstants::HTTP_CLIENT => $this->httpClient
            ]);
            // if merchant id is present in current session pass it as query param
            // to fetch user call. This is used for fetching signup campign based on user
            $path = "users/$userId";
            if(empty($currentMerchantId) == false)
            {
                $queryParams = [
                    'merchant_id'   => $currentMerchantId,
                ];
                $path = $path.'?'.http_build_query($queryParams);
            }

            list($error, $data) = $request->send($path, "GET");
        }

        $this->trace->info(TraceCode::GET_USER_ROUTE_INFO, [
            'action'                => 'GetUser',
            'admin_user_flow'       => empty($adminUser) === false,
            'error'                 => $error,
            'user_id'               => $userId,
        ]);

        $genericUser = null;

        if (empty($error) === true)
        {
            $genericUser = (new Helper)->createdGenericUser($data);

            // In case of admin doing login as merchant and if merchants-email is associated with > 1000 merchants,
            // There is a chance that current merchant stored in session would not be available in /user/{id} API (limit is 1000)
            // So we are explicitly querying for the currentMerchantId and checking the user has access or not
            // Ref: https://razorpay.slack.com/archives/C3Y0UA0CB/p1635919864023100
            if ($currentMerchantId !== null)
            {
                $currentMerchant = $genericUser->merchants->where('id', $currentMerchantId)->first();

                // if currentMerchant is not in merchants array
                // then check user's access on it using checkAccessOfUserOnMerchant
                // if no error push the returned merchant object in merchants array
                if ($currentMerchant === null)
                {
                    list($error, $data) = $this->checkAccessOfUserOnMerchant($currentMerchantId);

                    if (empty($error) === true)
                    {
                        $genericUser->merchants->push(new GenericMerchant($data['merchant']));
                    }
                }

                Session::put('dashboard_user_payload', $genericUser);
            }

            $this->trace->info(TraceCode::GET_USER_ROUTE_INFO, [
                'action'                => 'GetCurrentMerchant',
                'current_merchant_id'   => $currentMerchantId,
                'error'                 => $error,
                'user_id'               => $userId,
            ]);
        }

        $endTime = microtime(true) * 1000;
        $duration = round($endTime - $startTime);

        $this->trace->info(TraceCode::GET_USER_ROUTE_INFO, [
            'action'              => 'FetchEnded',
            'end_time'            => $endTime,
            'duration'            => $duration,
            'controller'          => app('request')->route()->getAction()['controller']
        ]);

        return [$error, $genericUser];
    }

    public function getPartnerConfig(array $input): array
    {
        $this->trace->info(TraceCode::GET_PARTNER_CONFIG_GUEST, $input);

        $options = [];

        if (Request::header(Headers::ONBOARDING_SIGNATURE))
        {
            $options['headers'][Headers::ONBOARDING_SIGNATURE] = Request::header(Headers::ONBOARDING_SIGNATURE);
        }

        return $this->requestAPI($input,'partner_config_guest', 'GET', $options);
    }

    protected function checkAccessOfUserOnMerchant($merchantId)
    {
        $request = new \App\Admin\ApiRequestAny();

        $queryParams = [
            'merchant_id'   => $merchantId,
        ];

        $path = 'users/access';

        return $request->send($path.'?'.http_build_query($queryParams), 'GET');
    }

    /**
     * generate the redirect URL with the user identity token
     *
     * @param string $clientId
     * @param array $params
     * @return array
     */
    public function generateIdentityToken(string $clientId, array $params): array
    {
        $error = $url = null;

        try
        {
            list($redirectURL, $token) = (new Identity())->generateIdentityToken($clientId, $params);

            $url = $redirectURL . '?token=' . $token->toString();
        }
        catch (BadRequestError $e)
        {
            $error = [
                'code'        => $e->getCode(),
                'description' => $e->getMessage(),
                'status_code' => $e->getHttpStatusCode(),
            ];
        }

        return [$error, $url];
    }

    /**
     *
     * Here we generate a jwt token so that chrome extension consumes the token.
     * Token is generated by a jwt encrypton with a specified expiry time and encrypted using sha256.
     *
     * @return array
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    public function generateJWT()
    {
        $user = Auth::user();

        $currentMerchantId = $user->currentMerchant() ? $user->currentMerchant()->id : null;

        if ((empty($user) === true) or ($currentMerchantId === null))
        {
            throw new BadRequestError(
                "Merchant context not present in user",
                \Razorpay\Api\Errors\ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        $this->trace->info(TraceCode::GENERATE_JWT_DASHBOARD, [
            'merchant_id' => $currentMerchantId,
            'user_id'     => $user->id
        ]);

        $merchant = $user->currentMerchant();

        $merchantData = $merchant->toArray();

        $merchantDetailData = (new MerchantDetails\Service)->fetchDetails();

        $merchantLogo = $merchantData['logo_url'] ?? null;

        $merchantName = $merchantData['name'] ?? null;

        $merchantActivated = $merchantData['activated'] ?? null;

        $merchantInternational = $merchantDetailData['international'] ?? null;

        $signer = new Sha256();

        $sessionConfig = $this->app['config']['session'];

        $jwtEncryptionKey = $sessionConfig['jwt_encryption_key'];

        $tokenExpiry = $sessionConfig['jwt_expiry'] * 60;

        $issuer = parse_url(config('app.url'), PHP_URL_HOST);

        $tokenBuilder = (new Builder(new JoseEncoder(), ChainedFormatter::withUnixTimestampDates()));

        $config = Configuration::forSymmetricSigner($signer, Key\InMemory::plainText($jwtEncryptionKey));

        $sysClock = new SystemClock(new DateTimeZone('UTC'));

        /*
         * $tokenExpiry is of 1day
         * */
        $tokenExpiry_ttl = 'PT' . $tokenExpiry . 'S';

        $token = $tokenBuilder->issuedBy($issuer)
            ->permittedFor(self::EXTENSION)
            ->issuedAt($sysClock->now())
            ->expiresAt($sysClock->now()->add(new \DateInterval($tokenExpiry_ttl)))
            ->withClaim(self::MERCHANT_ID, $currentMerchantId)
            ->withClaim(self::USER_ID, $user->id)
            ->withClaim(self::MERCHANT_ACTIVATED, $merchantActivated)
            ->withClaim('merchant_international', $merchantInternational)
            ->withClaim(self::MERCHANT_LOGO, $merchantLogo)
            ->withClaim(self::MERCHANT_NAME, $merchantName)
            ->getToken($config->signer(), $config->signingKey());

        return [[], ["token" => $token->toString()]];
    }

    /**
     * @throws AuthorizationException
     */
    public function validateJWT(string $token): Token
    {
        if (empty($token) === true)
        {
            throw new AuthorizationException('Token context not present in the request');
        }

        $this->trace->info(TraceCode::VERIFY_JWT_DASHBOARD);

        $token = (new Parser(new JoseEncoder()))->parse($token);

        $signer = new Sha256();

        $issuer = parse_url(config('app.url'), PHP_URL_HOST);

        $sessionConfig = $this->app['config']['session'];

        $jwtEncryptionKey = $sessionConfig['jwt_encryption_key'];

        $config = Configuration::forSymmetricSigner(
            $signer,
            Key\InMemory::plainText($jwtEncryptionKey)
        );

        $config->setValidationConstraints(
            new Constraint\SignedWith($config->signer(), $config->signingKey()),
            new Constraint\PermittedFor(self::EXTENSION),
            new Constraint\IssuedBy($issuer)
        );

        if (!$config->validator()->validate($token, ...$config->validationConstraints()))
        {
            throw new AuthorizationException('Invalid token provided');
        }

        $this->validateMerchantUserIfLoggedIn($token);

        return $token;
    }

    protected function validateMerchantUserIfLoggedIn($token)
    {
        $user = Auth::user();

        if (empty($user) === false)
        {
            $currentMerchantId = $user->currentMerchant() ? $user->currentMerchant()->id : null;

            if (($user->id !== $token->claims()->get(self::USER_ID)) or
                ($currentMerchantId !== $token->claims()->get(self::MERCHANT_ID)))
            {
                throw new AuthorizationException('Different user/merchant is loggedin to the dashboard');
            }

            $this->trace->info(TraceCode::VALIDATE_JWT_USER_DASHBOARD, [
                'merchant_id' => $currentMerchantId,
                'user_id'     => $user->id
            ]);
        }
    }

    public function isRequestOriginSatisfied(array $experimentConfig)
    {
        if (isset($experimentConfig[Constants::REQUEST_ORIGIN]) === true)
        {
            switch ($experimentConfig[Constants::REQUEST_ORIGIN])
            {
                case 'banking':
                    return ApiUrl::isBankingOriginRequest();
                case 'primary':
                    return ApiUrl::isPrimaryOriginRequest();
            }
        }
        return true;
    }

    protected function updateNewUsersOnlyTypeExperiments(array $merchant, array $data): array
    {
        $merchantService = new Merchant\Service;

        foreach (config('razorx.new_signup_experiments_config') as $experimentFeatureFlag => $experimentConfig)
        {
            if (($this->isRequestOriginSatisfied($experimentConfig) === true)
                and ($merchant['created_at'] > $experimentConfig[Constants::TIMESTAMP_THRESHOLD]))
            {
                $data['experiments'][$experimentFeatureFlag] = $merchantService->getTreatment($experimentFeatureFlag);
            }
            else
            {
                $data['experiments'][$experimentFeatureFlag] = $experimentConfig[Constants::DEFAULT_RESULT];
            }
        }
        return $data;
    }

    /**
     * This function excludes users who are on old CA Self_Serve flow
     * and users who did signup to rx within 60 days,
     * and put remaining users to the new CA flow.
     *
     * @param array $merchant
     * @param array $data
     * @return array
     * @throws BadRequestError
     */

    protected function updateRXCASelfServeExperiment(array $merchant, array $data): array
    {
        $merchantService = new Merchant\Service;

        $isBankingRequest = ApiUrl::isBankingOriginRequest();

        if (array_key_exists('business_banking_signup_at', $merchant) === false or $isBankingRequest === false)
        {
            return $data;
        }

        /*
         * This flag is to tell FE whether to show NeoStone, SelfServe or None flow
         * to current account applicants
         */
        $flag = null;

        // This is to make backward compatible, while fetching experiments, we usually fetch this experiment value as well.
        // As we are doing a customization logic here for making it ON, resetting to off by default
        unset($data['experiments']['rx_ca_self_serve_flow_neo']);

        $from_ca_campaign = $this->checkIfFromCaCampaign($merchant);

        if ($from_ca_campaign === true)
        {
            $data['experiments']['rx_ca_self_serve_flow_neo'] = ['result' => 'on'];

            $this->fireNeoStoneEventToHubspot($merchant);
        }

        $from_ca_page = $this->checkIfFromCaPage($merchant);

        /*
         * Front-End uses self-serve as experiment if none of the
         * experiment are on
         * if signed up from CA website
              if signed up before {NEOSTONE_GO_LIVE_DATE}
              {
                flag = ca_self_serve
                EXP => rx_ca_self_serve_flow = on/off
                       rx_ca_self_serve_flow_neo = off
              else use new RazorX experiment
                flag = if (true for X%) neostone else ca_self_serve
            else
              use existing CA self serve RazorX experiment
              if user signed-up 60 days ago
                Exp => rx_non_self_serve_ca_flow = on/off
                       rx_ca_self_serve_flow = on/off
                flag = 30% ca_self_serve
                flag = 70% none
         *
         */

        if ($from_ca_page === true and array_key_exists('rx_ca_self_serve_flow_neo', $data['experiments']) === false)
        {
            if ($merchant['business_banking_signup_at'] < strtotime('25 May 2021'))
            {
                $data['experiments']['rx_ca_self_serve_flow_neo'] = ['result' => 'off'];

                $data['experiments']['rx_ca_self_serve_flow'] = ['result' => 'on'];

                $flag = 'ca_self_serve';
            }
            else
            {
                // Here there is a chance that both rx_ca_self_serve_flow_neo and rx_ca_self_serve_flow
                // experiment may be on
                $data['experiments']['rx_ca_self_serve_flow_neo'] =
                    $merchantService->getTreatment('rx_ca_self_serve_flow_neo');

                if ($data['experiments']['rx_ca_self_serve_flow_neo'] === ['result' => 'on'])
                {
                    $flag = 'neostone';

                    $data['experiments']['rx_ca_self_serve_flow'] = ['result' => 'off'];

                    $this->fireNeoStoneEventToHubspot($merchant);
                }
                else
                {
                    $data['experiments']['rx_ca_self_serve_flow'] = ['result' => 'on'];

                    $flag = 'ca_self_serve';
                }
            }
        }
        else
        {
            if($data['experiments']['rx_ca_self_serve_flow'] !== ['result' => 'on']
                and $merchant['business_banking_signup_at'] < strtotime('- 60 days'))
            {
                $data['experiments']['rx_non_self_serve_ca_flow'] =
                    $merchantService->getTreatment('rx_non_self_serve_ca_flow');
            }
            else
            {
                $data['experiments']['rx_non_self_serve_ca_flow'] = ['result' => 'off'];
            }

            if ($data['experiments']['rx_ca_self_serve_flow'] === ['result' => 'on'])
            {
                $flag = 'ca_self_serve';
            }
            else
            {
                $flag = 'none';
            }
        }

        $data['ca_experiment_flag'] = $flag;

        return $data;
    }

    protected function switchProduct($data, $user)
    {
        $startTime = microtime(true) * 1000;

        $this->trace->info(TraceCode::PRODUCT_SWITCH_ROUTE_INFO, [
            'action'                => 'ProductSwitchInitiated',
            'start_time'            => $startTime
        ]);

        $options = [
            'client_type'           => 'merchant',
            AppConstants::HTTP_CLIENT => $this->httpClient,
        ];

        $request = new \App\Admin\ApiRequestAny($options);

        list($error, $x) = $request->send("merchants/product-switch", "POST");

        $data = $this->updateUserDetails($data, $user);

        $this->traceMerchantActivatedTruthyValue($data, __LINE__);

        $endTime  = microtime(true) * 1000;
        $duration = round($endTime - $startTime);

        $this->trace->info(TraceCode::PRODUCT_SWITCH_ROUTE_INFO, [
            'action'              => 'ProductSwitchCompleted',
            'end_time'            => $endTime,
            'duration'            => $duration,
            'controller'          => app('request')->route()->getAction()['controller']
        ]);

        return $data;
    }

    private function markUserTwoFactorVerified()
    {
        Session::put(Constants::TWO_FA_VERIFIED, true);
    }

    protected function isPartnerIntentTrue(array $data): bool
    {
        return (
            isset($data['partner_intent']) and
            $data['partner_intent'] === true
        );
    }

    protected function appendBankingDetails(array $data): array
    {
        $merchantService = new Merchant\Service;
        $isBankingRequest = ApiUrl::isBankingOriginRequest();

        if ($isBankingRequest)
        {
            $data['banking_details'] = array();

            try {
                $testCount = $merchantService->getPayoutCount('test');
                $data['banking_details']['is_test_payout_created'] = $testCount > 0;
            }
            catch (\Razorpay\Api\Errors\Error $e)
            {
                $data['banking_details']['is_test_payout_created'] = false;
            }

            try {

                if ($data['activation_status'] === 'activated')
                {
                    $liveCount = $merchantService->getPayoutCount('live');
                    $data['banking_details']['is_live_payout_created'] = $liveCount > 0;
                }
                else
                {
                    $data['banking_details']['is_live_payout_created'] = false;
                }
            }
            catch (\Razorpay\Api\Errors\Error $e)
            {
                $data['banking_details']['is_live_payout_created'] = false;
            }
        }

        return $data;
    }

    /*
     * https://razorpay.slack.com/archives/C01HL41R1NF/p1609256502017900
     */
    private function traceMerchantActivatedTruthyValue($data, $line)
    {
        if (isset($data['activated']) === false)
        {
            return;
        }

        $this->trace->info(TraceCode::DEBUG_MERCHANT_TRUTHY_VALUE, [
            'activated' =>  $data['activated'] ?? 'fallback',
            'line'      =>  $line,
        ]);
    }

    private function checkIfFromCaPage(array $merchant): bool
    {
        if (array_key_exists('attributes', $merchant) === true)
        {
            $attributes = $merchant['attributes']['items'];

            foreach ($attributes as $attribute)
            {
                if ($attribute['type'] === 'ca_page_visited')
                {
                    return $attribute['value'] === '1';
                }
            }
        }
        return false;
    }

    private function checkIfFromCaCampaign(array $merchant): bool
    {
        if (array_key_exists('attributes', $merchant) === true)
        {
            $attributes = $merchant['attributes']['items'];

            foreach ($attributes as $attribute)
            {
                if ($attribute['type'] === 'campaign_type')
                {
                    return $attribute['value'] === 'ca_neostone';
                }
            }
        }
        return false;
    }

    private function createLeadToSalesforce(array $payload, string $merchantId)
    {
        $startTime = microtime(true) * 1000;

        $this->trace->info(TraceCode::LEAD_TO_SALESFORCE_ROUTE_INFO, [
            'action'                => 'LeadCreationStarted',
            'start_time'            => $startTime
        ]);

        $request = new ApiRequestAny(['client_type' => 'merchant', AppConstants::HTTP_CLIENT => $this->httpClient]);

        list($error, $data) = $request->processInput($payload)->send("merchants/lead_to_salesforce", "POST");

        if (empty($error) === false)
        {
            $this->trace->error(TraceCode::PUSHED_SALESFORCE_LEAD_TO_API_FAILED, [
                'error'                 => $error,
                'merchant_id'           => $merchantId,
            ]);

            return;
        }

        $this->trace->info(TraceCode::PUSHED_SALESFORCE_LEAD_TO_API, [
            'merchant_id'           => $merchantId,
            'payload'               => $payload
        ]);

        $endTime  = microtime(true) * 1000;
        $duration = round($endTime - $startTime);

        $this->trace->info(TraceCode::LEAD_TO_SALESFORCE_ROUTE_INFO, [
            'action'              => 'LeadCreationEnded',
            'end_time'            => $endTime,
            'duration'            => $duration,
            'controller'          => app('request')->route()->getAction()['controller']
        ]);

    }

    /**
     * $payload = [
     *  merchant_email, HubspotFlags
     * ]
     *
     * @param array  $payload
     * @param string $merchantId
     */
    private function fireEventToHubspotViaApi(array $payload, string $merchantId)
    {
        $startTime = microtime(true) * 1000;

        $this->trace->info(TraceCode::FIRE_EVENT_TO_HUBSPOT_ROUTE_INFO, [
            'action'                => 'FetchStarted',
            'start_time'            => $startTime
        ]);

        $request = new ApiRequestAny(['client_type' => 'merchant']);

        list($error, $data) = $request->processInput($payload)->send("merchants/fire_hubspot_event", "POST");

        if (empty($error) === false)
        {
            $this->trace->error(TraceCode::PUSHED_HUBSPOT_EVENT_TO_API_FAILED, [
                'error'                 => $error,
                'merchant_id'           => $merchantId,
            ]);

            return;
        }

        $this->trace->info(TraceCode::PUSHED_HUBSPOT_EVENT_TO_API, [
            'merchant_id'           => $merchantId,
            'payload'               => $payload
        ]);

        $endTime  = microtime(true) * 1000;
        $duration = round($endTime - $startTime);

        $this->trace->info(TraceCode::FIRE_EVENT_TO_HUBSPOT_ROUTE_INFO, [
            'action'              => 'FetchEnded',
            'end_time'            => $endTime,
            'duration'            => $duration,
            'controller'          => app('request')->route()->getAction()['controller']
        ]);
    }

    private function fireNeoStoneEventToHubspot(array $merchant)
    {
        $adminUser = Auth::guard('api')->user();

        if (empty($adminUser) === false)
        {
            return;
        }

        if (isset($merchant['email']) === false)
        {
            return;
        }

        $merchantEmail = $merchant['email'];

        $merchantId = $merchant['id'];

        $input = [
            'merchant_email'       => $merchantEmail,
            'ca_neostone_eligible' => 'TRUE'
        ];

        $this->fireEventToHubspotViaApi($input, $merchantId);
    }

    // This function is to check if the rendering is done for PG Dashboard of RazorPay org.
    public function isPgRenderCall(string $currentRouteName, string $serverName) : bool
    {
        // PG_DASHBOARD_RENDER_ROUTES contains the routes called for PG rendering
        // dashboard and dashboard_app routes are only called from PG merchant dashboard and not from X dashboard
        // PG_DASHBOARD_SERVER_NAMES contains the names of server for RazorPay org merchant dashboard
        if ((in_array($currentRouteName, Constants::PG_DASHBOARD_RENDER_ROUTES, true) === true) and
            (in_array($serverName, Constants::PG_DASHBOARD_SERVER_NAMES, true) === true))
        {
            return true;
        }

        return false;
    }

    // This function is to decouple the API calls for the fields mentioned in FIELDS_DECOUPLED_FOR_PG_RENDERING
    // during PG rendering to improve the loadtime.
    // Separate API calls are being made to get those field values.
    private function isFieldExcluededInPgRendering(string $field) : bool
    {
        // Fields to be added in FIELDS_DECOUPLED_FOR_PG_RENDERING only if API calls are to be skipped for those fileds.
        if (in_array($field, Constants::FIELDS_DECOUPLED_FOR_PG_RENDERING, true) === true)
        {
            return true;
        }

        return false;
    }

    /**
     * Checks Four Conditions
     * 1) Feature enable_tc_dashboard is enabled on org
     * 2) Feature disable_tc_dashboard is not enabled on merchant
     * 3) Admin_as_merchant is false
     * 4) Merchant has not already accepted the T&C conditions
     * if all the above conditions are satisfied then shouldShowPopUp will be true, in case of errors while calling this api we will return as false
     */
    public function shouldShowPopUp(): bool
    {
        try {
            return (new Merchant\Service())->getShowTncPopup() ;
        }
        catch (\Throwable $e){
            $this->trace->info(TraceCode::GET_TNC_POPUP_ERROR,[
                'Error' => $e->getMessage(),
                'status_code' => $e->getCode(),
            ]);

            return false;
        }
    }

    private function getUserOauthAction(?string $merchantId, $queryParams) : ?string
    {
        if (empty($queryParams[self::SOURCE]) || $queryParams[self::SOURCE] != MerchantConstants::OAUTH_SOURCE || empty($merchantId))
        {
            return null;
        }

        $this->trace->info(TraceCode::FETCH_OAUTH_ACTION_FOR_MERCHANT, ['merchant_id' => $merchantId]);

        $merchantDetails = (new MerchantDetails\Service())->fetchDetails($merchantId);

        $activationStatus = $merchantDetails['activation_status'];
        $suspended_at = $merchantDetails['merchant']['suspended_at'];

        $this->trace->info(TraceCode::OAUTH_MERCHANT_ACTIVATION_DETAILS, [
            'merchant_id'       => $merchantId,
            'suspended_at'      => $suspended_at,
            'activation_status' => $activationStatus
        ]);

        if ($suspended_at == null && in_array($activationStatus, MerchantConstants::ACTIVATION_STATUS_ALLOWED_FOR_OAUTH_ACTION))
        {
            return MerchantConstants::OAUTH_ACTION_RENDER;
        }

        return MerchantConstants::OAUTH_ACTION_REDIRECT;
    }

    public function getMerchantLogin($merchantId): bool
    {
        if (empty($merchantId) === true or $merchantId === null)
        {
            return false;
        }
        $key = Util::getIsMerchantLoginCacheKey($merchantId);
        $isMerchantLogin = $this->cache->get($key);

        if (is_null($isMerchantLogin) === true)
        {
            return false;
        }
        return $isMerchantLogin;
    }
}

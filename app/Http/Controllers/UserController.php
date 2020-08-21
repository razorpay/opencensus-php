<?php
namespace App\Http\Controllers;

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
                'api_host'              => ApiUrl::getApiBaseUrl(),
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
        if (empty($currentRouteName) === false and $currentRouteName === "signup")
        {
            $data['newAuthFlow'] = true; // new pre-signup flow
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
                $data['notifications'] = json_encode((new Merchant\Notifications\Service)->getNotificationsForUser($details));
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

            return view('merchant.index', $data);
        }
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

    public function postRegister()
    {
        $input = Input::all();

        $data = null;

        $error = [];

        try
        {
            list($error, $data) = (new User\Service)->register($input);

            if (empty($error))
            {
                $credentials = [
                    'email'             => $input['email'],
                    'password'          => $input['password'],
                    'captcha_disable'   => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
                ];

                if (Auth::attempt($credentials, false, true) === false)
                {
                    list($error, $data) = (new User\Service)->login($credentials);
                }
            }
        }
        catch (User\RecoverableException $e)
        {
            $error = [$e->getMessage()];
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
        $input = Input::all();

        // Lowercasing emails for consistency
        if (isset($input['email']))
        {
            $input['email'] = mb_strtolower($input['email']);
        }

        $this->trace->info(TraceCode::USER_LOGIN_KEYS, array_keys($input));

        list($error, $data) = (new User\Service)->login($input);

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

        $user->logout();

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
        list($error, $data) = (new User\Service)->getUserDetails();

        if (empty($error) === true)
        {
            // Debug logs for Specific UserId. Will be removed once issue is fixed.
            $userId = $data['user']['id'] ?? null;

            if ($userId === User\Constants::USER_ID_DEBUG_ACTIVATION_ISSUE)
            {
                $activated = $data['activated'] ?? null;

                $this->trace->info(TraceCode::USER_ID_DEBUG, ['userId' => $userId, 'activated' => $activated]);
            }
        }

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

    public function postUnlockUserScreen()
    {
        $input = Input::all();

        $encryptedEmail = Cookie::get('rzp_user_email');

        if (empty($encryptedEmail) === false)
        {
            $email = Crypt::decrypt($encryptedEmail);

            $this->trace->info(TraceCode::USER_UNLOCK_REQUEST, [$email]);
            // replace the email.
            $input['email'] = $email;

            list($error, $data) = (new User\Service)->postloginNo2fa($input);

            $traceData = [
                'email'         => $input['email'],
                'error'         => $error,
                'data'          => $data,
            ];

            $this->trace->info(TraceCode::USER_UNLOCK_RESPONSE, $traceData);
        }
        else
        {
            $error = ["Incorrect password/Network issue, please reload the page"];
            $data = null;
        }

        return AppResponse::jsonResponse($error, $data);
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
}

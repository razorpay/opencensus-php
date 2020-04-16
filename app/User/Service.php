<?php

namespace App\User;

use DB;
use Auth;
use Hash;
use Input;
use Queue;
use Config;
use Session;
use App\Base;
use App\Generic;
use App\Merchant;
use App\AdminLead;
use App\Http\ApiUrl;
use App\Trace\TraceCode;
use App\MerchantDetails;
use App\Providers\GenericUser;
use App\Session as SessionTable;
use App\Merchant\GenericMerchant;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Foundation\Application;
use Lcobucci\JWT\Builder as JWTBuilder;
use Razorpay\Api\Errors\BadRequestError;
use Lcobucci\JWT\Parser as JWTParser;
use Lcobucci\JWT\ValidationData as JWTValidation;
use Illuminate\Auth\Access\AuthorizationException;


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

    // Users who signed up before this date
    // are not exposed to the pre signup flow
    const PRE_SIGNUP_TIMESTAMP = 1488306600;

    /**
     * @var Application
     */
    protected $app;

    protected $trace;

    /**
     * @var Store
     */
    protected $cache;

    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->cache = $app['cache'];
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
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    public function register($input)
    {
        $request = new \App\Admin\ApiRequestAny();

        list($error, $data) = $request->processInput($input)->send('users/register', 'POST');

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
     * @param  array  $input [description]
     *
     * @return array
     */
    public function postSetup2faVerifyOtp(array $input)
    {
        $userId = Session::get('user_id', "");

        if (empty($userId) === false)
        {
            $options['headers']['X-Dashboard-User-Id'] = $userId;

            $options['headers']['X-Dashboard-User-Session-Id'] = Session::getId();
        }
        else
        {
            return [["User Not authenticated, please login"], []];
        }

        list($error, $genericUser) = $this->verify2faOtp($input, $options);

        return $this->handleLoginResponse($error, $genericUser);
    }

    public function postloginNo2fa(array $input)
    {
        list($error, $genericUser) = $this->loginOnApiNo2faSetup($input);

        return $this->handleLoginResponse($error, $genericUser);
    }

    public function verify2faOtp(array $input, array $options = [])
    {
        return $this->loginOnApiOnRoute($input,'users/2fa/verify', 'POST', $options);
    }

    public function requestApiWithBasicSession(array $input, $route, $httpVerb)
    {
        $userId = Session::get('user_id', "");

        if (empty($userId) === false)
        {
            $options['headers']['X-Dashboard-User-Id'] = $userId;

            $options['headers']['X-Dashboard-User-Session-Id'] = Session::getId();
        }
        else
        {
            return [["User Not authenticated, please login"], []];
        }

        $request = new \App\Admin\ApiRequestAny($options);

        list($error, $data) = $request->processInput($input)->send($route, $httpVerb);

        return [$error, $data];
    }

    public function postUpdate2faContact(array $input)
    {
        return $this->requestApiWithBasicSession($input, 'users/2fa_setup/contact_mobile', 'PATCH');
    }

    public function postResendOtp(array $input)
    {
        return $this->requestApiWithBasicSession($input, 'users/2fa/otp_resend', 'POST');
    }

    /**
     * @param  array  $input [description]
     *
     * @return array
     */
    public function login(array $input)
    {
        $res = null;

        list($error, $genericUser) = $this->loginOnApi($input);

        return $this->handleLoginResponse($error, $genericUser);
    }

    protected function handleLoginResponse($error, $genericUser)
    {
        if (empty($error) === false)
        {
            if ((array_key_exists('internal_error_code', $error) === true) and
                (empty($error['internal_error_code']) === false))
            {
                $userId = $error['_internal']['user_details']['user_id'] ??  "";

                if (empty($userId) === false)
                {
                    Session::set('user_id', $userId);
                }
                else
                {
                    return [['User Login Failed, Please check your login credentials.'], null];
                }

                // very very nasty dirty hack to not to write lot of code.
                if ($error['internal_error_code'] === self::BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP)
                {
                    $error = $error['description'];
                }

                return [[$error], null];
            }

            return [['Incorrect email or password. To reset your password click on "Forgot Password" link.'], null];
        }

        Auth::login($genericUser, false);

        $this->app['session']->put('dashboard_user_payload', $genericUser);

        $res = [
            'id' => $genericUser->id,
        ];
        $merchantIds = [];
        foreach ($genericUser->merchants as $merchant)
        {
            $merchantIds[] = $merchant->id;
        }
        $res['merchantIds'] = $merchantIds;

        $user = Auth::user();

        $currentMerchantId = $user->currentMerchant() ? $user->currentMerchant()->id : null;

        $traceData = [
            'id'          => $user->id,
            'merchant_id' => $currentMerchantId,
        ];

        $this->trace->info(TraceCode::USER_LOGIN, $traceData);

        return [$error, $res];
    }

    public function changePassword(array $input)
    {
        $user = Auth::user();

        if ($user->currentMerchant() and $user->currentMerchant()->isTestAccount())
        {
            return [["Password change forbidden on this account"], null];
        }

        list($error, $data) = $this->updatePasswordOnApi($input);

        $currentSessionId = Session::getId();

        (new SessionTable\Entity)->deleteSessionsForUser($user->id, $currentSessionId);

        return [$error, $data];
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

            Session::put('current_merchant_id', $merchantId);

            $traceData = [
                'id'          => $user->id,
                'merchant_id' => $merchantId,
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

        // Create and cache a random token tying the user to the request
        $token = str_random(30);

        $data = [
            'user_id'       => $user->id,
            'user_email'    => $user->email,
            'merchant_id'   => $currentMerchant->id,
            'role'          => $currentMerchant->role,
            'query_params'  => $queryParams['query'] ?? []
        ];

        $cacheKey = $this->getOAuthSessionTokenCacheKey($token);

        $this->cache->put($cacheKey, $data, 10);

        $response = [
            'token'         => $token,
            'email'         => $user->email,
            'name'          => $user->name,
            'merchant_id'   => $currentMerchant->id,
            'role'          => $currentMerchant->role,
            'merchant_name' => $currentMerchant->name,
            'logo'          => $currentMerchant->logo_url
        ];

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

    public function getUserDetails()
    {
        $data = [
            'current'   =>  null
        ];

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

        $merchants = $userDetails['merchants'];

        $data['user'] = $userDetails;

        $data['_token'] = \Request::getSession()->token();

        $activated = false;

        // Default values in case no merchant is associated
        // with the user account
        $data['pre_signup'] = [];
        $data['pre_signup_complete'] = true;
        $data['experiments'] = [];

        $currentMerchant = (new Helper)->getCurrentMerchant($genericUser);

        if ($currentMerchant === null)
        {
            return [[], $data];
        }

        $data = $data + $currentMerchant->toArray();

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
            // Fetch merchant details for current merchant
            $data = $data + (new MerchantDetails\Service)->fetchDetails();

            $data["pre_signup"] = $merchantService->getPreSignupDetails($currentMerchantId);

            foreach ($merchants as $merchant) {

                $data['merchants'][$merchant['id']] = $merchant;

                if ($merchant['id'] === $currentMerchantId)
                {
                    $data = $this->updateExperiments($data);

                    $data = $this->updateInstantActivationExperiment($data);

                    if (((bool) $merchant['activated']) === true)
                    {
                        $data['experiments']['support_call'] = $merchantService->getTreatment('support_call');
                    }
                    else
                    {
                        $data['experiments']['support_call'] = ['result' => 'off'];
                    }

                    if ((new Helper)->isOwner($currentMerchant))
                    {
                        $data['partner_intent'] = $merchantService->getPartnerIntent();
                    }

                    $data['current'] = $currentMerchantId;

                    $data['tags'] = $merchantService->getMerchantTags($currentMerchantId);

                    $data['features'] = $merchantService->getMerchantFeatures();

                    $isBankingRequest = ApiUrl::isBankingOriginRequest();

                    //
                    // Make switch product call only if
                    // 1. Request is banking request and banking_role is null
                    // 2. Request is pg request and role is null
                    //
                    if ((($isBankingRequest === true) and ($data['banking_role'] === null)) or
                        (($isBankingRequest === false) and ($data['role'] === null)))
                    {
                        $options = [
                            'client_type' => 'merchant',
                            'headers'     => [
                                'X-Request-Origin' => null,
                            ],
                        ];

                        $request = new \App\Admin\ApiRequestAny($options);

                        list($error, $x) = $request->send("merchants/product-switch", "POST");

                        $data = $this->updateUserDetails($data, $user);
                    }

                    // if the merchant is a partner
                    if (($isBankingRequest === false) and (empty($data['merchants'][$merchant['id']]['partner_type']) === false))
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

                if (((bool) $merchant['activated']) === true)
                {
                    $activated = true;
                }
            }

            $preSignupValues = array_values($data['pre_signup']);

            // This is same as on UserController
            $data['pre_signup_complete'] = array_reduce($preSignupValues, function($carry, $item)
            {
                return $carry and !empty($item);
            }, true);

            // We don't show presignup form for user
            // created before this date
            if ($user->created_at < self::PRE_SIGNUP_TIMESTAMP)
            {
                $data['pre_signup_complete'] = true;
            }

            // for non-registered check if pre_signup_complete done or not;

            if ($this->isPartnerIntentTrue($data) or
                $this->isExperimentOnAndIsUnregisteredBusinessType($data) === true)
            {
                if ((((new MerchantDetails\Service))->isPreSignupDetailsSetForNotRegisteredBusiness($data['pre_signup'])) === true)
                {
                    $data['pre_signup_complete'] = true;
                }
            }

            // There are approx 3k merchants who have not
            // filled "role" or "department", but are
            // already activated.
            if ($activated)
            {
                $data['pre_signup_complete'] = true;
            }

            if ((isset($data['activation_status']) === true) and ($data['activation_status'] !== null))
            {
                $data['pre_signup_complete'] = true;
            }

            if ($currentMerchant->role !== 'owner')
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

        $currentMerchant = (new Helper)->getCurrentMerchant($genericUser);

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

        list($error, $data) = $request->processInput($input)->send($route, $httpVerb);

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


        return [$error, $genericUser];
    }

    public function loginOnApi(array $input)
    {
        return $this->loginOnApiOnRoute($input,'users/login', 'POST');
    }

    // Another route for a successful login. If a uses 2fa is not setup
    // this will allow to set up 2fa while logging in. And if setup is success
    // api returns user object. And dashboard needs to start the session.
    public function loginOnApiBy2faSetupSuccessful(array $input)
    {
        return $this->loginOnApiOnRoute($input,'users/login/2fa_setup/verify-mobile', 'POST');
    }

    public function loginOnApiNo2faSetup(array $input)
    {
        return $this->loginOnApiOnRoute($input,'users/login/no2fa', 'POST');
    }

    public function getUserFromApi($userId)
    {
        $adminUser = Auth::guard('api')->user();

        if (empty($adminUser) === false)
        {
            $request = new \App\Admin\ApiRequestAny([
                'client_type' => 'admin'
            ]);

            list($error, $data) = $request->send("users-admin/$userId", "GET");
        }
        else
        {
            $request = new \App\Admin\ApiRequestAny(['client_type' => 'user']);

            list($error, $data) = $request->send("users/$userId", "GET");
        }

        $genericUser = null;

        if (empty($error) === true)
        {
            $genericUser = (new Helper)->createdGenericUser($data);

            $currentMerchantId = Session::get('current_merchant_id');

            if ($currentMerchantId !== null and empty($adminUser) === true)
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

                Auth::login($genericUser, false);
                Session::put('dashboard_user_payload', $genericUser);

            }

        }

        return [$error, $genericUser];
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
     * check and update that instant activation behaviour should be enable for a merchant or not.
     *
     * @param array $data
     *
     * @return array
     */
    public function updateInstantActivationExperiment(array $data): array
    {
        $enableInstantActivations = true;

        //
        // For merchants who are in older activation flow and have already submitted L2 form ,
        // activation_flow will be null and submitted flag will be true. instant activation should be disabled for them.
        // merchant who have already submitted(L2) and got activated() should have older experience only.
        //
        if (($data['activation_flow'] === null)
            and (((bool) $data['submitted']) === true))
        {
            $enableInstantActivations = false;
        }

        //
        // For unregistered business activation flow will be null so instant activation should be true for unregistered business
        //
        if ($this->isExperimentOnAndIsUnregisteredBusinessType($data) === true)
        {
            $enableInstantActivations = true;
        }

        $data['instant_activations'] = $enableInstantActivations;

        $this->trace->info(TraceCode::ENABLE_INSTANT_ACTIVATIONS, [
            'instant_activations' => $data['instant_activations'],
            'merchant_id'         => $data['id'] ?? '',
        ]);

        return $data;
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

        $token = (new JWTBuilder())->setIssuer($issuer)
                                   ->setAudience(self::EXTENSION)
                                   ->setIssuedAt(time())
                                   ->setExpiration(time() + $tokenExpiry)
                                   ->set(self::MERCHANT_ID, $currentMerchantId)
                                   ->set(self::USER_ID, $user->id)
                                   ->set(self::MERCHANT_ACTIVATED, $merchantActivated)
                                   ->set('merchant_international', $merchantInternational)
                                   ->set(self::MERCHANT_LOGO, $merchantLogo)
                                   ->set(self::MERCHANT_NAME, $merchantName)
                                   ->sign($signer, $jwtEncryptionKey)
                                   ->getToken();

        return [[], ["token" => (string) $token]];
    }

    public function validateJWT($token)
    {
        if (empty($token) === true)
        {
            throw new AuthorizationException('Token context not present in the request');
        }

        $token = (new JWTParser())->parse((string) $token);

        $signer = new Sha256();

        $validationData = new JWTValidation();

        $issuer = parse_url(config('app.url'), PHP_URL_HOST);

        $sessionConfig = $this->app['config']['session'];

        $jwtEncryptionKey = $sessionConfig['jwt_encryption_key'];

        $validationData->setIssuer($issuer);

        $validationData->setAudience(self::EXTENSION);

        $validToken = $token->validate($validationData);

        $validSignature = $token->verify($signer, $jwtEncryptionKey);

        if (($validToken === false) or ($validSignature === false))
        {
            throw new AuthorizationException('Invalid Token');
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

            if (($user->id !== $token->getClaim(self::USER_ID)) or
                ($currentMerchantId !== $token->getClaim(self::MERCHANT_ID)))
            {
                throw new AuthorizationException('Different user/merchant is loggedin to the dashboard');
            }
        }
    }

    /**
     * @param $data
     *
     * @return array
     * @throws BadRequestError
     */
    protected function updateExperiments(array $data): array
    {
        $merchantService = new Merchant\Service;

        $features = [
            'reminders',
            'coupons',
            'is_announcement',
            'is_banner',
            'capital_announcement',
            'capital_banner',
            'international_currencies',
            'announcements_early_settlements_1',
            'checkout_survey',
            'sellerapp_plus',
            'second_factor_auth',
            'disable-view-reports',
            'mobile_hotjar_survey',
            'paymentpages_mli',
            'show_commission_balance',
            'custom_notes',
            'sellerapp_PL_batch_upload',
            'nps_survey_banner',
            'vpa_enabled',
            'async_reports',
            'new_pp_success_modal',
            'hide_company_name',
            'rx_test_mode',
            'batch_cancel',
            'rev_up_chennai_announcement',
            'disable_va_creation_bank_account',
            'rx_ssl_cert_renew',
            'user-2fa',
            'rx_webhook_migration_april_alert',
        ];

        $experimentsResults = $merchantService->getBulkTreatment($features);

        foreach ($experimentsResults as $result => $val)
        {
            $data['experiments'][$result] = $val;
        }

        return $data;
    }

    protected function isExperimentOnAndIsUnregisteredBusinessType(array $data): bool
    {


            // check business_type

            $businessType = $data['pre_signup']['business_type'] ?? null;

            if (MerchantDetails\BusinessType::isBusinessTypeForNotRegisteredBusiness($businessType) === true)
            {
                return true;
            }

        return false;
    }

    protected function isPartnerIntentTrue(array $data): bool
    {
        return (
            isset($data['partner_intent']) and
            $data['partner_intent'] === true
        );
    }
}

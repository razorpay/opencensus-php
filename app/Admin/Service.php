<?php

namespace App\Admin;

use DB;
use Auth;
use Hash;
use Uuid;
use Cache;
use Trace;
use Queue;
use Crypt;
use Input;
use Config;
use Request;
use Session;
use Requests;
use Exception;

use App\Base;
use App\User;
use App\Admin;
use App\Generic;
use App\Merchant;
use App\Lib\Util;
use App\Schedules;
use App\Providers;
use App\Transaction;
use App\User\Helper;
use App\Http\ApiUrl;
use App\Http\Headers;
use App\Trace\TraceCode;
use App\MerchantDetails;
use App\Mailers\MiscMailer;
use App\Providers\ApiGuard;
use App\Merchant\Constants;
use App\Admin\ApiRequestAny;
use OneLogin\Saml2 as SamlAuth;
use App\Session as SessionTable;
use GuzzleHttp\Client as Guzzle;
use Razorpay\Api\Errors\ErrorCode;
use Razorpay\Api\Request as ApiRequest;
use Razorpay\Api\Errors\Error as ApiError;
use App\Constants\Constants as AppConstants;
use App\Transaction\Service as TransactionService;
use Razorpay\Api\Errors\ServerError as ServerError;
use Razorpay\Api\Errors\BadRequestError as BadRequestError;

use Carbon\Carbon;
use UAParser\Parser;
use Aws\Laravel\AwsFacade as AWS;
use Illuminate\Support\Facades\App as App;
use Illuminate\Support\Facades\Redis;

class Service extends Base\Service
{
    // 15 minutes
    const TIMEOUT = 900;
    const PRIMARY_LOGIN_ERROR = "There is no user associated with this account.";
    const PAGE_SIZE = 1000;

    const RAZORPAY_ORG_ID       = '100000razorpay';

    const CACHE_KEY_ORG_DATA = 'org_data_';

    // This is the Admin\Logger trait
    use Logger;

    const BLACKLIST_ROUTES_ADMIN = [
        'admin/oauth_login'
    ];

    const PNG           = 'png';
    const SVG           = 'svg';
    const ADFS_ERROR    = 'ADFS Authentication Failed';
    const PNG_MIME_TYPE = 'image/png';
    const SVG_MIME_TYPE = 'image/svg+xml';

    const ORG_LOGO_ALLOWED_MIMETYPES = [
        self::PNG_MIME_TYPE,
        self::SVG_MIME_TYPE,
    ];

    const ORG_LOGO_ALLOWED_EXTENSIONS = [
        self::PNG,
        self::SVG,
    ];
    /**
     * @var \GuzzleHttp\Client|null
     */
    private ?Guzzle $httpClient;

    public function __construct(array $options = [])
    {
        $app = \App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->cache = $app['cache'];

        $this->httpClient = array_get($options, AppConstants::HTTP_CLIENT);

        $this->settings = Config::get('samlconfig');

        $this->auth = new SamlAuth\Auth($this->settings, true);
    }

    protected function getEncryptionSecret()
    {
        return config('key');
    }

    protected function getIv()
    {
        return openssl_random_pseudo_bytes(12);
    }

    public function getCacheTime()
    {
       return Config::get('app.cache_ttl_org_time_minute');
    }

    public function passwordLogin($domain, array $input)
    {
        $error = $data = null;

        try
        {
            // This is password based login

            $request = new ApiRequestAny();

            list($error, $data) = $request->processInput($input)->send('admin/authenticate', 'POST');

            $validate2FAparams = $this->validate2FAparams($input);

            if(isset($error) === true and $validate2FAparams){
                $tag1  = '';
                $tag2  = '';
                $iv1 = $this->getIv();
                $iv2 = $this->getIv();

                $encryptedUsername = openssl_encrypt(
                    $input[Admin\Constants::USERNAME],
                    'aes-256-gcm',
                    $this->getEncryptionSecret(),
                    OPENSSL_ZERO_PADDING,
                    $iv1,
                    $tag1
                );

                $encryptedPassword = openssl_encrypt(
                    $input[Admin\Constants::PASSWORD],
                    'aes-256-gcm',
                    $this->getEncryptionSecret(),
                    OPENSSL_ZERO_PADDING,
                    $iv2,
                    $tag2
                );

                $encryptedUsername = base64_encode($iv1 . $encryptedUsername . $tag1);
                $encryptedPassword = base64_encode($iv2 . $encryptedPassword . $tag2);

                Session::put(Admin\Constants::USERNAME, $encryptedUsername);
                Session::put(Admin\Constants::PASSWORD, $encryptedPassword);
            }
            Session::put(config('auth.guards.api.session_key'), $data);

            if((new Util)->debugLogsEnable()=== true)
            {
                $this->app['trace']->info(TraceCode::ADMIN_LOGIN_DEBUG, [
                    'type' => "s2",
                    'session_id' => Session::getId(),
                ]);
            }
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }
        return  $this->handleLoginResponse($error,$data);

    }

    function validate2FAparams($input)
    {
        if(isset($input['username']) === true and
        isset($input['password']) === true)
            return true;
        return false;
    }

    function handleLoginResponse($error,$data){
        if (empty($error) === false) {
            if ((array_key_exists('internal_error_code', $error) === true) and
                (empty($error['internal_error_code']) === false)) {
                return [[$error], $data];
            }
        }

        // if the admin login show_tnc_popup should be false as it is a case of admin_as_merchant
        Session::put('show_tnc_popup',false);

        return [$error, $data];
    }

    /**
     * Called when admin performs merchant actions like suspend
     *
     * @param string $merchantId
     *
     * @return array obj
     */
    public function action(string $merchantId) : array
    {
        try
        {
            $request = new ApiRequestAny(['client_type' => 'admin']);

            $input = Request::all();
            $action = $input[Constants::ACTION];

            list($error, $data) = $request->send("merchants/{$merchantId}/action", Request::method());
        }
        catch (BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }
        catch (ServerError $e)
        {
            $error[] = $e->getMessage();
        }

        // for suspend/unsuspend action, clear all sessions of users for that merchant
        if((empty($error) === true) and
           (($action === Constants::ACTION_SUSPEND) or
           ($action === Constants::ACTION_UNSUSPEND)))
        {
            $this->clearMerchantUserSessions($merchantId);
        }

        return [$error, $data];
    }

    public function oAuthLogin($input)
    {
        $error = $data = null;
        // This is oAuth based login

        $request = new ApiRequestAny(
            ["client_type" => "internal"]
        );

        list($error, $data) = $request->processInput($input)->send("admin/oauth_login", 'POST');

        return [$error, $data];
    }


    public function getSso()
    {
        $ssoBuiltUrl = $this->auth->login(null, array(), false, false, true);

        Session::put('AuthNRequestID', $this->auth->getLastRequestID());

        return $ssoBuiltUrl;
    }


    public function postCallback($input)
    {
        $validator = (new Admin\Validator);

        $validator->validateInput('callback', $input);

        $_POST['SAMLResponse'] = $input['SAMLResponse'] ?? null;

        $requestID = Session::get('AuthNRequestID') ?? null;

        if ($requestID === null) {
            $this->trace->info(TraceCode::SAML_LOGIN, [
                'msg' => 'SAML Login: AuthNRequestID is null'
            ]);
        }

        $this->auth->processResponse($requestID);

        $errors = $this->auth->getErrors();

        if (empty($errors) === false) {
            $ex = $this->auth->getLastErrorException();
            //log exception

            $this->trace->info(TraceCode::SAML_LOGIN_EXCEPTION, [
                'exception' => $ex->getMessage(),
            ]);

            $response = [
                'success' => false,
                'errors'  => [self::ADFS_ERROR],
            ];

            return view('admin.adfs',['response' =>  $response]);

        }

        $attributes = $this->auth->getAttributes();

        $this->trace->info(TraceCode::SAML_CALLBACK, [
            '$errors' => $errors,
            '$attributes' => $attributes,
        ]);

        $data = $this->getApiRequest($attributes);

        $response = $this->validateAdminAndGenerateToken($data);

        if (isset($response[1]['token']) === true)
        {
            $response = [
                'success' => true
            ];
        }
        else
        {
            $response = [
                'success' => false,
                'errors'  => $response[0],
            ];
        }

        return view('admin.adfs',['response' =>  $response]);

    }

    function getApiRequest($data)
    {
        $claimToRequestMap = [
            'http://schemas.microsoft.com/identity/claims/displayname' => 'username',
            'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress' => 'email',
            'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name'=> 'ad_id',
        ];

        $requestMap = [];

        foreach ($claimToRequestMap as $key => $value) {
            $requestMap[$value] = $data[$key][0] ?? null;

            if ($value === 'email')
                $requestMap['email'] = strtolower($requestMap['email']);
        }

        return $requestMap;
    }

    function validateAdminAndGenerateToken($input)
    {
        $error = $data = null;

        try
        {
            $request = new ApiRequestAny();

            list($error, $data) = $request->processInput($input)->send('admins/saml/login', 'POST');

            $this->trace->info(TraceCode::SAML_LOGIN, [
                '$data' => $data ?? 'null',
                '$error' => $error ?? 'null'
            ]);

            Session::put(config('auth.guards.api.session_key'), $data);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $this->trace->info(TraceCode::SAML_LOGIN,[
                'data' => $e->getMessage(),
            ] );
            $error[] = $e->getMessage();
        }

        return $this->handleLoginResponse($error,$data);
    }

    public function loginWithGoogle($code, $googleService)
    {
        $this->setApiCredentials();

        $error = [];
        $token = $googleService->requestAccessToken($code);

        $response = $googleService->request(Config::get('oauth-5-laravel.userinfo_url'));

        $result = json_decode($response);

        if ($result->verified_email === false)
        {
            return App::abort(404);
        }

        // 1. Login the user to dashboard. Have to make an API call
        // to login the user and get an admin_token

        $oAuthLoginInput = [
            'email'                 => $result->email,
            'oauth_access_token'    => $token->getAccessToken(),
            'oauth_provider_id'     => $result->id,
        ];

        list($error, $data) = $this->oAuthLogin($oAuthLoginInput);

        if (empty($data) === false)
        {
            Session::put(config('auth.guards.api.session_key'), $data);
        }

        $traceData = [
            'email'     => $result->email,
        ];

        // if the admin login show_tnc_popup should be false as it is a case of admin_as_merchant
        Session::put('show_tnc_popup',false);

        $this->trace->info(TraceCode::ADMIN_LOGIN, $traceData);

        return $error;
    }

    /**
     * Updates the keepAlive timer stored in Session
     * @return integer|boolean Current timestamp or false if user needs to be
     * logged out
     */
    public function updateKeepAlive()
    {
        $time = time();

        $last_timer = Session::get('timeout');

        // If we had a timer in session and it has passed
        if ($time - $last_timer >  self::TIMEOUT)
        {
            return false;
        }

        else
        {
            Session::put('timeout', $time);
            return ['alive' => true];
        }
    }

    /**
     * Logs the admin in to the user account of the primary owner
     *
     * @param  $merchantId ineteger
     *
     * @return  array
     */
    public function loginUsingPrimaryOwner($merchantId)
    {
        $admin = Auth::guard('api')->user();

        $error = [];

        $headers = [];

        $users = (new Merchant\Service)->getMerchantUsers($merchantId);

        $genericUsers = (new Helper)->createGenericUsers($users);

        $primaryOwner = $genericUsers->whereIn('role', ['owner', 'linked_account_owner'])
                                     ->first();

        if ($primaryOwner === null)
        {
            $error[] = self::PRIMARY_LOGIN_ERROR;

            return [$error, $headers];
        }

        try
        {
            list($error, $user) = (new User\Service)->getUserFromApi($primaryOwner->id);

            if (empty($error) === false)
            {
                return [$error, $headers];
            }

            $this->app['session']->put('dashboard_user_payload', $user);

            Auth::login($user, false);

            (new User\Service)->switchCurrentMerchantForUser($merchantId, $user);

            list($error, $res, $headers) = $this->loginToAdminExperienceService($merchantId);

            if (empty($error) === false)
            {
                $this->trace->error(TraceCode::ADMIN_AS_MERCHANT, ['error' => $error]);

                return [$error, $headers];
            }

            $traceData = [
                'org_id'            => $admin->org_id,
                'merchant_id'       => $merchantId,
                'admin_user_id1'    => $admin->id,
            ];

            $this->trace->info(TraceCode::ADMIN_AS_MERCHANT, $traceData);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = self::PRIMARY_LOGIN_ERROR;
        }

        return [$error, $headers];
    }

    public function getAdminActivity($id)
    {
        $sessionsCollection = (new SessionTable\Entity)->getAllSessionsForAdmin($id);

        foreach ($sessionsCollection as $session)
        {
            $session['current'] = false;

            if ($session['id'] === Session::getId())
            {
                $session['current'] = true;
            }

            $session['id'] = Crypt::encrypt($session['id']);

            $parser = Parser::create();

            $session['parsed_user_agent'] = $parser->parse($session['user_agent']);
            // 9 Feb 2017 03:12 pm
            $session['parsed_last_activity'] = Carbon::createFromTimeStamp(time(), "Asia/Kolkata")->format('j M Y h:i a');

            $sessions[] = $session;
        }

        return $sessions;
    }

    public function deleteAllOtherAdminSessions($id)
    {
        $currentSessionId = Session::getId();

        (new SessionTable\Entity)->deleteAllOtherSessionsForAdmin($id, $currentSessionId);
    }

    public function deleteOneAdminSessions($sessionId)
    {
        $sessionId = Crypt::decrypt($sessionId);

        (new SessionTable\Entity)->deleteOneSessionForAdmin($sessionId);
    }

    public function fetchMerchantDetails($id)
    {
        $response = [];

        $this->setApiCredentials();

        $merchant = $this->api->merchant->fetch($id)->toArray();

        $parentId = $merchant['parent_id'] ?? null;

        // If parent_id is set, the merchant is a linked account under Marketplace
        // and are marked confirmed, without email confirmation
        if ($parentId !== null)
        {
            $merchant['confirmed'] = true;
        }
        else
        {
            $users = (new Merchant\Service)->getMerchantUsers($id);

            $genericUsers = (new Helper)->createGenericUsers($users);

            $confirmedPrimaryOwner = $genericUsers->where('role', 'owner')
                                                  ->where('confirmed', true)
                                                  ->first();

            $merchant['confirmed'] = (empty($confirmedPrimaryOwner) === false);
        }

        $merchantDetail = (new MerchantDetails\Service)->fetchDetails($id);

        $merchant['merchant_details'] = $merchantDetail;

        $response = [
            'archived_at'         => $merchant['archived_at'],
            'suspended_at'        => $merchant['suspended_at'],
            'locked'              => $merchantDetail['locked'],
            'submitted'           => $merchantDetail['submitted'],
            'submitted_at'        => $merchantDetail['submitted_at'],
            'activated_dashboard' => $merchant['activated']
        ] + $merchant;

        return $response;
    }

    public function postEditMerchant($id, $input)
    {
        $error = [];

        $data = [];

        $this->setApiCredentials();

        try
        {
            if (isset($input['transaction_report_email']))
            {
                $csvEmail = $input['transaction_report_email'];
                $input['transaction_report_email'] =
                    array_map(
                        'trim',
                        explode(',', $input['transaction_report_email'])
                    );

                $csvEmail = implode(',', $input['transaction_report_email']);
            }

            $data = $this->api
                         ->merchant
                         ->fetch($id)
                         ->edit($input)
                         ->toArray();

            $this->logMerchantEdits($id, $input);

            if ((isset($input['transaction_report_email']) === true) or (isset($input['website']) === true))
            {
                $params = [];

                if ((isset($input['transaction_report_email']) === true))
                {
                    $params['transaction_report_email'] = $csvEmail;
                }

                if ((isset($input['website']) === true))
                {
                    $params['business_website'] = $input['website'];
                }

                // Only when it is changed on API side we update on the dashboard side as well
                list($error, $details) = (new MerchantDetails\Service)->updateMerchantByAdminOnAPI($params, $id);
            }

            if ((isset($input['fee_bearer'])) and ($input['fee_bearer'] === 'customer'))
            {
                $currentTags = (new Merchant\Service)->getMerchantTags($id);

                (new Merchant\Service)->addMerchantTagsOnAPI($id, array_merge($currentTags, ['feebearer']));
            }
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $data);
    }

    /**
     * Logs a merchant being edited properly
     * @param  string $id Merchant Id
     * @param  array $input Input array
     */
    protected function logMerchantEdits($id, $input)
    {
        if (isset($input['hold_funds']))
        {
            if ($input['hold_funds'] == 1)
            {
                $this->logActionToSlack($id, Actions::FUNDS_HELD);
            }
            elseif ($input['hold_funds'] == 0)
            {
                $this->logActionToSlack($id, Actions::FUNDS_RELEASED);
            }
        }

        if (isset($input['risk_rating']))
        {
            // This is always sent currently for every edit.
            unset($input['transaction_report_email']);
            $this->logActionToSlack($id, Actions::RISK_RATING_CHANGED, $input);
        }
    }

    public function postEditMerchantEmail($id, $input)
    {
        $input[Merchant\Entity::EMAIL] = strtolower($input[Merchant\Entity::EMAIL]);

        $request = new ApiRequestAny(['client_type' => 'admin']);

        list($error, $data) = $request->processInput($input)->send("merchants/$id/email", 'PUT');

        if (empty($error) === true)
        {
            // Clear Merchant User Sessions because roles and new users will be added based on the email.
            $this->clearMerchantUserSessions($id);
        }

        return [$error, $data];
    }

    public function clearMerchantsUserSessions($merchantIds)
    {
        foreach ($merchantIds as $merchantId)
        {
            $this->clearMerchantUserSessions($merchantId);
        }
    }

    private function clearMerchantUserSessions($merchantId)
    {
        $merchantUsers = (new Merchant\Service)->getMerchantUsers($merchantId);

        foreach ($merchantUsers as $merchantUser)
        {
            (new SessionTable\Entity)->deleteSessionsForUser($merchantUser['id']);
        }
    }

    protected function dropFields(array &$array, array $fields)
    {
        foreach ($fields as $key)
        {
            unset($array[$key]);
        }
    }

    public function postMerchantTerminal($id, $input)
    {
        $error = (new Merchant\Validator)->validateInput('terminal', $input)->messages();

        $data = [];

        $mode = $input['mode'];

        // only first data sends terminal_mode.
        // mode should not be sent when terminal_mode is nor sent from client.
        if (isset($input['terminal_mode']) === true)
        {
            $input['mode'] = $input['terminal_mode'];

            unset($input['terminal_mode']);
        }
        else
        {
            unset($input['mode']);
        }

        if (empty($error))
        {
            $this->dropFields($input, [
                'gateway_terminal_password_confirmation',
            ]);

            $this->setAdminCredentials(null, $mode);

            if (isset($input['gateway_client_certificate']) === true)
            {
                $input['gateway_client_certificate'] = $this->encodeGatewayClientCertificate(
                                                        $input['gateway_client_certificate']);
            }

            try
            {
                $data = $this->api->merchant->fetch($id)->setTerminal($input)->toArray();
            }
            catch (\Razorpay\Api\Errors\BadRequestError $e)
            {
                $error[] = $e->getMessage();
            }
        }

        return array($error, $data);
    }

    /**
     * Encodes gateway client certificate to base64 and sends it to api, where it is
     * decoded and stored as a file
     * https://github.com/razorpay/api/blob/master/app/Gateway/FirstData/Gateway.php#L893
     * @param  certificateFile Certificate file object
     */
    protected function encodeGatewayClientCertificate(\SplFileInfo $certificateFile)
    {
        $gateway_client_certificate = file_get_contents($certificateFile->getPathname());

        return base64_encode($gateway_client_certificate);
    }

    /**
     * Incoming data is what is stored in the merchant details table
     * outgoing is what we store in the bank account itself
     * on the API
     *
     * @param  array    $details
     * @param  bool     $linkedAccount
     * @return array
     */
    protected function getBankAccountMap($details, $linkedAccount = false)
    {
        $data = [
            'ifsc_code'             => $details['bank_branch_ifsc'],
            'beneficiary_name'      => $details['bank_account_name'],
            'account_number'        => $details['bank_account_number'],
            'beneficiary_address1'  => $details['bank_beneficiary_address1'],
            'beneficiary_address2'  => $details['bank_beneficiary_address2'],
            'beneficiary_address3'  => $details['bank_beneficiary_address3'],
            'beneficiary_address4'  => '',
            'beneficiary_pin'       => $details['bank_beneficiary_pin'],
            'beneficiary_city'      => $details['bank_beneficiary_city'],
            'beneficiary_state'     => $details['bank_beneficiary_state'],
            'beneficiary_country'   => 'IN',
            'beneficiary_email'     => $details['contact_email'],
            'beneficiary_mobile'    => $details['contact_mobile']
        ];

        //
        // For Marketplace linked accounts, the bank fields set below are not
        // required in the activation form but needed for API validation
        // Setting default values here to overcome this
        //
        if ($linkedAccount === true)
        {
            $data['beneficiary_address1']   = 'Bangalore';
            $data['beneficiary_city']       = 'Bangalore';
            $data['beneficiary_state']      = 'KA';
            $data['beneficiary_pin']        = 560001;
            $data['beneficiary_mobile']     = 9999999999;
        }

        return $data;
    }

    /**
     * Activates a merchant account
     *
     * @param  string  $id            Merchant Id
     * @param  boolean $dashboardOnly Only perform the activation on dashboard, not on API
     *                                Useful in certain contexts, when merchant is already activated
     *                                in the API, but now causing issue elsewhere
     * @return array   Empty array in case of success
     */
    public function activateMerchant($id, $dashboardOnly = false)
    {
        $error = $response = [];

        $this->setApiCredentials();

        $merchant = $this->api->merchant->fetch($id);

        $details = $this->fetchMerchantDetails($id);

        if ((int) $details['submitted'] === 0)
        {
            return [['Activation form has not been submitted by merchant yet.'], []];
        }

        $this->setApiCredentials();

        // If parent_id is set here, the merchant is a marketplace linked account
        $isLinkedAccount = (empty($details['parent_id']) === false);

        $bankAccount = $this->getBankAccountMap($details['merchant_details'], $isLinkedAccount);

        $bankAccountApi = false;

        // Check if the merchant has a bank account
        try
        {
            $ba = $this->api->merchant->fetch($id)->fetchBankAccount();

            $bankAccountApi = true;
        }
        catch(BadRequestError $e)
        {
            $bankAccountApi = false;
        }

        try
        {
            $this->setAdminCredentials();

            // Only if the merchant doesn't have the Bank Account associated
            // Do we add a bank account
            if ($bankAccountApi === false)
            {
                $this->api->merchant->setId($id)->setBankAccount($bankAccount);
            }

            if ($merchant['activated'] === false)
            {
                $response = $this->api->merchant->setId($id)->activate();
            }
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            return [[$e->getMessage()], []];
        }

        try
        {
            if ($merchant['activated'] === false)
            {
                // Log activation on marketing google spreadsheet
                $zapierData = $this->activationZapierData($details);
                Queue::push('App\Admin\Service@postActivationToZapier', $zapierData);

                $this->logActionToSlack($id, Actions::ACTIVATED);
            }
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {

        }
        finally
        {
            return [$error, $response->toArray()];
        }
    }

    public function postActivationToZapier($job, $data)
    {
        if (Config::get('razorpay.zapier.mock'))
        {
            return;
        }

        $url = Config::get('razorpay.zapier.activations');
        Requests::post($url, [], $data);

        $job->delete();
    }

    public function isAdminLoggedIn()
    {
        return Auth::guard('api')->check() === true;
    }

    protected function activationZapierData(array $merchant)
    {
        $date =  Carbon::createFromTimeStamp(time(), "Asia/Kolkata")->format('j/m/Y');

        $merchantDetails = $merchant['merchant_details'];

        return [
            'date'             => $date,
            'id'               => $merchant['id'],
            'email'            => $merchant['email'],
            'name'             => $merchant['name'],
            'contact_name'     => $merchantDetails['contact_name'],
            'business_name'    => $merchantDetails['business_name'],
            'business_dba'     => $merchantDetails['business_dba'],
            'business_website' => $merchantDetails['business_website'],
            'ref'              => $merchant['referrer'],
        ];
    }

    public function generateMerchantHdfcExcel($id)
    {
        if ($id === null)
        {
            return [['id' => 'Merchant id cannot be null'], []];
        }

        $request = new ApiRequestAny([
            'client_type' => 'admin',
            'headers' => [
                'X-Razorpay-Account' => $id,
                'X-Merchant-Id'      => $id
            ]
        ]);

        list($error, $response) = $request->send('merchants/details', 'GET');

        if (empty($error) === false)
        {
            return [$error, []];
        }

        $data['merchant'] = $response;

        $file = Hdfc\HdfcTidExcel::generateExcel($data);

        $this->logActionToSlack($id, Actions::HDFC_EXCEL);

        return [[], $file];
    }

    public function fetchMultipleEntities($mode, $entity, $input, $adminAuth = false)
    {
        $error = array();

        $response = array();

        if ($adminAuth === true)
        {
            $this->setAdminCredentials(null, $mode);
        }
        else
        {
            $this->setApiCredentials(null, $mode);
        }

        try
        {
            $response = $this->api->admin->fetchMultipleEntities($entity, $input)->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        if(!empty($response['items']))
        {
            $response['headings'] = array_keys($response['items'][0]);
        }
        else
        {
            $response['headings'] = array();
        }

        return array($error, $response);
    }

    public function fetchEntityById($mode, $entity, $id)
    {
        $error = array();

        $response = array();

        $this->setApiCredentials(null, $mode);

        try
        {
            $response = $this->api->admin->fetchEntityById($entity, $id)->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $response);
    }

    /**
     * Fires off a queue worker to start capturing screenshots
     * @param  string $id merchant id
     */
    public function captureScreenshot($id)
    {
        $merchantDetails = (new MerchantDetails\Service)->fetchDetails($id);

        $name = $merchantDetails['business_name'];

        $urls = (new MerchantDetails\Service)->getWebsiteUrls($merchantDetails);

        if (count($urls) >= 7)
        {
            \Queue::push('App\Admin\Creevey', [$id, $urls, $name]);
            return [];
        }
        else
        {
            return ["The merchant needs to give all atleast 7 links"];
        }
    }

    /**
     * Saves provided screenshots to S3
     * @param  string $id    Merchant Id
     * @return array error
     */
    public function saveScreenshot($id, $input)
    {
        $keys = (new MerchantDetails\Service)->getUrlKeys();

        $found = false;

        $creevey = new Creevey($id);

        foreach ($keys as $key)
        {
            if (\Input::hasFile($key) and $input[$key]->isValid())
            {
                $found = true;

                $localFilePath = $input[$key]->getRealPath();

                try
                {
                    $creevey->compressAndSave($key, $localFilePath, $input[$key]->getClientOriginalName());
                }
                catch (\Exception $e)
                {
                    return [$e->getMessage()];
                }
            }
        }

        if ($found === false)
        {
            return ["No matching files found while uploading"];
        }

        return [];
    }

    /**
     * @param array $input [description]
     *
     * @return array
     */

    public function twoFactorAuthVerifyOtp(array $input)
    {
        $error = $data = null;

        try
        {
            $request = new ApiRequestAny();

            list($error, $data) = $request->processInput($input)->send('admins/2fa/verify', 'POST');

            Session::put(config('auth.guards.api.session_key'), $data);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return $this->handleLoginResponse($error,$data);
    }

    public function putSessionValues(array $input)
    {
        $encryptedUsername = base64_decode(Session::get(Admin\Constants::USERNAME));
        $encryptedPassword = base64_decode(Session::get(Admin\Constants::PASSWORD));
        $tag1 = substr($encryptedUsername,-16);
        $tag2 = substr($encryptedPassword, -16);
        $iv1 = substr($encryptedUsername,0,12);
        $iv2 = substr($encryptedPassword,0,12);
        $encryptedUsername = substr($encryptedUsername,12,-16);
        $encryptedPassword = substr($encryptedPassword,12,-16);


        $input[Admin\Constants::USERNAME]=openssl_decrypt(
            $encryptedUsername,
            'aes-256-gcm',
            $this->getEncryptionSecret(),
            OPENSSL_ZERO_PADDING,
            $iv1,
            $tag1
        );

        $input[Admin\Constants::PASSWORD]=openssl_decrypt(
            $encryptedPassword,
            'aes-256-gcm',
            $this->getEncryptionSecret(),
            OPENSSL_ZERO_PADDING,
            $iv2,
            $tag2
        );

        return $input;
    }

    public function postResendOtp(array $input)
    {
       $input = $this->putSessionValues($input);

        $error = $data = null;

        try
        {
            $request = new ApiRequestAny();

            list($error, $data) = $request->processInput($input)->send('admins/2fa/otp_resend', 'POST');

            Session::put(config('auth.guards.api.session_key'), $data);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return $this->handleLoginResponse($error,$data);
    }



    /**
     * Uploads a screenshot to S3
     * @param  string $objectPath Object path on S2
     * @param  String $filePath   Local file path
     */
    protected function uploadToS3($objectPath, $filePath)
    {
        $s3 = $this->getS3Client();

        $s3Obj = [
            'Bucket'        => config('aws.activation_bucket'),
            'Key'           => $objectPath,
            'ContentType'   => "image/jpeg",
            'SourceFile'    => $filePath,
        ];

        $s3->putObject($s3Obj);
    }

    /**
     * Returns an associative array of links to S3
     * @param  string $id merchant id
     * @return array screenshot S3 links
     */
    public function getScreenshot($id)
    {
        $s3 = $this->getS3Client();

        $bucket = config('aws.activation_bucket');

        $keys = (new MerchantDetails\Service)->getUrlKeys();

        $links = [];

        foreach ($keys as $key)
        {
            $cmd = $s3->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key'    => "$id/screenshots/$key.jpg"
            ]);

            $request = $s3->createPresignedRequest($cmd, '+30 minutes');

            $links[$key] = (string) $request->getUri();
        }

        return $links;
    }

    public function makeRawApiCall($path)
    {
        $input = \Input::all();
        $error = [];

        $this->traceRawApiCall($input, $path);

        $validator = (new Admin\Validator);

        $validator->setStrictFalse();

        $validator->validateInput('api_call', $input);

        $disableAPI = $this->disallowAPIFromMakeRawAPICall($path);

        if ($disableAPI === true)
        {
            return ['not allowed', []];
        }

        $allRequestHeaders = Request::header();
        $headersToBeAppended = [];

        foreach($allRequestHeaders as $headerKey => $headerValue) {
            if (stripos($headerKey, 'X-') !== false) {
                // $headerValue is an array
                // with first value being the value sent from client
                $headersToBeAppended[$headerKey] = $headerValue[0];
            }
        }

        $request = new RawApiRequest($input, $path, $headersToBeAppended);

        return $request->send();
    }

    /**
     *  Disallow Admin routes from make raw api call.
     *
     * @param $path
     *
     * @return bool
     */
    protected function disallowAPIFromMakeRawAPICall($path)
    {
        if (in_array($path, self::BLACKLIST_ROUTES_ADMIN, true) === true)
        {
            return true;
        }

        return false;
    }


    public function addEntityFeatures($entityType, $entityId, $input)
    {
        $error = $response = array();

        if (!empty($error))
        {
            return array($error, null);
        }

        $this->setAdminCredentials(null, $input['mode']);

        try
        {
            $params = [
                        'names'       => $input['features'],
                        'entity_type' => $entityType,
                        'entity_id'   => $entityId,
                        'should_sync' => $input['should_sync']
                    ];

            $response = $this->api->feature->setFeatures($params);

            $features = $this->api->feature->getFeatures($entityId);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        if (empty($error))
        {
            $this->retagMerchant($entityId, $features);

            return [null, $features];
        }

        return array($error, null);
    }

    private function retagMerchant($entityId, $features)
    {
        $featureNames = $this->getFeatureNames($features['assigned_features']);

        $merchantTags = (new Merchant\Service)->getMerchantTags($entityId);

        (new Merchant\Service)->addMerchantTagsOnAPI($entityId, array_merge($featureNames, $merchantTags));
    }

    private function getFeatureNames($features)
    {
        $featureNames = array_map(function ($feature)
        {
            return $feature['name'];
        }, $features);

        return $featureNames;
    }

    /**
     * See the data params at
     * https://razorpay.slack.com/services/20502106306?updated=1#service_setup
     *
     * The token is matched in the filter stage, so we just parse the message here
     * @param  array  $input Slack input
     */
    public function querySlack(array $input)
    {
        $slack = new Slack($input['text'], $input['user_name'], $input['channel_name']);

        return $slack->getResponse();
    }

    /**
     * This function is used to get the required timestamp based on the filters applied
     * on merchant stats page [Eg: Last 1 week, Last 3 Months etc.]
     * @param $duration_count int
     * @param $type $type string
    */
    public function getMerchantStatsFilterTimestamp($duration_count, $type)
    {
        $current = Carbon::now();

        $timestamp = '';

        switch ($type) {
            case 'day':
                $timestamp = $current->startOfDay()->subDays($duration_count)->timestamp;
                break;

            case 'week':
                $timestamp = $current->startOfWeek()->subWeeks($duration_count)->timestamp;
                break;

            case 'month':
                $timestamp = $current->startOfMonth()->subMonths($duration_count)->timestamp;
                break;

            case 'year':
                $timestamp = $current->startOfYear()->subYears($duration_count)->timestamp;
                break;
        }

        return $timestamp;
    }

    public function getMerchantAggregations($mode, $input)
    {
        $error = (new Admin\Validator)->validateInput('merchant_stats', $input)->messages();

        if (empty($error))
        {
            $sort = Input::get('sort', 'total_amount');

            $count = Input::get('count', 10);

            $duration_count = Input::get('duration_count', 1);

            $type = Input::get('type', 'month');

            $filterTimestamp = $this->getMerchantStatsFilterTimestamp($duration_count, $type);

            $response =
                Merchant\Entity::getAllTransactionAggregations($mode, $sort, $count, $filterTimestamp, $type);

            return [null, $response];
        }
        else
        {
            return [$error, null];
        }

    }

    public function getSingleMerchantAggregations($mode, $input, $merchantId)
    {
        $sort = Input::get('sort', 'total_amount');

        $duration_count = Input::get('duration_count', 1);

        $type = Input::get('type', 'month');

        $filterTimestamp = $this->getMerchantStatsFilterTimestamp($duration_count, $type);

        $response = Merchant\Entity::getTransactionAggregations($mode, $sort, $filterTimestamp, $type, $merchantId);

        return [null, $response];
    }

    public function makeReconciliateRequest($input, $mode = 'live')
    {
        $this->setApiCredentials(null, $mode);

        $error = $data = null;

        try
        {
            $data = $this->api->admin->makeReconciliateRequest($input, $mode);
        }
        catch(BadRequestError $e)
        {
            $error = [$e->getMessage()];
        }

       return [$error, $data];
    }

    public function updateMerchantDayAggregations($mode, $input)
    {
        $total_payments = $this->fetchPaymentsToAggregate($input, $mode);

        $this->trace->info(TraceCode::MISC_TRACE_CODE, array_keys($total_payments));

        list($error, $response) = (new Transaction\Service)->processDayAggregations($total_payments, $mode);

        return array($error, $response);
    }

    protected function fetchPaymentsToAggregate($input, $mode)
    {
        if (isset($input['date']) === false)
        {
            $dateFrom = Carbon::yesterday()->timestamp;
        }
        else
        {
            $dateFrom = Carbon::parse($input['date'])->timestamp;
        }

        $dateTo = $dateFrom + TransactionService::TIME_INTERVALS['day'];

        $count_done = 0;

        $params['status'] = 'captured,refunded';
        $params['from'] = $dateFrom;
        $params['count'] = self::PAGE_SIZE;
        $params['to'] = $dateTo;
        if (isset($input['merchant_id']))
        {
            $params['merchant_id'] = $input['merchant_id'];
        }

        $total_payments = [];

        while (1)
        {
            $params['skip'] = $count_done;

            list($error, $payments) = $this->fetchMultipleEntities($mode, 'payment', $params);

            $count = $payments['count'];

            $payments = $payments['items'];

            foreach ($payments as $payment)
            {
                if ($payment['captured_at'] === NULL)
                {
                    continue;
                }

                $payment = $this->cleanUpPayment($payment);

                $total_payments[$payment['merchant_id']][] = $payment;
            }

            if ($count < self::PAGE_SIZE)
            {
                break;
            }

            $count_done += self::PAGE_SIZE;
        }

        return $total_payments;
    }

    protected function cleanUpPayment($payment)
    {
        $minimal_keys = ['merchant_id', 'amount', 'created_at', 'updated_at'];

        $minimal_payment = array_filter($payment, function($key) use($minimal_keys) {
            return in_array($key, $minimal_keys);
        }, ARRAY_FILTER_USE_KEY);

        return $minimal_payment;
    }

    public function getOrgFeatures()
    {
        $features = [];

        $domain = \Request::server('SERVER_NAME');

        list($error, $org) = $this->getOrg($domain);

        if (empty($error) === true)
        {
            return $org['features'];
        }

        return $features;
    }

    public function getOrg($domain, $asMerchant = false)
    {
        $orgDataFromCache = $this->getOrgDataFromCache($domain);

        if ($orgDataFromCache !== null)
        {
            return [null, $orgDataFromCache];
        }

        if ($asMerchant)
        {
            $request = new ApiRequestAny(['client_type' => 'user', AppConstants::HTTP_CLIENT => $this->httpClient]);
        }
        else
        {
            $request = new ApiRequestAny();
        }

        [$error, $data] = $request->send("orgs/hostname/$domain", "GET");

        if (empty($error))
        {
            $this->setOrgInCache($data);
        }

        return [$error, $data];
    }

    protected function setOrgInCache($org)
    {
        $cacheKey = $domain = $org['hostname'];

        $orgDataCacheKey = $this->getOrgDataCacheKey($domain);

        if ($this->cache->has($cacheKey) === false)
        {
            $this->cache->put($cacheKey, $org['id'], 10);
        }
        $this->cache->put($orgDataCacheKey, $org, $this->getCacheTime());
    }

    public function getAdminData($admin)
    {
        $error = $data = null;

        $this->setApiCredentials();

        try
        {
            $body = [
                'token' => $admin->token
            ];

            $request = new ApiRequestAny(['client_type' => 'admin']);

            list($error, $data) = $request->processInput($body)->send('current_admin', 'POST');
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }
        catch (\Razorpay\Api\Errors\ServerError $e)
        {
            $error[] = $e->getMessage();
        }

        return [$error, $data];
    }

    public function uploadOrgLogo($orgId, $input)
    {
        // This is pretty useless in our case
        // since we won't make any request to API in RZP\Admin.
        // We just need access to ->api->admin and hence we're doing it.
        $this->setApiCredentials();

        $error = $data = null;

        $type = $input['type'];

        $file = $input["{$type}_logo"];

        $s3Client = $this->getS3Client('v2');

        $filePath = $file->getPathname();
        $fileName = $file->getFilename();
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getClientMimeType();

        $this->trace->info(TraceCode::ORG_LOGO_UPLOAD,[
            'extension'  => $extension,
            'mimetype'   => $mimeType
        ]);

        // if extension or mimetype both are not in allowed type: do not allow
        if ((in_array($extension, self::ORG_LOGO_ALLOWED_EXTENSIONS) === false) and
            (in_array($mimeType, self::ORG_LOGO_ALLOWED_MIMETYPES) === false))
        {
            return ['Invalid file format. Please upload a file with PNG or SVG extension.', $data];
        }

        // org_id/login_logo/file_name
        $keyName = "$orgId/{$type}_logo/$fileName";

        $region = config('aws.migrated_bucket_region');

        $bucket = config('aws.migrated_activation_bucket');

        $this->trace->info(TraceCode::S3_BUCKET_DETAILS, [
            'bucket'  => $bucket,
            'region'  => $region,
        ]);

        $s3Obj = [
            'Bucket'        => $bucket,
            'Key'           => $keyName,
            'SourceFile'    => $filePath,
            'ContentType'   => $mimeType,
            'ACL'           => 'public-read',
        ];

        try
        {
            $result = $s3Client->putObject($s3Obj);

            $data = $result['ObjectURL'];
        }
        catch (\Exception $e)
        {
            $error[] = $e->getMessage();
        }

        return [$error, $data];
    }

    public function uploadOrgBackgroundImage($orgId, $input)
    {
        $error = $data = null;

        $file = $input["background_image"];

        $s3Client = $this->getS3Client('v2');

        $filePath = $file->getPathname();
        $fileName = $file->getFilename();

        $keyName = "$orgId/background_image/$fileName";

        $region = config('aws.migrated_bucket_region');

        $bucket = config('aws.migrated_activation_bucket');

        $this->trace->info(TraceCode::S3_BUCKET_DETAILS, [
            'bucket'  => $bucket,
            'region'  => $region,
        ]);

        $s3Obj = [
            'Bucket'        => $bucket,
            'Key'           => $keyName,
            'SourceFile'    => $filePath,
            'ContentType'   => 'image/jpeg',
            'ACL'           => 'public-read',
        ];

        try
        {
            $result = $s3Client->putObject($s3Obj);

            $data = $result['ObjectURL'];
        }
        catch (\Exception $e)
        {
            $error[] = $e->getMessage();
        }

        return [$error, $data];
    }

    public function logout()
    {
        $error = $data = null;

        $this->setAdminCredentials();

        try
        {
            $request = new ApiRequestAny(['client_type' => 'admin']);

            list($error, $data) = $request->send('admin/logout', 'POST');

            $admin = Auth::guard('api');

            $adminUser = $admin->user();
            $traceData = [
                'email'     => $adminUser->email,
                'org_id'    => $adminUser->org_id,
            ];

            $this->trace->info(TraceCode::ADMIN_LOGOUT, $traceData);

            // Dashboard logout
            $admin->logout();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error = [$e->getMessage()];
        }

        return [$error, $data];
    }

    /**
     * This function returns the redis connection status
     *
     * @return array $response
     */
    protected function getRedisConnectionStatus()
    {
        $response = [
            'statusMessage' => 'ok',
            'statusCode'    => 200
        ];

        try
        {
            $redisConnection = $this->app['redis']->connection()->exists((string)rand(0 , 100));
        }
        catch (Exception $e)
        {
            Trace::info(TraceCode::REDIS_CONNECTION_ERROR, [
                'message' => $e->getMessage()
            ]);

            $response['statusMessage'] = 'Redis Connection Error';

            $response['statusCode'] = $e->getCode();
        }

        return $response;
    }

    /**
     * This function returns the api connection status
     *
     * @return array $response
     */
    protected function getAPIConnectionStatus()
    {
        $response = [
            'statusMessage' => 'ok',
            'statusCode'    => 200
        ];

        try
        {
            $stopServiceFileExists = file_exists("/app/public/graceful-shutdown.txt");

            if ($stopServiceFileExists === true)
            {
                Trace::info(TraceCode::API_GRACEFUL_SHUTDOWN_HAPPENING, []);

                return [
                    'statusMessage' => 'graceful shutdown happening',
                    'statusCode'    => 500
                ];
            }

            $apiBaseUrl = ApiUrl::getApiBaseUrl();
            // removing the /v1/ part at the end in the apiURL obtained from config

            // Checking on base URL instead of commit.txt
            // To fail liveliness of API, commit.txt file is deleted. Dashboard still hits commit.txt file
            // This sends 4xx URL not found from API and dashboard triggers false alerts
            $apiURL = substr($apiBaseUrl, 0, -4);

            $options = [
                'timeout' => Config::get('api.request_timeout'),
                'headers' => [
                    Headers::DEV_SERVE_USER => Request::header(Headers::DEV_SERVE_USER),
                    Headers::X_RAZORPAY_REQUEST_ID => Request::header(Headers::X_RAZORPAY_REQUEST_ID),
                    'X-Request-TraceId' => app('request')->requestId,
                ],
            ];

            $shouldRetry = true;

            for ($count = 1, $sleepTime = 2; $count <= 3 && $shouldRetry === true; $count++, $sleepTime = $sleepTime * 2)
            {
                $start_time = microtime(true);

                $APIConnection = Requests::request($apiURL, array(), array(), Requests::GET, $options);

                $end_time = microtime(true);

                $time_taken = $end_time - $start_time;

                // log if response time is more then 180 seconds
                if ($time_taken > 180)
                {
                    $shouldRetry = false;

                    Trace::info(TraceCode::API_SLOW_RESPONSE_CALL, [
                        'api_response_time' => $time_taken,
                    ]);
                }

                else if ($APIConnection->status_code === 200)
                {
                    $shouldRetry = false;
                }

                else
                {
                    $shouldRetry = true;

                    Trace::info(TraceCode::API_HEALTH_STATUS_CHECK_FAIL, [
                        'status_code'   => $APIConnection->status_code,
                        'body'          => $APIConnection->body,
                        'count'         => $count,
                        'time_taken'    => $time_taken,
                    ]);

                    sleep($sleepTime);
                }
            }

            if ($APIConnection->status_code !== 200)
            {
                Trace::info(TraceCode::API_ERROR_RESPONSE, [
                    'status_code'   => $APIConnection->status_code,
                    'url'           => $APIConnection->url,
                    'success'       => $APIConnection->success,
                    'body'          => $APIConnection->body,
                ]);

                throw new Exception('API Connection Error');
            }

        }
        catch (Exception $e)
        {

            Trace::error(
                TraceCode::API_REQUEST_FAILURE,
                [
                    'message'           => $e->getMessage(),
                ]);

            $response['statusMessage'] = 'API Connection Error';

            $response['statusCode'] = $e->getCode();
        }

        return $response;
    }

    /**
     * This function returns the status of the dashboard app
     *
     * @return array $response
     */
    public function getStatus()
    {
        $statusCode = 200;

        // Check Redis Connection
        $redisStatus = $this->getRedisConnectionStatus();

        if ($redisStatus['statusCode'] !== 200)
        {
            $statusCode = 500;
        }

        $response = [
            'redis' => $redisStatus['statusMessage'],
            'version' => app()->version(),
        ];

        return [$response, $statusCode];
    }

    public function getEmailLogs($input)
    {
        $error = $data = null;

        $this->trace->info(TraceCode::MAILGUN_GET_EMAIL_LOGS, [
            'recipient' => $input['recipient']
        ]);

        try
        {
            $data = (new Admin\Mailgun)->getLogs($input);
        }
        catch (\Exception $e)
        {
            $error = [$e->getMessage()];
        }

        return [$error, $data];
    }

    protected function getOrgDataFromCache($domain)
    {
        $cacheKey = $this->getOrgDataCacheKey($domain);

        return $this->cache->get($cacheKey);
    }

    protected function getOrgDataCacheKey($domain)
    {
        return self::CACHE_KEY_ORG_DATA . $domain;
    }

    protected function traceRawApiCall($input, $path)
    {
        $traceData = [
            'path'         => $path,
            'mode'         => $input['mode'],
            'method'       => $input['method'],
            'auth'         => $input['auth'],
            'content_type' => $input['content_type'] ?? '',
            'merchant_id'  => $input['merchant_id'] ?? '',
        ];

        $this->trace->info(TraceCode::ADMIN_RAW_API_CALL, $traceData);
    }

    public function triggerPassResetEmail($domain, array $input)
    {
        try {
            $request = new ApiRequestAny();
            list($error, $data) = $request->processInput($input)->send('admin/forgot_password', 'POST');
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e) {
            $error[] = $e->getMessage();
        }
        return [$error, $data];
    }

    public function changePassword($domain, array $input)
    {
        try {
            $request = new ApiRequestAny();
            list($error, $data) = $request->processInput($input)->send('admin/reset_password', 'POST');
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e) {
            $error[] = $e->getMessage();
        }
        return [$error, $data];
    }

    public function getKeysByPattern($pattern) {
        $keys = [];

        $cursor = null;

        $patternWithPrefix = "dashboard_:" . $pattern;

        do {
            [$cursor, $batch] = Redis::scan($cursor, 'MATCH', $patternWithPrefix);

            $keys = array_merge($keys, $batch);
        } while ($cursor !== '0');

        return $keys;
    }

    public function clearOrgCacheForAllDomains(): array
    {
        $success = [];
        $failure = [];
        $cacheKeys = $this->getKeysByPattern(self::CACHE_KEY_ORG_DATA . "*");
        foreach ($cacheKeys as $key) {
            $domain = substr($key, strlen("dashboard_:org_data_"));
            try
            {
                $this->cache->forget($key);

                $this->app['trace']->info(TraceCode::INVALIDATE_ORG_CACHE, [
                    'domain' => $domain,
                ]);

                array_push($success, $domain);
            }
            catch (\http\Exception $e)
            {
                $this->app['trace']->error(TraceCode::INVALIDATE_ORG_CACHE, [
                    'domain' => $domain,
                    'error' => $e->getMessage(),
                ]);

                array_push($failure, $domain);
            }

        }
        return ["success"=>$success, "failure"=> $failure];
    }
    public function loginToAdminExperienceService($merchantId)
    {

        $admin = Auth::guard('api')->user();

        $input = Input::all();

        $adminId = "";

        if (!key_exists('ticketID', $input))
        {
            return [null, [], []];
        }

        if (empty($admin) === false)
        {
            $adminId = $admin->id;
        }

        $requestBody = [
            "merchant"            => [
                "id" => $merchantId
            ],
            "admin"               => [
                "id" => $adminId
            ],
            "ticket_id"           => $input['ticketID'],
            "reasoning"           => !key_exists('reasoning', $input) ? "" : $input['reasoning'],
            "additional_comments" => !key_exists('additionalComments', $input) ? "" : $input['additionalComments'],
        ];

        $request = new \App\Admin\ApiRequestAny(
            $options=[
                'client_type'           => 'admin',
                'headers' => [
                    'x-admin-id'        => $adminId,
                    'x-Merchant-Id'     => $merchantId
                ]
            ]);

        list($error, $data, $statusCode) = $request->processInput($requestBody)->send(Constants::AES_LOGIN_PATH, 'POST');

        $this->trace->info(TraceCode::AES_LOGIN, [
            'error'               => $error,
            'data'                => $data,
            'response'            => $data,
            'input'               => $input,
        ]);

        return [$error, $data, $request->getResponseHeaders()];
    }
}

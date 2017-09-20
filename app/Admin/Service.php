<?php

namespace App\Admin;

use Auth;
use Hash;
use Uuid;
use Cache;
use Trace;
use Queue;
use Crypt;
use Input;
use Config;
use Session;
use Requests;
use App\Base;
use App\User;
use App\Admin;
use App\Generic;
use App\Merchant;
use App\Schedules;
use App\Providers;
use Carbon\Carbon;
use App\User\Helper;
use App\Transaction;
use UAParser\Parser;
use App\MerchantDetails;
use App\Trace\TraceCode;
use App\Mailers\MiscMailer;
use App\Providers\ApiGuard;
use App\Session as SessionTable;
use Aws\Laravel\AwsFacade as AWS;
use Razorpay\Api\Request as ApiRequest;
use Razorpay\Api\Errors\Error as ApiError;
use Illuminate\Support\Facades\App as App;
use App\Transaction\Service as TransactionService;
use Razorpay\Api\Errors\BadRequestError as BadRequestError;

class Service extends Base\Service
{
    // 15 minutes
    const TIMEOUT = 900;
    const ALREADY_ARCHIVED = 'Merchant already archived.';
    const ALREADY_SUSPENDED = 'Merchant already suspended.';
    const CANT_ARCHIVE_LIVE = 'Live merchants can not be archived.';
    const CANT_ARCHIVE_MERCHANT = 'Merchant should have submitted the form, form should be locked and account should not be activated to archive a merchant';
    const INVALID_CREDENTIALS = 'Username or password is invalid.';
    const PRIMARY_LOGIN_ERROR = "There is no user associated with this account.";
    const PAGE_SIZE = 1000;

    const SELF_INVITE_NOT_ALLOWED = "You can't invite yourself";

    // This is the Admin\Logger trait
    use Logger;

    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->cache = $app['cache'];
    }

    public function passwordLogin($domain, array $input)
    {
        $error = $data = null;

        try
        {
            // This is password based login
            $requestConfig = [
                'route_name'   => 'admin_authentication',
                'query_params' => $input,
            ];

            $genericService = new Generic\Service;

            list($error, $data) = $genericService->call('POST', $requestConfig);

            Session::put(config('auth.guards.api.session_key'), $data);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return [$error, $data];
    }

    public function oAuthLogin($input)
    {
        $error = $data = null;

        // This is oAuth based login
        $requestConfig = [
            'route_name'   => 'admin_oauth_authenticate',
            'query_params' => $input,
        ];

        $genericService = new Generic\Service;

        list($error, $data) = $genericService->call('POST', $requestConfig);

        return $data;
    }

    public function loginWithGoogle($code, $googleService, $orgId)
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

        // Fetch the admin with the email
        // $admin = Admin\Entity::where('email', $result->email)->first();
        // TODO: can throw exception
        $admin = $this->api
                      ->admin
                      ->getByEmail($orgId, ['email' => $result->email])
                      ->toArray();

        if ($admin)
        {
            $updateData = [
                'oauth_access_token'    => $token->getAccessToken(),
                'oauth_provider_id'     => $result->id
            ];

            // 1. Save the data (oauth token and provider) to API

            $requestConfig = [
                'route_name'   => 'admin_edit_app_auth',
                'query_params' => $updateData,
                'url_params'   => [
                    '{id}'  => $admin['id'],
                ],
            ];

            $genericService = new Generic\Service;

            list($error, $updatedAdmin) = $genericService->call('PUT', $requestConfig);

            // 2. Login the user to dashboard. Have to make an API call
            // to login the user and get an admin_token

            $oAuthLoginInput = [
                'email'                 => $updatedAdmin['email'],
                'oauth_access_token'    => $token->getAccessToken(),
                'oauth_provider_id'     => $result->id,
            ];

            try
            {
                $data = $this->oAuthLogin($oAuthLoginInput);

                Session::put(config('auth.guards.api.session_key'), $data);
            }
            catch (\Razorpay\Api\Errors\BadRequestError $e)
            {
                $error[] = $e->getMessage();
            }
        }
        else
        {
            $error[] = 'This email is not registered.';
        }

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
     * @return  Status
     */
    public function loginUsingPrimaryOwner($merchantId)
    {
        $error = [];

        $this->setApiCredentials();

        $users = $this->api->merchant->getUsers($merchantId)->toArray();

        $genericUsers = (new Helper)->createGenericUsers($users);

        $primaryOwner = $genericUsers->where('role', 'owner')
                                     ->first();

        if ($primaryOwner === null)
        {
            $error[] = self::PRIMARY_LOGIN_ERROR;

            return $error;
        }

        try
        {
            list($error, $user) = (new User\Service)->getUserFromApi($primaryOwner->id);

            if (empty($error) === false)
            {
                return $error;
            }

            $this->app['session']->put('dashboard_user_payload', $user);

            Auth::login($user, false);

            (new User\Service)->switchCurrentMerchantForUser($merchantId, $user);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = self::PRIMARY_LOGIN_ERROR;
        }

        return $error;
    }

    public function getMerchantIdsToList(string $orgId, string $adminId)
    {
        $this->setAdminCredentials();

        return $this->api->admin->fetchMerchantIds($orgId, $adminId);
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

    public function fetchMerchantActivationDetails($id)
    {
        $merchantDetails =  MerchantDetails\Entity::findorfail($id);

        $response = $merchantDetails->filterDetails();

        $files = [];

        foreach ($response['files'] as $key => &$file)
        {
            $extension_position = strrpos($file, '.', -1);
            $extension  = substr($file, $extension_position + 1);

            $s3 = $this->getS3Client();

            try
            {
                $cmd = $s3->getCommand('GetObject', [
                    'Bucket' => env('AWS_ACTIVATION_BUCKET'),
                    'Key'    => $id.'/'.$key.'.'.$extension
                ]);

                $request = $s3->createPresignedRequest($cmd, '+60 minutes');

                $file = (string) $request->getUri();
            }
            catch (\Exception $e)
            {
                $file = 'ERROR: ' . $e->getMessage();
            }

            $files[$key] = $file;
        }

        $fileResponse['files'] = $files;
        return $fileResponse;
    }

    public function fetchMerchantAndActivationDetails($id)
    {
        if ($id === null)
        {
            return [['id' => 'Merchant id cannot be null'], []];
        }

        $details = $this->fetchMerchantDetails($id);

        $merchantDetail = new MerchantDetails\Service;

        //
        // If parent_id is set, it is a marketplace linked account
        // and we set the context for it
        //
        if (isset($details['parent_id']) === true)
        {
            $merchantDetail->setLinkedAccount(true);

            if ((isset($details['linked_account_kyc']) === true) and
                ($details['linked_account_kyc'] === 1))
            {
                $merchantDetail->setLinkedAccountKYCRequired(true);
            }
        }

        $activationDetails = $merchantDetail->getActivationFiles($id);

        $data = [
            'activation' => $activationDetails,
            'merchant'   => $details,
        ];

        return [[], $data];
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
            $users = $this->api->merchant->getUsers($id)->toArray();

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
            'steps_finished'      => $merchantDetail['steps_finished'],
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

            if (isset($input['transaction_report_email']))
            {
                $params = ['transaction_report_email' => $csvEmail];
                // Only when it is changed on API side we update on the dashboard side as well
                $error = (new MerchantDetails\Service)->updateMerchantByAdminOnAPI($params, $id);
            }

            if (isset($input['name']))
            {
                $error = Merchant\Service::changeName($id, $input['name']);
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
        $data = $error = [];
        $this->setApiCredentials();

        $input[Merchant\Entity::EMAIL] = strtolower($input[Merchant\Entity::EMAIL]);

        try
        {
            $existingMerchant = Merchant\Entity::getMerchantFromEmail($input[Merchant\Entity::EMAIL]);

            if ($existingMerchant !== null)
            {
                $error[] = "Merchant already exists with this email id.";
                return [$error, $data];
            }

            $data = $this->api->merchant->fetch($id)->editEmail($input)->toArray();

            // Only when it is changed we update on the dashboard side as well
            list($e,) = (new Merchant\Service)->changeEmail($id, $input);
            $this->logActionToSlack($id, Actions::EMAIL_EDITED, $input);
            $error = $e;
        }

        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return [$error, $data];
    }

    protected function dropFields(array &$array, array $fields)
    {
        foreach ($fields as $key)
        {
            unset($array[$key]);
        }
    }

    public function lockMerchant($id)
    {
        $error = $merchantDetails = [];

        $params = ['locked' => true];

        list($error, $merchantDetails) = (new MerchantDetails\Service)->updateMerchantByAdminOnAPI($params, $id);

        return $error;
    }

    public function postMerchantTerminal($id, $input)
    {
        $error = (new Merchant\Validator)->validateInput('terminal', $input)->messages();

        $data = [];

        $mode = $input['mode'];

        if (empty($error))
        {
            $this->dropFields($input, [
                'gateway_terminal_password_confirmation',
                'mode',
            ]);

            $this->setApiCredentials(null, $mode);

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
            $merchant = Merchant\Entity::findorfail($id);

            $this->activateMerchantOnDashboard($merchant);

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

    protected function activateMerchantOnDashboard($merchant)
    {
        $merchant->activated = 1;
        $merchant->save();

        $this->lockMerchant($merchant->id);

        return array();
    }

    public function generateMerchantHdfcExcel($id)
    {
        if ($id === null)
        {
            return [['id' => 'Merchant id cannot be null'], []];
        }

        list(, $data) = $this->fetchMerchantAndActivationDetails($id);

        $file = Hdfc\HdfcTidExcel::generateExcel($data);

        $this->logActionToSlack($id, Actions::HDFC_EXCEL);

        return [[], $file];
    }

    public function fetchMultipleEntities($mode, $entity, $input)
    {
        $error = array();

        $response = array();

        $this->setApiCredentials(null, $mode);

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

    public function editName($merchantId, $input)
    {
        $response = $error = null;

        $this->setApiCredentials();

        try
        {
            $response = $this->api->merchant->fetch($merchantId)->edit($input);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error = [$e->getMessage()];
        }

        return [$response, $error];
    }

    public function makeRawApiCall($path)
    {
        $input = \Input::all();
        $error = [];

        $validator = (new Admin\Validator);
        $validator->setStrictFalse();
        $error = $validator->validateInput('api_call', $input)->messages();

        if ($error)
        {
            return [$error, []];
        }

        $request = new RawApiRequest($input, $path);
        return $request->send();
    }

    public function tagMerchant($merchantId, $input)
    {
        $error = (new Admin\Validator)->validateInput('add_tags', $input)->messages();

        if (empty($error))
        {
            $merchant = Merchant\Entity::findOrFail($merchantId);

            if (is_array($input['tags']) === false)
            {
                $inputTags = explode(',', $input['tags']);
            }
            else
            {
                $inputTags = $input['tags'];
            }

            (new Merchant\Service)->addMerchantTagsOnAPI($merchantId, $inputTags);

            $output = [];

            $output['tags'] = (new Merchant\Service)->getMerchantTags($merchantId);

            $this->logActionToSlack($merchant, Actions::TAGGED, ['tags' => $input['tags']]);

            $output = array_merge($merchant->toArray(), $output);

            return [null, $output];
        }
        else
        {
            return [$error, null];
        }
    }

    public function addEntityFeatures($entityType, $entityId, $input)
    {
        $error = $response = array();

        if (!empty($error))
        {
            return array($error, null);
        }

        $this->setApiCredentials(null, $input['mode']);

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
        $merchant = Merchant\Entity::findOrFail($entityId);

        $featureNames = $this->getFeatureNames($features['assigned_features']);

        $merchantTags = (new Merchant\Service)->getMerchantTags($merchant->id);

        (new Merchant\Service)->addMerchantTagsOnAPI($merchant->id, array_merge($featureNames, $merchantTags));
    }

    private function getFeatureNames($features)
    {
        $featureNames = array_map(function ($feature)
        {
            return $feature['name'];
        }, $features);

        return $featureNames;
    }

    public function confirmUser($email)
    {
        list($error, $data) = (new User\Service)->confirmUserByEmail($email);

        return [$error, $data];
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

    public function getOrg($domain)
    {
        $requestConfig = [
            'route_name' => 'org_get_by_hostname',

            'url_params' => [
                '{hostname}' => $domain
            ]
        ];

        $genericService = new Generic\Service;

        list($error, $data) = $genericService->call('GET', $requestConfig);

        if (empty($error))
        {
            $this->setOrgInCache($data);
        }

        return [$error, $data];
    }

    protected function setOrgInCache($org)
    {
        $cacheKey = $org['hostname'];

        if ($this->cache->has($cacheKey) === false)
        {
            $this->cache->put($cacheKey, $org['id'], 10);
        }
    }

    protected function getOrgFromCache($domain)
    {
        list($error, $data) = $this->getOrg($domain);

        return $data;

        // Disabling cache for now

        // $cacheKey = $domain;
        //
        // if ($this->cache->has($cacheKey) === false)
        // {
        //     $this->getOrg($domain);
        // }
        //
        // return $this->cache->get($cacheKey);
    }

    public function getAdminData($admin)
    {
        $error = $data = null;

        $this->setApiCredentials();

        try
        {
            $params = [
                'token' => $admin->token
            ];

            $requestConfig = [
                'route_name'    => 'admin_get_app_auth',
                'query_params'  => $params,
            ];

            $genericService = new Generic\Service;

            list($error, $data) = $genericService->call('POST', $requestConfig);
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

        $s3Client = $this->getS3Client();

        $filePath = $file->getPathname();
        $fileName = $file->getFilename();
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getClientMimeType();

        if ($extension !== 'png' and $mimeType !== 'image/png')
        {
            return ['Invalid file format. Please upload a file with PNG extension.', $data];
        }

        // org_id/login_logo/file_name
        $keyName = "$orgId/{$type}_logo/$fileName";

        $s3Obj = [
            'Bucket'        => config('aws.activation_bucket'),
            'Key'           => $keyName,
            'SourceFile'    => $filePath,
            'ContentType'   => 'image/png',
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
            $requestConfig = [
                'route_name' => 'admin_logout',
            ];

            $genericService = new Generic\Service;

            list($error, $data) = $genericService->call('POST', $requestConfig);

            // Dashboard logout
            Auth::guard('api')->logout();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error = [$e->getMessage()];
        }

        return [$error, $data];
    }

    public function getEmailLogs($input)
    {
        $error = $data = null;

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
}

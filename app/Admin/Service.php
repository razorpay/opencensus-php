<?php

namespace App\Admin;

use App\Admin;
use App\Base;
use App\Merchant;
use App\MerchantDetails;
use App\Trace\TraceCode;
use App\Transaction;
use App\User;
use App\Mailers\MiscMailer;
use App\Session as SessionTable;
use App\Providers\ApiGuard;
use App\Schedules;
use App\Generic;

use Auth;
use Config;
use Hash;
use Requests;
use Queue;
use Session;
use Crypt;
use Cache;
use Uuid;
use Trace;

use Aws\Laravel\AwsFacade as AWS;
use Carbon\Carbon;
use Illuminate\Support\Facades\App as App;

use Razorpay\Api\Errors\BadRequestError as BadRequestError;
use App\Transaction\Service as TransactionService;
use Razorpay\Api\Errors\Error as ApiError;
use Razorpay\Api\Request as ApiRequest;

use UAParser\Parser;

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

    public function forgotPassword($input)
    {
        $error = $data = null;

        $domain = \Request::server('SERVER_NAME');

        $org = $this->getOrgFromCache($domain);

        // `/access/resetpwd` is a hard-coded angular route
        $resetPasswordUrl = 'https://' . $org['hostname'] . '/admin#/access/resetpwd';

        $input['reset_password_url'] = $resetPasswordUrl;

        try
        {
            $data = $this->api->admin->forgotPassword($org['id'], $input);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return [$error, $data];
    }

    public function resetPassword($input)
    {
        $error = $data = null;

        $domain = \Request::server('SERVER_NAME');

        $org = $this->getOrgFromCache($domain);

        try
        {
            $data = $this->api->admin->resetPassword($org['id'], $input);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return [$error, $data];
    }

    public function passwordLogin($domain, array $input)
    {
        $error = $data = null;

        $this->setApiCredentials();

        $org = $this->getOrgFromCache($domain);

        try
        {
            // This is password based login
            $data = $this->api->admin->passwordLogin($org['id'], $input)->toArray();

            Session::put(config('auth.guards.api.session_key'), $data);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return [$error, $data];
    }

    public function oAuthLogin($input, $orgId)
    {
        $error = $data = null;

        $this->setApiCredentials();

        $data = $this->api->admin->oAuthLogin($input, $orgId)->toArray();

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

            $updatedAdmin = $this->api->admin->updateAdmin($orgId, $admin['id'], $updateData);

            // 2. Login the user to dashboard. Have to make an API call
            // to login the user and get an admin_token

            $oAuthLoginInput = [
                'email'                 => $updatedAdmin['email'],
                'oauth_access_token'    => $token->getAccessToken(),
                'oauth_provider_id'     => $result->id,
            ];

            try
            {
                $data = $this->oAuthLogin($oAuthLoginInput, $orgId);

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
    public function loginUsingPrimaryOwner($merchant_id)
    {
        $error = [];

        $merchant = Merchant\Entity::findOrFail($merchant_id);

        $ownerUser = $merchant->primaryOwner();

        if ($ownerUser)
        {
            $user = Auth::guard('user')->loginUsingId($ownerUser->id);
            (new User\Service)->switchCurrentMerchantForUser($merchant_id, $user);
        }
        else
        {
            $error[] = self::PRIMARY_LOGIN_ERROR;
        }

        return $error;
    }

    /**
     * Changes password oflogged in admin
     *
     * @param  $input input array
     * @param  $admin Admin\Entity Object
     * @return  Status
     */
    public function changePassword($input, $admin)
    {
        $error = $admin->changePassword($input);

        if (empty($error))
        {
            $admin->password = Hash::make($admin->password);
            $admin->saveOrFail();
        }

        return [$error, null];
    }

    public function editAdmin($input, $id)
    {
        $error = [];

        try
        {
            $this->logAdminEdits($id, $input);

            $error = array();

            $admin = Admin\Entity::findorfail($id);

            $error = $admin->edit($input);

            $admin->saveOrFail();

            if (empty($error) === true)
            {
                // Delete all existing sessions
                $this->deleteAllAdminSessions($id);
            }
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, []);
    }

    public function listMerchants($input)
    {
        $user = Auth::guard('api')->user();

        $adminId = $user->id;

        $orgId = $user->org_id;

        $response = [];

        try
        {
            $merchants = $this->getMerchants($orgId, $adminId, $input)->toArray();

            $merchantIds = array_column($merchants, 'id');

            $data = Merchant\Entity::select(['merchants.id'])
                                    ->with('tagged')
                                    ->whereIn('merchants.id', $merchantIds);

            if (isset($input['tags']))
            {
                $data = $data->withAllTags($input['tags']);
            }

            $data = $data->get()->toArray();

            foreach ($merchants as $merchant)
            {
                $key = array_search($merchant['id'], array_column($data, 'id'));

                if ($key !== false)
                {
                    unset($data[$key]['referrer']);

                    $response[] = array_merge($merchant, $data[$key]);
                }
            }
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            // something went wrong
        }

        return [
            'count' => count($response),
            'data'  => $response
        ];
    }

    public function getMerchantIdsToList(string $orgId, string $adminId)
    {
        $this->setAdminCredentials();

        return $this->api->admin->fetchMerchantIds($orgId, $adminId);
    }

    public function getMerchants(string $orgId, string $adminId, array $input)
    {
        $this->setAdminCredentials();

        return $this->api->admin->fetchMerchants($orgId, $adminId, $input);
    }


    public function getAdmins()
    {
        return Admin\Entity::get()->toArray();
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

    public function deleteAllAdminSessions($adminId)
    {
        (new SessionTable\Entity)->deleteAllSessionsForAdmin($adminId);
    }

    /**
     * Adds a new admin
     * @param $data input array
     * @return Status
     */
    public function add($input)
    {
        $admin = new Admin\Entity;
        $error = $admin->build($input);

        if (empty($error))
        {
            $admin->password = Hash::make($admin->password);
            $admin->saveOrFail();
        }

        return array($error, $admin->toArray());
    }

    /**
     * Adds a new admin
     * @param $data input array
     * @return Status
     */
    public function promote($id)
    {
        $admin = Admin\Entity::findorfail($id);

        try
        {
            $admin = $admin->promote();
            return [null];
        }
        catch(\Exception $e)
        {
            return [$e->getMessage()];
        }
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

        $activationDetails = (new MerchantDetails\Service)->getActivationFiles($id);

        $data = array(
            'activation' => $activationDetails,
            'merchant'   => $details
        );

        return [[], $data];
    }

    public function fetchEntityFeatures($entityId)
    {
        $this->setApiCredentials();

        $response = $this->api->feature->getFeatures($entityId);

        return [[], $response];
    }

    public function fetchFullMerchantDetails($id)
    {
        $details = null;

        $error = [];

        if ($id !== '10NodalAccount')
        {
            $details = $this->fetchMerchantDetails($id);
        }
        $terminal = $this->fetchMerchantTerminal($id);

        $pricingPlan = $this->fetchMerchantPricing($id);

        $scheduleTasks = $this->fetchMerchantSchedule($id);

        $data = array(
                    'details' => $details,
                    'terminals' => $terminal,
                    'pricing_plan' => $pricingPlan,
                    'schedule_tasks' => $scheduleTasks);

        return [$error, $data];
    }

    public function fetchMerchantDetails($id)
    {
        $merchant = Merchant\Entity::findOrSoftFail($id);

        $this->setApiCredentials();

        try
        {
            $data = $this->api->merchant->fetch($id)->toArray();
        }
        catch (BadRequestError $e)
        {
            $merchant = $merchant->toArray();
            $merchant['confirmed'] = false;
            return $merchant;
        }

        $parentId = $data['parent_id'] ?? null;

        // If parent_id is set, the merchant is a linked account under Marketplace
        // and are marked confirmed, without email confirmation
        if ($parentId !== null)
        {
            $data['confirmed'] = true;
        }
        else
        {
            try
            {
                $data['confirmed'] = ($merchant->primaryOwner()->getConfirmToken() === null);
            }
            catch (\Exception $e)
            {
                $data['confirmed'] = true;
            }
        }

        $merchantDetail = (new MerchantDetails\Service)->fetchDetails($id);

        $data['merchant_details'] = $merchantDetail;

        $merchant = $merchant->toArray();

        // @todo This is failing tests on wercker, fix
        // $merchant = Merchant\Entity::findorfail($id);
        // Merchant\Validator::checkAPIMatch($merchant, $response);

        $response = [
            'archived_at'         => $merchant['archived_at'],
            'suspended_at'        => $merchant['suspended_at'],
            'steps_finished'      => $merchantDetail['steps_finished'],
            'locked'              => $merchantDetail['locked'],
            'submitted'           => $merchantDetail['submitted'],
            'tags'                => $merchant['tags'],
            'submitted_at'        => $merchantDetail['submitted_at'],
            'activated_dashboard' => $merchant['activated'],
            'referrer'            => $merchant['referrer'],
        ] + $data;

        return $response;
    }

    public function fetchMerchantBalance($id)
    {
        $this->setApiCredentials(null, 'test');

        $test = $this->api->merchant->setId($id)->fetchBalance()->toArray();

        $this->setApiCredentials(null, 'live');

        $live = $this->api->merchant->setId($id)->fetchBalance()->toArray();

        return compact('test', 'live');
    }

    public function fetchMerchantBanks($id)
    {
        $this->setApiCredentials();

        $response = $this->api->merchant->setId($id)->fetchBanks()->toArray();

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
                $this->addTagToMerchant($id, 'feebearer');
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

    protected function logAdminEdits($id, $input)
    {
        if (isset($input['email']))
        {
            $this->logActionToSlack($id, Actions::ADMIN_EDIT);
        }
    }

    public function postEditMerchantEmail($id, $input)
    {
        $data = $error = [];
        $this->setApiCredentials();

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

    public function postSetMerchantInternational($id, array $input)
    {
        return $this->postEditMerchant($id, $input);
    }

    protected function dropFields(array &$array, array $fields)
    {
        foreach ($fields as $key)
        {
            unset($array[$key]);
        }
    }

    public function postEditBankDetails($id, $input)
    {
        $error = array();

        $this->dropFields($input, [
            "beneficiary_address4",
            "beneficiary_code",
            "beneficiary_country",
            "created_at",
            "entity_id",
            "type",
            'id',
            'merchant_id',
            'mpin_set',
        ]);

        $this->setApiCredentials();

        $error = $merchantDetail = [];

        try
        {
            $this->api->merchant->fetch($id)->setBankAccount($input);

            $merchantDetailsData = array(
                'bank_branch_ifsc'           => $input['ifsc_code'],
                'bank_account_name'          => $input['beneficiary_name'],
                'bank_account_number'        => $input['account_number'],
                'bank_beneficiary_address1'  => $input['beneficiary_address1'],
                'bank_beneficiary_address2'  => $input['beneficiary_address2'],
                'bank_beneficiary_address3'  => $input['beneficiary_address3'],
                'bank_beneficiary_pin'       => $input['beneficiary_pin'],
                'bank_beneficiary_city'      => $input['beneficiary_city'],
                'bank_beneficiary_state'     => $input['beneficiary_state']
            );

            list($error, $merchantDetails) = (new MerchantDetails\Service)->updateMerchantByAdminOnAPI($merchantDetailsData, $id);

            $this->logActionToSlack($id, Actions::BANK_DETAILS_EDITED, $input);
        }

        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return [$error, $merchantDetails];
    }

    public function postEditMerchantComment($id, $comment)
    {
        $error = $merchantDetails = [];

        $params = ['comment' => $comment];

        list($error, $merchantDetails) = (new MerchantDetails\Service)->updateMerchantByAdminOnAPI($params, $id);

        return [$error, $comment];
    }

    public function postMerchantBanks($id, $input)
    {
        $error = (new Merchant\Validator)->validateInput('banks', $input)->messages();

        $data = [];

        if (empty($error))
        {
            $this->setApiCredentials();

            try
            {
                $data = $this->api->merchant->fetch($id)->setBanks($input)->toArray();
                $this->logActionToSlack($id, Actions::BANK_LIST_EDITED);
            }
            catch (\Razorpay\Api\Errors\BadRequestError $e)
            {
                $error[] = $e->getMessage();
            }
        }

        return array($error, $data);
    }

    public function postAddAdjustment($id, $input)
    {
        $data = [];
        $error = [];
        $logData = $input;

        $mode = $input['mode'];
        unset($input['mode']);

        $this->setApiCredentials($id, $mode);

        try
        {
            $data = $this->api->adjustment->create($input)->toArray();
            $this->logActionToSlack($id, Actions::ADJUSTMENT_ADDED, $logData);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $data);
    }

    public function refundAuthorizedPayment($mode, $merchantId, $id)
    {
        $data = [];
        $error = [];

        $this->setApiCredentials($merchantId, $mode);

        try
        {
            $data = $this->api->payment->fetch($id)
                ->refundAuthorized()
                ->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $data);
    }

    public function refundPayment($mode, $merchantId, $id, $input)
    {
        $data = [];
        $error = [];

        $this->setApiCredentials($merchantId, $mode);

        try
        {
            $data = $this->api->payment->fetch($id)
                ->refund($input)
                ->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $data);
    }

    /**
     * Makes a request to fetch the list of payment_analytics entities
     * for that payment, and then returns the URL for the entity
     * itself
     * @param  string $mode
     * @param  string $paymentId
     * @return array
     */
    public function getPaymentAnalytics($mode, $paymentId)
    {
        $this->stripSign($paymentId);

        $analytics = null;

        list($error, $data) = $this->fetchMultipleEntities($mode, 'payment_analytics', [
            'payment_id'    =>  $paymentId
        ]);

        if (empty($error) and $data['count'] === 1)
        {
            $analytics = $data['items'][0];

            $ua_parsed = [];

            $original_ua = $analytics['user_agent'];

            try
            {
                $parser = Parser::create();

                $analytics['user_agent'] = $parser->parse($original_ua)->toString();
            }

            // Catch any index errors and return the
            // default response instead
            catch(\Exception $e)
            {
                $analytics['user_agent'] = $original_ua;
            }
        }
        else if ($data['count'] === 0)
        {
            $error[] = 'No analytics found';
        }

        return [$error, $analytics];
    }

    public function getPaymentRefunds($mode, $paymentId)
    {
        list($error, $response) = $this->fetchEntityById($mode, 'payment', $paymentId);

        if(empty($error))
        {
            $merchantId = $response['merchant_id'];
            $this->setApiCredentials($merchantId, $mode);

            try
            {
                $data = $this->api->payment->fetch($paymentId)
                    ->refunds()
                    ->all()
                    ->toArray();
            }
            catch (\Razorpay\Api\Errors\BadRequestError $e)
            {
                $error = $e->getMessage();
            }

            return array($error, $data);
        }
        else
        {
            return [$error, null];
        }
    }

    public function capturePayment($mode, $merchantId, $id, $input)
    {
        $data = [];
        $error = [];

        $this->setApiCredentials($merchantId, $mode);

        try
        {
            $data = $this->api->payment->fetch($id)
                ->capture($input)
                ->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $data);
    }

    public function lockMerchant($id)
    {
        $error = $merchantDetails = [];

        $params = ['locked' => true];

        list($error, $merchantDetails) = (new MerchantDetails\Service)->updateMerchantByAdminOnAPI($params, $id);

        $this->logActionToSlack($id, Actions::FORM_LOCKED);

        return $error;
    }

    public function unlockMerchant($id)
    {
        $error = $merchantDetails = [];

        $params = ['locked' => false];

        list($error, $merchantDetails) = (new MerchantDetails\Service)->updateMerchantByAdminOnAPI($params, $id);

        $this->logActionToSlack($id, Actions::FORM_UNLOCKED);

        return $error;
    }

    public function fetchMerchantTerminal($id)
    {
        $this->setApiCredentials();

        $liveTerminals = $this->api->merchant->setId($id)->fetchTerminals()->toArray();

        $this->setApiCredentials(null, 'test');

        $testTerminals = $this->api->merchant->setId($id)->fetchTerminals()->toArray();

        foreach ($testTerminals['items'] as &$item)
        {
            $item['mode'] = 'test';
        }

        foreach ($liveTerminals['items'] as &$item)
        {
            $item['mode'] = 'live';
        }

        $response = array(
            'entity'    => 'collection',
            'count'     => $liveTerminals['count'] + $testTerminals['count'],
            'items'     => array_merge($liveTerminals['items'], $testTerminals['items'])
        );

        return $response;
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

    public function fetchMerchantPricing($id)
    {
        $this->setApiCredentials();

        $response = $this->api->merchant->fetch($id)->fetchPricing()->toArray();

        return $response;
    }

    public function fetchMerchantSchedule($id)
    {
        $this->setApiCredentials(null, 'live');

        $response = $this->api->admin->fetchMultipleEntities('schedule_task', ['merchant_id' => $id])->toArray();

        return $response;
    }

    public function postMerchantPricing($id, $input)
    {
        $error = array();
        $data = array();

        $originalInput = $input;

        if (isset($input['pricing_plan_name']))
        {
            unset($input['pricing_plan_name']);
        }

        $this->setApiCredentials();

        try
        {
            $data = $this->api->merchant->fetch($id)->setPricing($input)->toArray();
            $this->logActionToSlack($id, Actions::PRICING_PLAN_SET, $originalInput);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $data);
    }

    /**
     * Fetches bank account details for a merchant from the API
     * @param  string $merchantId Merchant Id
     * @return array Bank Account Details
     */
    public function fetchBankAccount($merchantId)
    {
        $this->setApiCredentials();

        try
        {
            $ba = $this->api->merchant
                ->setId($merchantId)
                ->fetchBankAccount()
                ->toArray();

            return [null, $ba];
        }
        catch(\Razorpay\Api\Errors\BadRequestError $e)
        {
            return [$e->getMessage(), null];
        }
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
            $data['beneficiary_address1']   = 'NA';
            $data['beneficiary_city']       = 'NA';
            $data['beneficiary_state']      = 'NA';
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
        $this->setApiCredentials();

        $merchant = $this->api->merchant->fetch($id);

        $details = $this->fetchMerchantDetails($id);

        if ((int) $details['submitted'] === 0)
        {
            return ['Activation form has not been submitted by merchant yet.'];
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
            $this->setApiCredentials();

            // Only if the merchant doesn't have the Bank Account associated
            // Do we add a bank account
            if ($bankAccountApi === false)
            {
                $this->api->merchant->fetch($id)->setBankAccount($bankAccount);
            }

            if ($merchant['activated'] === false)
            {
                $this->api->merchant->fetch($id)->activate();
            }
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            return [$e->getMessage()];
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

            return $this->activateMerchantOnDashboard($merchant);
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

    public function liveEnableMerchant($id)
    {
        $error = [];

        $this->setApiCredentials();

        try

        {
            $this->api->merchant->fetch($id)->enable();

            $merchant = Merchant\Entity::findOrSoftFail($id);
            $this->logActionToSlack($merchant, Actions::LIVE_ENABLED);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error = $e->getMessage();
        }

        return $error;
    }

    /**
     * Merchant Archive
     * Conditions: merchant_details->submitted != null
     *             and merchant_details->locked = true
     *             and merchant->activated = false
     *
     * @param  string $id
     * @return array
     */
    public function archiveMerchant($id)
    {
        $error = $this->actions($id, 'archive');

        if (empty($error) === true)
        {
            // For backward compatibility
            $merchant = Merchant\Entity::findOrSoftFail($id);
            $merchant->archive();

            $this->logActionToSlack($merchant, Actions::ARCHIVED);
        }

        return $error;
    }

    public function unarchiveMerchant($id)
    {
        $error = $this->actions($id, 'unarchive');

        if (empty($error))
        {
            // For backward compatibility
            $merchant = Merchant\Entity::findorfail($id);
            $merchant->archived_at = null;
            $merchant->save();

            $this->logActionToSlack($merchant, Actions::UNARCHIVED);
        }

        return $error;
    }

    public function suspendMerchant($id)
    {
        $error = $this->actions($id, 'suspend');

        if (empty($error))
        {
            $merchant = Merchant\Entity::findOrSoftFail($id);
            $merchant->suspend();

            $this->logActionToSlack($merchant, Actions::SUSPENDED);
        }

        return $error;
    }

    public function unsuspendMerchant($id)
    {
        $error = $this->actions($id, 'unsuspend');

        if (empty($error))
        {
            $merchant = Merchant\Entity::findorfail($id);
            $merchant->suspended_at = null;
            $merchant->save();

            $this->logActionToSlack($merchant, Actions::UNSUSPENDED);
        }

        return $error;
    }

    public function liveDisableMerchant($id)
    {
        $error = [];

        $this->setApiCredentials();

        try
        {
            $this->api->merchant->fetch($id)->disable();

            $merchant = Merchant\Entity::findOrSoftFail($id);
            $this->logActionToSlack($merchant, Actions::LIVE_DISABLED);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error = $e->getMessage;
        }

        return $error;
    }

    /**
     * Edits the merchant's methods
     *
     * @param  string $id      Merchant Id
     * @param  array $methods Array containing methods
     *                        with values 0/1
     * @return array $error
     */
    public function editMethods($id, $methods)
    {
        $error = [];

        $this->setApiCredentials();

        try
        {
            $this->api->merchant->fetch($id)->editMethods($methods);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            return [$e->getMessage()];
        }

        return $error;
    }

    public function fetchPricingPlan($id)
    {
        $errors = array();

        $response = array();

        $this->setApiCredentials();

        try
        {
            $response = $this->api->pricing->fetch($id)->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $errors[] = $e->getMessage();
        }

        return array($errors, $response);
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

    public function getUploadedFile($id)
    {
        $error = null;
        $url = null;

        $this->setApiCredentials();

        try
        {
            $file = $this->api
                         ->admin
                         ->getFileByAdmin($id);
            $url = $file->headers->offsetGet('location');
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error = [ $e->getMessage() ];

            Trace::debug('MISC_TRACE_CODE', [
                    'error'     => "Error occured while getting requested file from API",
                    'exception' => $error,
            ]);
        }

        return array($error, $url);
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
            'Bucket'        => $_ENV['AWS_ACTIVATION_BUCKET'],
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

        $bucket = env('AWS_ACTIVATION_BUCKET');

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

    public function sendTestNewsletter($input)
    {
        $this->setApiCredentials();

        try
        {
            $input['email'] = Auth::guard('api')->user()->email;

            return [null, $this->api->admin->sendTestNewsletter($input)
                ->toArray()];
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            return [$e->getMessage(), null];
        }
    }

    public function sendNewsletter($input)
    {
        $this->setApiCredentials();

        try
        {
            return [null, $this->api->admin->sendNewsletter($input)
                ->toArray()];
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            return [$e->getMessage(), null];
        }
    }

    public function deleteTerminal($mode, $terminalId)
    {
        $this->setApiCredentials(null, $mode);
        try
        {
            $response = $this->api->terminal->delete($terminalId);
            return [null, $response->toArray()];
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            return [$e->getMessage(), null];
        }
    }

    public function editTerminal($mode, $terminalId, $input)
    {
        $this->setApiCredentials(null, $mode);

        try
        {
            $response = $this->api->terminal->edit($terminalId, $input);
            return [null, $response->toArray()];
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            return [$e->getMessage(), null];
        }
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

    public function unassignSubMerchantToTerminal($mode, $terminalId, $merchantId)
    {
        $error = $response = null;

        $this->setApiCredentials(null, $mode);

        try
        {
            $response = $this->api->terminal->unassignSubMerchant($terminalId, $merchantId)->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error = $e->getMessage();
        }

        return [ $error, $response ];
    }

    public function assignSubMerchantToTerminal($mode, $terminalId, $merchantId)
    {
        $error = $response = null;

        $this->setApiCredentials(null, $mode);

        try
        {
            $response = $this->api->terminal->assignSubMerchant($terminalId, $merchantId)->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error = $e->getMessage();
        }

        return [ $error, $response ];
    }

    public function changeTerminalPrimaryMerchant($mode, $terminalId, $input)
    {
        $error = $response = null;

        $this->setApiCredentials(null, $mode);

        try
        {
            $response = $this->api->terminal->changePrimaryMerchant($terminalId, $input)->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error = $e->getMessage();
        }

        return [ $error, $response ];
    }

    public function toggleTerminal($mode, $terminalId, $input)
    {
        $this->setApiCredentials(null, $mode);
        try
        {
            $response = $this->api->terminal->toggle($terminalId, $input);
            return [null, $response->toArray()];
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            return [$e->getMessage(), null];
        }
    }

    public function editCredits($merchantId, $input)
    {
        $this->setApiCredentials(null, 'live');

        try
        {
            $response = $this->api->merchant->
                fetch($merchantId)->editCredits($input);
            $this->logActionToSlack($merchantId, Actions::FREE_CREDITS_EDIT, $input);

            return [null, $response->toArray()];
        }

        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            return [[$e->getMessage()], null];
        }

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
        $error = (new Admin\Validator)->validateInput('add_tags', $input)
            ->messages();

        if (empty($error))
        {
            $merchant = Merchant\Entity::findOrFail($merchantId);
            $merchant->retag(explode(',', $input['tags']));
            $merchant['tags'] = $merchant->tags;
            $this->logActionToSlack($merchant, Actions::TAGGED, ['tags' => $input['tags']]);

            return [null, $merchant->toArray()];
        }
        else
        {
            return [$error, null];
        }
    }

    protected function addTagToMerchant($merchantId, $tag)
    {
        $merchant = Merchant\Entity::findOrFail($merchantId);
        $merchant->tag($tag);
    }

    public function addEntityFeatures($entityType, $entityId, $input)
    {
        $error = $response = array();

        if (!empty($error))
        {
            return array($error, null);
        }

        $this->setApiCredentials();

        try
        {
            $params = [
                        'names'       => $input['features'],
                        'entity_type' => $entityType,
                        'entity_id'   => $entityId
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

    public function deleteEntityFeature($entityId, $featureName)
    {
        $this->setAdminCredentials();

        try
        {
            $response = $this->api->feature->deleteFeature($entityId, $featureName);

            $this->setApiCredentials();

            $features = $this->api->feature->getFeatures($entityId);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        if (empty($error))
        {
            $this->removeMerchantTag($entityId, $featureName);

            return [null, $features];
        }

        return [$error, null];
    }

    private function removeMerchantTag($entityId, $featureName)
    {
        $merchant = Merchant\Entity::findOrFail($entityId);

        $merchant->untag($featureName);
    }

    private function retagMerchant($entityId, $features)
    {
        $merchant = Merchant\Entity::findOrFail($entityId);

        $featureNames = $this->getFeatureNames($features['assigned_features']);

        $merchant->retag(array_merge($featureNames, $merchant->tags));
    }

    private function getFeatureNames($features)
    {
        $featureNames = array_map(function ($feature)
        {
            return $feature['name'];
        }, $features);

        return $featureNames;
    }

    public function getMerchantTags($merchantId)
    {
        $merchant = Merchant\Entity::findOrFail($merchantId);

        return [null, $merchant->tagNames()];
    }

    public function confirmUser($email)
    {
        list($error, $data) = (new User\Service)->confirmUserByEmail($email);

        return [$error, $data];
    }

    public function editIIN($iin, $input)
    {
        // Auth as admin, live mode
        $this->setApiCredentials(null);

        $this->api->IIN->edit($iin, $input);

        return [null, 'IIN Edit successful'];
    }

    /**
     * deletes an EMI Plan
     * @param  string $emiId EMI Plan Id
     * @return array
     */
    public function deleteEmi($emiId)
    {
        $this->setApiCredentials(null);
        $error = $data = [];

        try
        {
            $data = $this->api->EMI->setId($emiId)->delete($emiId);
        }
        catch (ApiError $e)
        {
            $error = $e->getMessage();
        }

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

    public function getMerchantAggregations($mode, $resource, $input)
    {
        $error = (new Admin\Validator)->validateInput('merchant_stats', $input)->messages();

        if (empty($error))
        {
            $sort = \Input::get('sort', 'total_amount');
            return [null, (new Transaction\Service)->getAllAggregations($mode, $resource, $sort)];
        }
        else
        {
            return [$error, null];
        }

    }

    public function getSingleMerchantAggregations($merchantId, $mode, $resource)
    {
        $data = [
            'merchant_id'   =>  $merchantId,
            'resource'      =>  $resource
        ];

        $response = Merchant\Entity::getAggregations($data, $mode);

        return [null, $response];
    }

    public function makeReconciliateRequest($input)
    {
        $this->setApiCredentials();

        $error = $data = null;

        try
        {
            $data = $this->api->admin->makeReconciliateRequest($input);
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

    // ----- Credits -----

    public function getMerchantCreditsLog($merchantId, $mode)
    {
        $error = $data = null;

        $this->setApiCredentials($merchantId, $mode);

        try
        {
            $data = $this->api->merchant->getMerchantCreditLogs();
        }
        catch (BadRequestError $e)
        {
            $error = [$e->getMessage()];
        }

        return [$error, $data];
    }

    public function addMerchantCredits($merchantId, $input)
    {
        $error = $data = null;

        $this->setApiCredentials(null, $input['mode']);

        unset($input['mode']);

        try
        {
            $data = $this->api->merchant->addMerchantCredits($merchantId, $input)->toArray();
        }
        catch (BadRequestError $e)
        {
            $error = [$e->getMessage()];
        }

        return [$error, $data];
    }

    public function deleteMerchantCredit($merchantId, $creditId, $input)
    {
        $error = $data = null;

        $this->setApiCredentials(null, $input['mode']);

        unset($input['mode']);

        try
        {
            $data = $this->api->merchant->deleteMerchantCredits($merchantId, $creditId);
        }
        catch (BadRequestError $e)
        {
            $error = [$e->getMessage()];
        }

        return [$error, $data];
    }


    public function getOrg($domain)
    {
        $error = $data = null;

        $this->setApiCredentials();

        try
        {
            $data = $this->api->org->fetchByDomain($domain)->toArray();

            $this->setOrgInCache($data);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
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

    /**
    * Gets Schedule list
    * Uses admin auth on the API
    *
    * @return array containing all available schedules
    */
    public function getScheduleList()
    {
        $error = $data = null;

        $this->setApiCredentials();

        try
        {
            $data = $this->api->schedule->getScheduleList();
        }
        catch (BadRequestError $e)
        {
            $error = [$e->getMessage()];
        }

        return [$error, $data];
    }

    public function getAdminData($admin)
    {
        $error = $data = null;

        $this->setApiCredentials();

        try
        {
            $orgId = $admin->org_id;

            $params = [
                'token' => $admin->token
            ];

            $data = $this->api->admin->getAdminData($orgId, $params)->toArray();
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

    /**
    * Assigns schedule to a merchant
    * Uses admin auth on the API
    *
    * @param $merchantId integer
    * @param $input input array
    * @return $data array
    */
    public function assignMerchantSchedule($merchantId, $input)
    {
        $error = $data = null;

        $this->setAdminCredentials();

        try
        {
            $data = $this->api->merchant->setSchedule($merchantId, $input)->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error = [$e->getMessage()];
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
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        if ($extension !== '.png')
        {
            return ['Invalid file format. Please upload a file with PNG extension.', $data];
        }

        // org_id/login_logo/file_name
        $keyName = "$orgId/{$type}_logo/$fileName";

        $s3Obj = [
            'Bucket'        => $_ENV['AWS_ACTIVATION_BUCKET'],
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

        $user = Auth::guard('api')->user();

        $orgId = $user->org_id;

        try
        {
            $data = $this->api->admin->logout($orgId);

            // Dashboard logout
            Auth::guard('api')->logout();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error = [$e->getMessage()];
        }

        return [$error, $data];
    }

    public function actions($merchantId, $action)
    {
        $params = ['action' => $action ];

        $this->setApiCredentials();

        list($error, $merchant) = $this->api
                                       ->merchant
                                       ->actions($merchantId, $params);

        if (empty($error) === false)
        {
            $this->trace->debug('MISC_TRACE_CODE', [
                    'error'     => "Error occured while updating merchant details on API",
                    'exception' => $error,
            ]);
        }

        return $error;
    }
}

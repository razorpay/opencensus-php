<?php

namespace App\Admin;

use App\Admin;
use App\Base;
use App\Merchant;
use App\MerchantDetails;
use App\Trace\TraceCode;
use App\Transaction;
use App\User;
use App\Session as SessionTable;
use App\Schedules;

use Auth;
use Config;
use Hash;
use Requests;
use Queue;
use Session;
use Crypt;

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
    const CANT_ARCHIVE_LIVE = 'Live merchants can not be archived.';
    const INVALID_CREDENTIALS = 'Username or password is invalid.';
    const PRIMARY_LOGIN_ERROR = "There is no user associated with this account.";
    const SELF_DELETE_ERROR = 'You can not delete yourself.';
    const PAGE_SIZE = 1000;

    // This is the Admin\Logger trait
    use Logger;

    public function __construct()
    {
        $app = \App::getFacadeRoot();
        $this->trace = $app['trace'];
    }

    public function login(array $input)
    {
        $error = (new Admin\Validator)->validateInput('login', $input)->messages();

        $verify = false;

        if (empty($error))
        {
            $verify = Auth::guard('admin')->attempt($input);

            if ($verify)
            {
                Session::put('timeout', time());
            }
        }

        $error = ($verify) ? [] : [self::INVALID_CREDENTIALS];

        return [$error, null];
    }

    public function loginWithGoogle($code, $googleService)
    {
        $error = [];
        $token = $googleService->requestAccessToken($code);

        $response = $googleService->request(Config::get('oauth-5-laravel.userinfo_url'));

        $result = json_decode($response);

        if ($result->verified_email === false)
        {
            return App::abort(404);
        }

        $admin = Admin\Entity::where('email', $result->email)->first();

        if ($admin)
        {
            $admin->access_token = $token->getAccessToken();
            $admin->google_id = $result->id;
            $admin->password = '';

            $admin->save();

            Auth::guard('admin')->loginUsingId($admin->id);
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
        $data = Merchant\Entity::join('merchant_details', 'merchants.id', '=', 'merchant_details.merchant_id')
            ->select([
                'id',
                'name',
                'email',
                'confirm_token',
                'activated',
                'steps_finished',
                'merchants.created_at',
                'merchant_details.updated_at',
                'submitted_at',
                'archived_at'
        ])->with('tagged');


        if (isset($input['tags']))
        {
            $data = $data->withAllTags($input['tags']);
        }


        if (isset($input['archived']))
        {
            $data = $data->whereNotNull('archived_at')->get();
        }
        else
        {
            $data = $data->whereNull('archived_at')->get();
        }

        // $data = Merchant\Entity::with('merchantDetails')->where('archived', '', 0)->get();

        if (reset($input) !== false)
        {
            list($key, $value) = each($input);

            switch ($key)
            {
                case "activated":
                    $response = $data->filter(function($merchant) use($value)
                    {
                        return ($merchant->activated == $value);
                    });
                    break;

                case "pending":

                    $response = $data->filter(function($merchant)
                    {
                        return ($merchant->activated == 0 and $merchant->merchantDetails->submitted == 1);
                    });
                    break;

                case "confirmed":
                    $response = $data->filter(function($merchant) use($value)
                    {
                        if($value)
                            return ($merchant->confirm_token == null);
                        else
                            return !($merchant->confirm_token == null);
                    });
                    break;

                case "dead":
                    $response = $data->filter(function($merchant) use($value)
                    {
                        if($value)
                            return ($merchant->created_at < time() - 24*7*3600 and empty($merchant->merchant_details->steps_finished));
                        else
                            return !($merchant->created_at < time() - 24*7*3600 and empty($merchant->merchant_details->steps_finished));
                    });
                    break;

                default:
                   $response = $data;
            }
        }
        else
        {
            $response = $data;
        }

        $response = $response->toArray();

        return ['count'=>count($response), 'data'=>$response];
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
            $session->id = Crypt::encrypt($session->id);
            $parser = Parser::create();
            $session->parsed_user_agent = $parser->parse($session->user_agent);
            $session->parsed_last_activity = Carbon::createFromTimeStamp(time(), "Asia/Kolkata")->format('j M Y h:i a');
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

    public function deleteAdmin($id)
    {
        $error = array();

        if ($id === Auth::guard('admin')->id())
        {
            $error[] = self::SELF_DELETE_ERROR;
        }

        $admin = Admin\Entity::findorfail($id);

        $admin->delete();

        return $error;
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

        $response['data'] = MerchantDetails\Validator::sortDataInSteps($response['data']);

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
                $file = 'ERROR';
            }
        }

        return $response;
    }

    public function fetchMerchantAndActivationDetails($id)
    {
        if ($id === null)
        {
            return [['id' => 'Merchant id cannot be null'], []];
        }

        $details = $this->fetchMerchantDetails($id);

        $activationDetails = $this->fetchMerchantActivationDetails($id);

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

        if ($id !== '10NodalAccount')
        {
            $details = $this->fetchMerchantDetails($id);

            if (isset($details['confirm_token']))
            {
                return [['Merchant not confirmed'], null];
            }
        }

        $terminal = $this->fetchMerchantTerminal($id);

        $pricingPlan = $this->fetchMerchantPricing($id);

        $data = array(
                    'details' => $details,
                    'terminals' => $terminal,
                    'pricing_plan' => $pricingPlan);

        return [[], $data];
    }

    public function fetchMerchantDetails($id)
    {
        $merchant = Merchant\Entity::findOrSoftFail($id);

        if ($merchant->confirm_token !== null)
        {
            return $merchant->toArray();
        }

        $merchantDetails = MerchantDetails\Entity::findorfail($id);

        $this->setApiCredentials();

        $data = $this->api->merchant->fetch($id)->toArray();

        $data['merchant_details'] = $merchantDetails->toArray();

        $merchant = $merchant->toArray();

        // @todo This is failing tests on wercker, fix
        // $merchant = Merchant\Entity::findorfail($id);
        // Merchant\Validator::checkAPIMatch($merchant, $response);

        $response = array(
            'archived_at'       => $merchant['archived_at'],
            'steps_finished'    => $merchantDetails['steps_finished'],
            'locked'            => $merchantDetails['locked'],
            'submitted'         => $merchantDetails['submitted'],
            'tags'              => $merchant['tags'],
            'submitted_at'      => $merchantDetails['submitted_at'],
            'activated_dashboard' => $merchant['activated'],
            'referrer'          => $merchant['referrer'],
        ) + $data;

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
            }

            $this->logMerchantEdits($id, $input);

            $data = $this->api
                        ->merchant
                        ->fetch($id)
                        ->edit($input)
                        ->toArray();

            if (isset($input['transaction_report_email']))
            {
                // Only when it is changed on API side we update on the dashboard side as well
                $error = MerchantDetails\Service::changeTransactionEmail($id, $csvEmail);
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
        ]);

        $this->setApiCredentials();

        $merchantDetails = MerchantDetails\Entity::findorfail($id);

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

            $merchantDetails->fill($merchantDetailsData);
            $merchantDetails->save();

            $this->logActionToSlack($id, Actions::BANK_DETAILS_EDITED, $input);
        }

        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return [$error, $merchantDetails->toArray()];
    }

    public function postEditMerchantComment($id, $comment)
    {
        $error = array();

        $merchantDetails = MerchantDetails\Entity::findorfail($id);

        $merchantDetails->comment = $comment;
        $merchantDetails->save();

        return array($error, $comment);
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

    public function postInitiateSetl($channel)
    {
        $data = [];
        $error = [];

        $this->setApiCredentials();

        try
        {
            $data = $this->api->settlement->initiate($channel)->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $data);
    }

    public function postAddIIN($input)
    {
        $data = [];
        $error = [];

        $this->setApiCredentials();

        try
        {
            $data = $this->api->IIN->create($input)->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $data);
    }

    public function getVerifyPayment($mode, $id)
    {
        $data = [];
        $error = [];

        $this->setApiCredentials(null, $mode);

        try
        {
            $data = $this->api->admin->fetchEntityById('payment', $id)
                                    ->verify()
                                    ->toArray();
        }
        catch (\Razorpay\Api\Errors\Error $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $data);
    }

    public function authorizeFailedPayment($mode, $id)
    {
        $data = [];
        $error = [];

        $this->setApiCredentials(null, $mode);

        try
        {
            $data = $this->api->admin->fetchEntityById('payment', $id)
                ->authorizeFailed()
                ->toArray();
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
        $error = array();

        $merchantDetails = MerchantDetails\Entity::findorfail($id);

        if ($merchantDetails->isLocked())
        {
            $error[] = 'Merchant already locked.';

            return $error;
        }

        $merchantDetails->locked = 1;
        $merchantDetails->save();

        $this->logActionToSlack($id, Actions::FORM_LOCKED);

        return $error;
    }

    public function unlockMerchant($id)
    {
        $error = array();

        $merchantDetails = MerchantDetails\Entity::findorfail($id);

        if ($merchantDetails->locked === 0)
        {
            $error[] = 'Merchant already unlocked.';
            return $error;
        }

        $merchantDetails->locked = 0;
        $merchantDetails->save();

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

    public function fetchMerchantPricing($id)
    {
        $this->setApiCredentials();

        $response = $this->api->merchant->fetch($id)->fetchPricing()->toArray();

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
     * @param  array $details
     * @return array
     */
    protected function bankAccountMap($details)
    {
        return [
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
    }

    /**
     * Activates a merchant account
     * @param  string  $id            Merchant Id
     * @param  boolean $dashboardOnly Only perform the activation on dashboard, not on API
     *                                Useful in certain contexts, when merchant is already activated
     *                                in the API, but now causing issue elsewhere
     * @return Array Empty array in case of success
     */
    public function activateMerchant($id, $dashboardOnly = false)
    {
        $merchant = Merchant\Entity::findorfail($id);

        $details = $this->fetchMerchantDetails($id);

        if ((int)$details['submitted'] === 0)
        {
            return array('Activation form has not been submitted by merchant yet.');
        }

        // Double equals because its probably a string
        if ($dashboardOnly == true)
        {
            return $this->activateMerchantOnDashboard($merchant);
        }


        $this->setApiCredentials();

        $bankAccount = $this->bankAccountMap($details['merchant_details']);

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
            // Only if the merchant doesn't have the Bank Account associated
            // Do we add a bank account
            if ($bankAccountApi === false)
            {
                $this->api->merchant->fetch($id)->setBankAccount($bankAccount);
            }

            $this->api->merchant->fetch($id)->activate();

            // Log activation on marketing google spreadsheet
            $zapierData = $this->activationZapierData($details);
            Queue::push('App\Admin\Service@postActivationToZapier', $zapierData);

            $this->logActionToSlack($merchant, Actions::ACTIVATED);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            return array($e->getMessage());
        }

        return $this->activateMerchantOnDashboard($merchant);
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

        $date =  Carbon::createFromTimeStamp(time(), "Asia/Kolkata")
            ->format('j/m/Y');

        $merchantDetails = $merchant['merchant_details'];

        return [
            'date'  =>  $date,
            'id'    =>  $merchant['id'],
            'email' =>  $merchant['email'],
            'name'          =>  $merchant['name'],
            'contact_name'  =>  $merchantDetails['contact_name'],
            'business_name' =>  $merchantDetails['business_name'],
            'business_dba'  =>  $merchantDetails['business_dba'],
            'business_website'  =>  $merchantDetails['business_website'],
            'ref'   =>  $merchant['referrer'],
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
        $error = array();

        $merchant = Merchant\Entity::findorfail($id);

        if ((int)$merchant->activated === 0 or $merchant->archived_at !== null)
        {
            return array(
                'Merchant must be active & unarchived before enabling/disabling live transactions.');
        }

        $this->setApiCredentials();

        try
        {
            $this->api->merchant->fetch($id)->enable();
            $this->logActionToSlack($merchant, Actions::LIVE_ENABLED);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            return array($e->getMessage());
        }

        return array();
    }

    public function archiveMerchant($id)
    {
        $error = [];
        $merchant = Merchant\Entity::findOrSoftFail($id);

        if ($merchant->archived_at !== null)
        {
            $error = [self::ALREADY_ARCHIVED];
        }

        $this->setApiCredentials();

        try
        {
            $data = $this->api->merchant->fetch($id);

            // This is a hard fail and we return
            // immediately
            if ($data->live === true)
            {
                return [self::CANT_ARCHIVE_LIVE];
            }
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            // We just ignore this for now
            $error =[$e->getMessage()];
        }
        finally
        {
            $this->logActionToSlack($merchant, Actions::ARCHIVED);
            $merchant->archive();

            // Return empty array in case of success
            return [];
        }
    }

    public function unarchiveMerchant($id)
    {
        $error = array();

        $merchant = Merchant\Entity::findorfail($id);

        if($merchant->archived_at === null)
        {
            return array("Merchant not archived.");
        }

        $this->logActionToSlack($merchant, Actions::UNARCHIVED);

        $merchant->archived_at = null;
        $merchant->save();

        return array();
    }

    public function liveDisableMerchant($id)
    {
        $error = array();

        $merchant = Merchant\Entity::findorfail($id);

        if ((int)$merchant->activated === 0)
        {
            return array('Merchant must be active before enabling/disabling live transactions.');
        }

        $this->setApiCredentials();

        try
        {
            $this->api->merchant->fetch($id)->disable();
            $this->logActionToSlack($merchant, Actions::LIVE_DISABLED);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            return array($e->getMessage());
        }

        return array();
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

        $merchant = Merchant\Entity::findorfail($id);

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

    public function fetchPricingPlans()
    {
        $errors = array();

        $response = array();

        $this->setApiCredentials();

        try
        {
            $response = $this->api->pricing->merchants()->toArray();

            $response = $response['items'];
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $errors[] = $e->getMessage();
        }

        return array($errors, $response);
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

    public function addPricingPlanRule($id, $input)
    {
        $error = array();

        $response = array();

        $this->setApiCredentials();

        try
        {
            $response = $this->api->pricing->fetch($id)->createRule($input)->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $response);
    }

    public function deletePricingPlanRule($planId, $ruleId)
    {
        $error = array();

        $response = array();

        $this->setApiCredentials();

        try
        {
            $response = $this->api->pricing
                ->deleteRule($planId, $ruleId)
                ->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $response);
    }

    public function createPricingPlan($input)
    {
        $error = array();

        $response = array();

        $this->setApiCredentials();

        try
        {
            $response = $this->api->pricing->create($input)->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array($error, $response);
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
     * Sends a redirect the the file
     */
    public function getBeneficiaryFile($input)
    {
        $error = (new Validator)->validateInput('get_beneficiary', $input)->messages();

        if (empty($error))
        {
            $date = \Input::get('date', date('Y-m-d'));
            return [null, $this->getBeneficiaryFileUrl($date)];
        }

        return [$error, null];
    }

    /**
     * Returns a pre-authed S3 URL to download beneficiary file
     * @param  Date $date date in Y-m-d format (with leading zeroes)
     * @return String URL
     */
    protected function getBeneficiaryFileUrl($date)
    {
        $s3 = $this->getS3Client();

        $beneficiaryBucket = Config::get('aws::config.buckets')['beneficiary'];
        $filename = $date.'.xls';

        return $s3->getObjectUrl($beneficiaryBucket, $filename, '+2 minutes', [
            'https'     => true
        ]);
    }

    public function generateBeneficiaryFile()
    {
        $this->setApiCredentials();
        try
        {
            $response = $this->api->merchant->generateBeneficiaryFile();
        }

        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error[] = $e->getMessage();
        }

        return array();
    }

    /**
     * Fires off a queue worker to start capturing screenshots
     * @param  string $id merchant id
     */
    public function captureScreenshot($id)
    {
        $merchant =  MerchantDetails\Entity::findorfail($id);
        $urls = $merchant->getUrls();
        $name = $merchant->business_name;

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
        $keys = MerchantDetails\Entity::getUrlKeys();
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
                    $creevey->compressAndSave($key, $localFilePath,
                        $input[$key]->getClientOriginalName());
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

        $bucket = $_ENV['AWS_ACTIVATION_BUCKET'];
        $keys = MerchantDetails\Entity::getUrlKeys();

        $links = [];

        foreach ($keys as $key)
        {
            $filename = "$id/screenshots/$key.jpg";
            $links[$key] = $s3->getObjectUrl($bucket, $filename,
                '+10 minutes', [
                    'https'     => true
            ]);
        }

        return $links;
    }

    public function sendTestNewsletter($input)
    {
        $this->setApiCredentials();

        try
        {
            $input['email'] = Auth::guard('admin')->get()->email;

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

    public function triggerError()
    {
        $this->setApiCredentials();
        try
        {
            $errorMsg = $this->api->admin->triggerError();
            if($errorMsg)
            {
                return [null, $errorMsg];
            }
            else
            {
                return ['Error not triggered', null];
            }
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

    public function verifyAllPayments()
    {
        $this->setApiCredentials(null, 'live');
        try
        {
            $response = $this->api->payment->verifyAll();
            return [null, $response->toArray()];
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            return [[$e->getMessage()], null];
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

    public function generateNetBankingRefunds($input)
    {
        $this->setApiCredentials(null, $input['mode']);
        unset($input['mode']);

        try
        {
            $response = $this->api->refund->generateNetBankingExcel($input);
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
            $params = array('names'             => explode(",", $input['features']),
                            'entity_type'       => $entityType,
                            'entity_id'         => $entityId);

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
        $this->setApiCredentials();

        try
        {
            $response = $this->api->feature->deleteFeature($entityId, $featureName);

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

    public function confirmMerchant($merchantId)
    {
        (new Merchant\Service)->confirmMerchantById($merchantId);

        $this->logActionToSlack($merchantId, Actions::CONFIRMED);

        return [null, 'Merchant Confirmed'];
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

    public function addEMI($input)
    {
        // EMI Plans are modeless so we don't care about live or test
        $this->setApiCredentials(null);
        $error = $data = [];

        try
        {
            $data = $this->api->EMI->create($input);
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

    public function fetchPaymentNetworks()
    {
        $this->setApiCredentials(null, 'live');

        $data = $this->api->pricing->fetchPaymentNetworks();

        return $data;
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
        $dateFrom = Carbon::parse($input['date'])->timestamp;

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

    public function assignMerchantSchedule($merchantId, $input)
    {
        $error = $data = null;

        $this->setApiCredentials();

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

    public function createSchedule($input)
    {
        $error = $data = null;

        $this->setApiCredentials();

        try
        {
            $data = $this->api->schedule->createSchedule($input);
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error = [$e->getMessage()];
        }

        return [$error, $data];
    }

}

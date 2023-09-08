<?php

namespace App\Merchant;

use Auth;
use Hash;
use Queue;
use Session;
use Request;
use Carbon\Carbon;
use App\Http\ApiUrl;
use GuzzleHttp\Client as Guzzle;
use Razorpay\Api\Errors\ErrorCode;
use Razorpay\Api\Errors\ServerError;
use App\Admin\Service as AdminService;
use Razorpay\Api\Errors\BadRequestError;

use App\Base;
use App\User;
use App\Razorx;
use App\Merchant;
use App\MerchantDetails;
use App\Trace\TraceCode;
use App\Admin\ApiRequestAny;
use App\Session\Entity as AppSession;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\PromiseInterface;

class Service extends Base\Service
{
    protected $trace;

    protected $app;

    const UPLOAD_KEYS = [
        'business_proof'           => 'business_proof_url',
        'business_operation_proof' => 'business_operation_proof_url',
        'business_pan_proof'       => 'business_pan_url',
        'address_proof'            => 'address_proof_url',
        'promoter_proof'           => 'promoter_proof_url',
        'promoter_pan_proof'       => 'promoter_pan_url',
        'promoter_address_proof'   => 'promoter_address_url'
    ];

    public function __construct()
    {
        $this->currentUser = Auth::user();

        $app = \App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];
    }

    /**
     * Registers a sub-merchant account and associates it both ways:
     * 1. Marks the original user as the referral
     * 2. Adds the original user to the new merchant's team
     *
     * This is one scenario where we don't use currentMerchant
     *
     * @param  array  $input
     * @return array
     */
    public function registerSubMerchant(array $input)
    {
        $isLinkedAccount = (bool) ($input['account'] ?? false);

        /*
         * Mode has to be passed in case of creating submerchant via marketplace
         * This is because the route has a feature check on api.
         * Without passing mode, the request is in live mode by default and the route fails
         * as the feature will not be enabled in live mode initially.
         */
        $mode = $input['mode'] ?? null;

        unset($input['mode']);

        $data = array_merge([
            'user_id' => $this->currentUser->id
        ], $input);

        $request = new ApiRequestAny([
            'mode'        => $mode,
            'client_type' => 'merchant',
        ]);

        list($error, $data) = $request->processInput($data)->send('submerchants', 'POST');

        if (($isLinkedAccount === false) and (empty($error) === true))
        {
            list($error, $genericUser) = (new User\Service)->getUserFromApi($this->currentUser->id);

            if (empty($error) === true)
            {
                Session::put('dashboard_user_payload', $genericUser);
            }
        }

        return [$error, $data];
    }

    public function resendConfirmation()
    {
        $request = new ApiRequestAny();

        list($error, $data) = $request->send('users/resend-verification', 'POST');

        if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        if (empty($data['confirm']) === false)
        {
            return [['User already confirmed. You can login ' .
                    '<a href="'.\URL::to('#/access/signin').'">here</a>'], null];
        }

        return [$error, $data];
    }

    public function fetchMerchantFromApi($merchantId)
    {
        $error = $response = null;

        $adminUser = Auth::guard('api')->user();

        if (empty($adminUser) === false)
        {
            $this->setAdminCredentials();

            $response = $this->api
                             ->merchant
                             ->fetch($merchantId)
                             ->toArray();
        }
        else
        {
            $currentMerchant = $this->currentUser->currentMerchant();

            if (empty($currentMerchant) === false)
            {
                $response = $currentMerchant->toArray();
            }
        }


        if (empty($response) === false)
        {
            $response =  [
                'id'           => $response['id'],
                'name'         => $response['name'],
                'email'        => $response['email'],
                'activated'    => (int) $response['activated'],
                'created_at'   => $response['created_at'],
                'updated_at'   => $response['updated_at'],
                'archived_at'  => $response['archived_at'],
                'suspended_at' => $response['suspended_at'],
            ];
        }

        return [$error, $response];
    }
    /*
     * This calls the ezetap void API and based on success response authorized_refund is created in rzp via webhook
     * */
    public function ezetapVoidApi($input): array
    {
        $error = (new Merchant\Validator)->validateInput('ezetap_void', $input)->messages();

        $this->trace->info(TraceCode::EZETAP_VOID_ACTION, [
            Constants::TXN_ID => $input[Constants::TXN_ID],
            Constants::AMOUNT => $input[Constants::AMOUNT]
        ]);

        if (empty($error) === false)
        {
            return [$error, null];
        }

        $base_url = $this->getRazorpayPosBaseUrl();

        return $this->requestRazorpayPos($base_url . '/api/3.0/payment/void', $input);
    }

    /*
     * This calls the ezetap refund API and based on success response refund is called in rzp API via dashboard
     * */
    public function ezetapRefundApi($input): array
    {
        $error = (new Merchant\Validator)->validateInput('ezetap_refund', $input)->messages();

        $this->trace->info(TraceCode::EZETAP_REFUND_ACTION, [
            Constants::EXTERNAL_REF_NUMBER => $input[Constants::EXTERNAL_REF_NUMBER],
            Constants::AMOUNT              => $input[Constants::AMOUNT]
        ]);

        if (empty($error) === false)
        {
            return [$error, null];
        }

        $base_url = $this->getRazorpayPosBaseUrl();

        return $this->requestRazorpayPos($base_url . '/api/2.0/payment/refund', $input);
    }

    public function storeAppKeys($merchantId, $input)
    {
        $CacheIdForStoringKeys = 'ezetap_username_and_appkey_' . $merchantId;

        $dataToCache = [
            Constants::USERNAME => $input[Constants::USERNAME] ?? null,
            Constants::APP_KEY  => $input[Constants::APP_KEY] ?? null
        ];

        $this->app['cache']->put($CacheIdForStoringKeys, $dataToCache);

        $this->trace->info(TraceCode::EZETAP_SET_APP_KEY, [
            Constants::USERNAME => $dataToCache[Constants::USERNAME]
        ]);

        $errors = null;

        $data = $this->app['cache']->get($CacheIdForStoringKeys);

        return [$errors, $data];
    }

    public function fetchAppKeys($merchantId)
    {
        $CacheIdForFetchingKeys = 'ezetap_username_and_appkey_' . $merchantId;

        $errors = null;

        $dataFromCache = $this->app['cache']->get($CacheIdForFetchingKeys);

        $data = [
            Constants::USERNAME => $dataFromCache[Constants::USERNAME] ?? null,
            Constants::APP_KEY  => $dataFromCache[Constants::APP_KEY] ?? null
        ];

        $this->trace->info(TraceCode::EZETAP_FETCH_APP_KEY, [
            Constants::USERNAME => $data[Constants::USERNAME]
        ]);

        return [$errors, $data];
    }

    public function fetchKeysFromApi($merchantId, $mode)
    {
        $options = [
            'mode'        => $mode,
            'client_type' => 'merchant',
        ];

        $request = new ApiRequestAny($options);

        list($error, $data) = $request->send('keys', 'GET');

        if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    public function fetchInvoices($mode)
    {
        $options = [
            'mode'        => $mode,
            'client_type' => 'merchant',
        ];

        $request = new ApiRequestAny($options);

        list($error, $data) = $request->send('invoices', 'GET');

        if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    public function createKey($merchantId, $mode)
    {
        $options = [
            'mode'        => $mode,
            'client_type' => 'merchant',
        ];

        $request = new ApiRequestAny($options);

        list($error, $data) = $request->send('keys', 'POST');

        if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    public function getInvoices($mode)
    {
        $merchantId = $this->currentUser->currentMerchant()->id;

        $this->setApiCredentials($merchantId, $mode);

        $errors = $data = null;

        try
        {
            $data = $this->api->invoice->all()->toArray();
        }
        catch(BadRequestError $e)
        {
            $errors[] = $e->getMessage();
        }

        return [$errors, $data];
    }

    public function createInvoice($mode, $input)
    {
        $options = [
            'mode'        => $mode,
            'client_type' => 'merchant',
        ];

        $request = new ApiRequestAny($options);

        list($error, $data) = $request->processInput($input)->send('invoices', 'POST');

        if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    public function saveActivationData($input)
    {
        if (isset($input['bank_account_number_confirmation']) === true)
        {
            unset($input['bank_account_number_confirmation']);
        }

        $request = new ApiRequestAny(['client_type' => 'merchant']);

        list($error, $data) = $request->processInput($input)->send('merchant/activation', 'POST');

        if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    public function saveActivationFilesData($input)
    {
        if ((count($input) !== 1) or
            (in_array(key($input), array_keys(self::UPLOAD_KEYS)) === false))
        {
            throw new BadRequestError(
                'Invalid parameters.',
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        $options = [
            'client_type'      => 'merchant',
            'custom_file_keys' => true,
        ];

        $request = new ApiRequestAny($options);

        list($error, $data) = $request->send('merchant/activation/upload', 'POST');

        if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    public function sendInvoiceNotification($mode, $invoiceId, $medium)
    {
        $errors = $data = [];

        $merchantId = $this->currentUser->currentMerchant()->id;

        // Fetches keyId from api for given merchant
        list($errors, $data) = $this->fetchKeysFromApi($merchantId, $mode);

        if ($errors)
        {
            return [$errors, $data];
        }

        if ($data['count'] === 0)
        {

            return [
                ['No keyId found for given merchant with id: ' . $merchantId],
                $data
            ];
        }

        $keyId = $data['items'][0]['id'];

        $this->setApiCredentialsForPublicAuth($keyId);

        try
        {
            $data = $this->api->invoice->sendNotification($invoiceId, $medium)->toArray();
        }
        catch (BadRequestError $e)
        {
            $errors[] = $e->getMessage();
        }

        return [$errors, $data];
    }

    public function savePreSignupDetails($input)
    {
        $request = new ApiRequestAny(['client_type' => 'merchant']);

        list($error, $data) = $request->processInput($input)->send('pre_signup', 'PUT');

        if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    public function whatsappOptIn($input)
    {
        $request = new ApiRequestAny(['client_type' => 'merchant']);

        return $request->processInput($input)->send('users/whatsapp/opt_in', 'POST');
    }

    public function getReferrerAttribute($merchantId)
    {
        $tags = $this->getMerchantTags($merchantId);

        foreach ($tags as $tag)
        {
            $tag = strtolower($tag);

            if (substr($tag, 0, 4) === 'ref-')
            {
                return substr($tag, 4);
            }
        }

        return null;
    }

    /**
     * returns the presignup data for a merchant
     * if the merchant is referred (submerchant)
     * then returns an empty array
     */
    public function getPreSignupDetails($merchantId): array
    {
        $data = [];

        $referrer = $this->getReferrerAttribute($merchantId);

        if (($referrer === null) or
            (Merchant\Entity::verifyUniqueId($referrer) === 0))
        {
            $data = (new MerchantDetails\Service)->getPresignupDetails($merchantId);
        }

        $this->trace->info(
            TraceCode::MISC_TRACE_CODE,
            ["action" => "GET_REFERRER_ATTRIBUTE",
                "data" => [
                    "referrer" => $referrer
                ]
            ]
        );

        return $data;
    }

    /**
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    public function fetchPartnerConfigsAsyncPromise(Guzzle $guzzleClient): PromiseInterface
    {
        $request = new ApiRequestAny(['client_type' => 'merchant', 'guzzle_client' => $guzzleClient]);

        return $request->sendAsyncPromise('merchants/me/partner/configs', 'GET');
    }

    public function fetchPartnerConfigs()
    {
        $startTime = microtime(true) * 1000;

        $this->trace->info(TraceCode::GET_PARTNER_CONFIGS_ROUTE_INFO, [
            'action'                => 'FetchStarted',
            'start_time'            => $startTime
        ]);

        $request = new ApiRequestAny(['client_type' => 'merchant']);

        list($error, $data) = $request->send('merchants/me/partner/configs', 'GET');

        if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        $endTime  = microtime(true) * 1000;
        $duration = round($endTime - $startTime);

        $this->trace->info(TraceCode::GET_PARTNER_CONFIGS_ROUTE_INFO, [
            'action'              => 'FetchEnded',
            'end_time'            => $endTime,
            'duration'            => $duration,
            'controller'          => app('request')->route()->getAction()['controller']
        ]);

        return $data['items'] ?? [];
    }

    /**
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    public function fetchPartnerActivationStatusAsyncPromise(): PromiseInterface
    {
        $request = new ApiRequestAny(['client_type' => 'merchant']);

        return $request->sendAsyncPromise('partner/activation', 'GET');
    }

    public function fetchPartnerActivationStatus()
    {
        $startTime = microtime(true) * 1000;

        $this->trace->info(TraceCode::GET_PARTNER_ACTIVATION_ROUTE_INFO, [
            'action'                => 'FetchStarted',
            'start_time'            => $startTime
        ]);

        $request = new ApiRequestAny(['client_type' => 'merchant']);

        list($error, $data) = $request->send('partner/activation', 'GET');

        if (empty($error) === false)
        {
            $this->trace->error(
                TraceCode::GET_PARTNER_ACTIVATION_ROUTE_ERROR,
                [
                    "exception" => $error
                ]
            );
            $this->app['metrics']->count(Constants::FETCH_PARTNER_ACTIVATION_FAILED, 1, [ "exception" => $error[0] ]);
        }

        $endTime  = microtime(true) * 1000;
        $duration = round($endTime - $startTime);

        $this->trace->info(TraceCode::GET_PARTNER_ACTIVATION_ROUTE_INFO, [
            'action'              => 'FetchEnded',
            'end_time'            => $endTime,
            'duration'            => $duration,
            'controller'          => app('request')->route()->getAction()['controller']
        ]);

        return ($data && $data['partner_activation']) ? $data ['partner_activation']['activation_status']: '';
    }

    public function getMerchantUsers($merchantId)
    {
        $adminUser = Auth::guard('api')->user();

        if (empty($adminUser) === false)
        {
            $request = new ApiRequestAny([
                'client_type' => 'admin',
                'mode'        => "live_$merchantId"
            ]);
        }
        else
        {
            $request = new ApiRequestAny();
        }

        list($error, $data) = $request->send("merchants-users", 'GET');

        if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return $data;
    }

    public function getTreatment($featureFlag)
    {
        $startTime = microtime(true) * 1000;

        $this->trace->info(TraceCode::GET_RAZORX_EXPERIMENTS_ROUTE_INFO, [
            'action'                => 'FetchStarted',
            'start_time'            => $startTime
        ]);

        $request = new ApiRequestAny(['client_type' => 'merchant']);

        list($error, $data) = $request->send("razorx/evaluate/$featureFlag", 'GET');

        if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        $endTime  = microtime(true) * 1000;
        $duration = round($endTime - $startTime);

        $this->trace->info(TraceCode::GET_RAZORX_EXPERIMENTS_ROUTE_INFO, [
            'action'              => 'FetchEnded',
            'end_time'            => $endTime,
            'duration'            => $duration,
            'controller'          => app('request')->route()->getAction()['controller']
        ]);

        return $data;
    }

    /**
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    public function getPartnerIntentAsyncPromise(Guzzle $guzzleClient): PromiseInterface
    {
        $request = new ApiRequestAny(['client_type'    => 'merchant', 'guzzle_client' => $guzzleClient]);

        return $request->sendAsyncPromise('merchant/partner-intent', 'GET');
    }

    public function getPartnerIntent()
    {
        $startTime = microtime(true) * 1000;

        $this->trace->info(TraceCode::GET_PARTNER_INTENT_ROUTE_INFO, [
            'action'                => 'FetchStarted',
            'start_time'            => $startTime
        ]);

        $request = new ApiRequestAny(['client_type'    => 'merchant']);

        list($error, $data) = $request->send('merchant/partner-intent', 'GET');

        if(empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        $endTime  = microtime(true) * 1000;
        $duration = round($endTime - $startTime);

        $this->trace->info(TraceCode::GET_PARTNER_INTENT_ROUTE_INFO, [
            'action'              => 'FetchEnded',
            'end_time'            => $endTime,
            'duration'            => $duration,
            'controller'          => app('request')->route()->getAction()['controller']
        ]);

        return $data['partner_intent'] ?? null;
    }

    public function getMerchantTags($merchantId)
    {
        $startTime = microtime(true) * 1000;

        $this->trace->info(TraceCode::GET_TAGS_ROUTE_INFO, [
            'action'                => 'FetchStarted',
            'start_time'            => $startTime
        ]);

        $adminUser = Auth::guard('api')->user();

        if (empty($adminUser) === false)
        {
            $request = new ApiRequestAny(['client_type' => 'admin', 'mode' => "live_$merchantId"]);
        }
        else
        {
            $request = new ApiRequestAny(['client_type' => 'merchant']);
        }

        list($error, $data) = $request->send("merchants/$merchantId/tags", 'GET');

        if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        $endTime  = microtime(true) * 1000;
        $duration = round($endTime - $startTime);

        $this->trace->info(TraceCode::GET_TAGS_ROUTE_INFO, [
            'action'              => 'FetchEnded',
            'end_time'            => $endTime,
            'duration'            => $duration,
            'controller'          => app('request')->route()->getAction()['controller']
        ]);

        return $data;
    }

    /**
     * It returns the merchant feature names.
     *
     * @param $merchantId
     *
     * @return array Merchant features
     * @throws BadRequestError
     */
    public function getMerchantFeatures(): array
    {
        $startTime = microtime(true) * 1000;

        $this->trace->info(TraceCode::GET_FEATURES_ROUTE_INFO, [
            'action'                => 'FetchStarted',
            'start_time'            => $startTime
        ]);

        $request = new ApiRequestAny(['client_type' => 'merchant']);

        list($error, $data) = $request->send("merchants/me/features", 'GET');

        if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        $features = $data['features'];

        $validFeatures = array_filter($features, function($feature) {
            return ($feature['value'] === true);
        });

        $featureNames = array_map(function($val) {
            return $val['feature'];
        }, array_values($validFeatures));

        $endTime  = microtime(true) * 1000;
        $duration = round($endTime - $startTime);

        $this->trace->info(TraceCode::GET_FEATURES_ROUTE_INFO, [
            'action'              => 'FetchEnded',
            'end_time'            => $endTime,
            'duration'            => $duration,
            'controller'          => app('request')->route()->getAction()['controller']
        ]);

        return $featureNames;
    }

    /**
     * It returns data
     * @return bool
     * @throws ServerError
     */
    public function getShowTncPopup() : bool
    {
        if((new AdminService())->isAdminLoggedIn() === true)
        {
           return false;
        }

        $startTime = microtime(true) * 1000;

        $this->trace->info(TraceCode::GET_TNC_POPUP_INFO, [
            'action'                => 'FetchStarted',
            'start_time'            => $startTime
        ]);

        $domain = \Request::server('SERVER_NAME');
        list($error, $org) = (new AdminService)->getOrg($domain);

        if(empty($error) === false)
        {
            throw new ServerError(
                $error[0],
                ErrorCode::SERVER_ERROR,
                500
            );
        }

        //show_tnc_popup will be false for rzp org
        $customCode = $org['custom_code']?? null;

        if($customCode === 'rzp')
        {
            return false;
        }

        $request = new ApiRequestAny(['client_type' => 'merchant']);

        list($error, $data) = $request->send("merchant/tnc_popup_status", 'GET');

        if(empty($error) === false)
        {
            throw new ServerError(
                $error[0],
                ErrorCode::SERVER_ERROR,
                500
            );
        }

        $endTime  = microtime(true) * 1000;
        $duration = round($endTime - $startTime);

        $this->trace->info(TraceCode::GET_TNC_POPUP_INFO, [
            'action'              => 'FetchEnded',
            'end_time'            => $endTime,
            'duration'            => $duration,
            'data'                => $data,
        ]);

        return $data['show_tnc_popup'];
    }

    public function getMerchantActiveCampaigns(): array
    {
        $startTime = microtime(true) * 1000;

        $this->trace->info(TraceCode::GET_CAMPAIGNS_ROUTE_INFO, [
            'action'                => 'FetchStarted',
            'start_time'            => $startTime
        ]);

        $request = new ApiRequestAny(
            [
                'client_type'   => 'merchant',
                'process_input' => false,
            ]);

        list($error, $data) = $request->send("credits?fetch_expired=0&is_promotion=1", 'GET');

        if (empty($error) === false)
        {

            $this->trace->warning(TraceCode::GET_CAMPAIGNS_ROUTE_INFO, [
                'action'            => 'FetchFailed',
                'error_description' => $error[0],
                'controller'        => app('request')->route()->getAction()['controller']
            ]);

            return [];
        }

        $campaigns = array_map(function($val) {
            return $val['campaign'];
        }, array_values($data['items']));

        $endTime  = microtime(true) * 1000;
        $duration = round($endTime - $startTime);

        $this->trace->info(TraceCode::GET_CAMPAIGNS_ROUTE_INFO, [
            'action'              => 'FetchEnded',
            'end_time'            => $endTime,
            'duration'            => $duration,
            'controller'          => app('request')->route()->getAction()['controller']

        ]);

        return $campaigns;
    }

    public function addMerchantTagsOnAPI($merchantId, $tags)
    {
        $body = [
            'tags' => $tags
        ];

        $request = new ApiRequestAny(['client_type' => 'admin']);

        list($error, $data) = $request->processInput($body)->send("merchants/$merchantId/tags", 'POST');
    }

    public function getCurrentMerchantId()
    {
        $currentMerchant = Auth::user()->currentMerchant();

        if (empty($currentMerchant) === true)
        {
            // This case will happen only if the user has zero merchants and tried to access the merchant route.
            throw new BadRequestError(
                'Merchant not found for the user',
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return $currentMerchant->id;
    }

    public function removeUser(string $mode, string $userId)
    {
        $request = new ApiRequestAny(['mode' => $mode, 'client_type' => 'merchant']);

        $method = Request::method();

        $path = 'users/' . $userId . '/detach';

        list($error, $data) = $request->send($path, $method);

        if (empty($error) === true)
        {
            (new AppSession)->deleteSessionsForUser($userId);
        }

        return [$error, $data];
    }

    public function validateCouponCode($input)
    {
        $request = new ApiRequestAny(['client_type' => 'merchant']);

        list($error, $data) = $request->processInput($input)->send('coupons/validate', 'POST');

        if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    public function getPayoutCount($mode)
    {
        $startTime = microtime(true) * 1000;

        $this->trace->info(TraceCode::GET_PAYOUT_ROUTE_INFO, [
            'action'                => 'FetchStarted',
            'start_time'            => $startTime
        ]);

        $request = new ApiRequestAny(['client_type' => 'merchant', 'mode' => $mode]);

        list($error, $data) = $request->send("payouts?count=1&product=banking", 'GET');

        if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        $endTime  = microtime(true) * 1000;
        $duration = round($endTime - $startTime);

        $this->trace->info(TraceCode::GET_PAYOUT_ROUTE_INFO, [
            'action'              => 'FetchEnded',
            'end_time'            => $endTime,
            'duration'            => $duration,
            'controller'          => app('request')->route()->getAction()['controller']

        ]);

        return $data['count'];
    }

    public function getExperiments($razorxCachingEnabled = false, $merchantId = '')
    {
        $response = [];

        $razorxService = (new Razorx\Service());

        $response = $razorxService->updateExperiments($response, $razorxCachingEnabled, $merchantId);

        $experiments = $response['experiments'] ?? [];

        return $experiments;
    }
    
    public function processPartnerIntentPromiseResponse($apiPartnerIntentPromise)
    {
        list($error, $data) = $apiPartnerIntentPromise->processAsyncPromiseResponse();
        
        if(empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }
        
        return $data['partner_intent'] ?? null;
    }
    
    public function processPartnerConfigPromiseResponse($apiPartnerConfigPromise)
    {
        list($error, $data)  = $apiPartnerConfigPromise->processAsyncPromiseResponse();
    
        if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }
    
        return $data['items'] ?? [];
        
    }
    
    public function processPartnerActivationStatusPromiseResponse($apiPartnerActivationPromise)
    {
        list($error, $data)  = $apiPartnerActivationPromise->processAsyncPromiseResponse();
    
        if (empty($error) === false)
        {
            $this->trace->error(
                TraceCode::GET_PARTNER_ACTIVATION_ROUTE_ERROR,
                [
                    "exception" => $error
                ]
            );
            $this->app['metrics']->count(Constants::FETCH_PARTNER_ACTIVATION_FAILED, 1, [ "exception" => $error[0] ]);
        }

        return ($data && $data['partner_activation']) ? $data ['partner_activation']['activation_status'] : '';
    }
    
    public function processExperimentPromiseResponse($apiExperimentPromise)
    {
        $razorxService = (new Razorx\Service());
        
        $experimentsResults =  $razorxService->processBulkTreatmentPromiseResponse($apiExperimentPromise);
    
        foreach ($experimentsResults as $result => $val)
        {
            $data['experiments'][$result] = $val;
        }
    
        return  $data['experiments'] ?? [];
        
    }

    /**
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    public function getExperimentPromise(Guzzle $guzzleClient): PromiseInterface
    {
        $razorxService = (new Razorx\Service());

        return $razorxService->getBulkTreatmentPromise(Razorx\Constants::FEATURE_FLAGS, $guzzleClient);
    }

    public function getBusinessTypes(){

        $request = new ApiRequestAny(['client_type' => 'merchant']);

        list($error, $data) = $request->processInput()->send('merchant/onboarding/business_types', 'GET');

        if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return [$error, $data];
    }

    public function getMerchantNavigationList()
    {
        $request = new ApiRequestAny(['client_type' => 'merchant']);

        list($error, $data) = $request->processInput()->send('care_service/merchant/twirp/rzp.care.merchantNavigation.v1.MerchantNavigationService/GetMerchantNavigationList', 'GET');

        if (empty($error) === false)
        {
            throw new BadRequestError(
                $error[0],
                ErrorCode::BAD_REQUEST_ERROR,
                400
            );
        }

        return $data;
    }

    protected function getRazorpayPosBaseUrl()
    {
        return $this->app['config']->get('app.ezetap_base_url');
    }

    /**
     * @param array $options
     * @param       $input
     *
     * @return array
     */
    protected function requestRazorpayPos($endPoint, $input): array
    {
        //convert the razorpay paise amount to rupees in ezetap for consistency
        if (isset($input[Constants::AMOUNT]) === true)
        {
            $input[Constants::AMOUNT] = (double) $input[Constants::AMOUNT] / 100.0;
        }

        try
        {
            $client = new Guzzle();

            $response   = $client->post($endPoint, [
                'headers' => Constants::HEADERS,
                'json'    => $input,
                'timeout' => 20,
            ]);
            $statusCode = $response->getStatusCode();

            $body = json_decode($response->getBody(), true);

            // Process the response based on the HTTP status code
            if (($statusCode === 200)
                and ($body[Constants::SUCCESS] === true))
            {
                // Successful response
                return [null, [Constants::SUCCESS => true]];
            }

            $this->trace->info(TraceCode::EZETAP_API_RESPONSE, [
                'response'        => $body
            ]);

            return [['Internal error occurred'], null];
        }
        catch (\Exception $e)
        {
            // Handle any exceptions that occurred during the request
            return [[$e->getMessage()], null];
        }
        catch (GuzzleException $e)
        {
            return [[$e->getMessage()], null];
        }
    }
}

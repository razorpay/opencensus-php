<?php

namespace App\Merchant;

use Auth;
use Hash;
use Queue;
use Session;
use Request;
use Carbon\Carbon;
use App\Http\ApiUrl;
use Razorpay\Api\Errors\ErrorCode;
use Razorpay\Api\Errors\BadRequestError;

use App\Base;
use App\User;
use App\Razorx;
use App\Merchant;
use App\MerchantDetails;
use App\Trace\TraceCode;
use App\Admin\ApiRequestAny;
use App\Session\Entity as AppSession;

class Service extends Base\Service
{
    protected $trace;

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

    public function getExperiments()
    {
        $response = [];

        $razorxService = (new Razorx\Service());

        $response = $razorxService->updateExperiments($response);

        $experiments = $response['experiments'] ?? [];

        return $experiments;
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

}

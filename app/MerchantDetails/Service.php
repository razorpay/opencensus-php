<?php

namespace App\MerchantDetails;

use App\Http\ApiUrl;
use App\Trace\TraceCode;
use Auth;
use Mail;
use Queue;
use Trace;
use Config;
use Session;
use Aws\Sdk;
use Requests;
use App\Base;
use App\Merchant;
use Carbon\Carbon;
use App\Mailers\MerchantMailer;
use Aws\Laravel\AwsFacade as AWS;
use Illuminate\Support\Facades\App as App;

class Service extends Base\Service
{
    protected $trace;

    protected $app;

    const PRE_SIGNUP_FIELDS = [
        'business_type',
        'transaction_volume',
        'contact_name',
        'business_name',
        'contact_mobile',
    ];

    // for Registered Onboarding flow.
    // note: with mobile based signups going live,
    // contact_mobile is no longer considered for presignup completeness check
    const NOT_REGISTERED_BUSINESS_PRE_SIGNUP_FIELDS = [
      'contact_name'
    ];

    const PRE_SIGNUP_TIMESTAMP = 1488306600;

    const WEBSITE_URLS = [
        'business_website',
        'website_about',
        'website_contact',
        'website_privacy',
        'website_terms',
        'website_refund',
        'website_pricing'
    ];

    const CODE_TO_STATE_MAPPING = array(
      "AN" => "Andaman And Nicobar",
      "AP" => "Andhra Pradesh",
      "AR" => "Arunachal Pradesh",
      "AS" => "Assam",
      "BI" => "Bihar",
      "CH" => "Chandigarh (UT)",
      "CT" => "Chattisgarh",
      "DN" => "Dadra And Nagar Haveli",
      "DD" => "Daman And Diu (UT)",
      "DL" => "Delhi",
      "GO" => "Goa",
      "GJ" => "Gujarat",
      "HA" => "Haryana",
      "HP" => "Himachal Pradesh",
      "JK" => "Jammu And Kashmir",
      "JH" => "Jharkhand",
      "KA" => "Karnataka",
      "KE" => "Kerala",
      "LD" => "Lakshadweep",
      "MP" => "Madhya Pradesh",
      "MH" => "Maharashtra",
      "MA" => "Manipur",
      "ME" => "Meghalaya",
      "MI" => "Mizoram",
      "NA" => "Nagaland",
      "OR" => "Orissa",
      "PO" => "Pondicherry(UT)",
      "PB" => "Punjab",
      "RJ" => "Rajasthan",
      "SK" => "Sikkim",
      "TG" => "Telangana",
      "TN" => "Tamilnadu",
      "TR" => "Tripura",
      "UP" => "Uttar Pradesh",
      "UT" => "Uttranchal",
      "WB" => "West Bengal",
    );

    public function __construct()
    {
        $user = Auth::user();

        $app = \App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        if ($user)
        {
            $this->merchant = $user->currentMerchant();

            $this->user = $user;
        }
    }

    public function fetchDetails($merchantId = null)
    {
        $merchantDetails = $this->getDetailsFromAPI($merchantId);

        $merchantDetails['isYesBankMerchant'] = $this->isYesBankMerchant($merchantDetails);

        $merchantDetails['submitted'] = (int) ($merchantDetails['submitted'] ?? 0);

        $merchantDetails['locked'] = (int) ($merchantDetails['locked'] ?? 0);

        $merchantDetails['activated'] = (int) ($merchantDetails['activated'] ?? 0);

        $merchantDetails['activation_flow'] = $merchantDetails['activation_flow'] ?? null;

        return $merchantDetails;
    }

    public function isYesBankMerchant($merchantDetails)
    {
        $ifscCode = $merchantDetails['bank_branch_ifsc'];

        $yesIfsc = substr( $ifscCode, 0, 4 );

        return ((strcasecmp($yesIfsc, "YESB") === 0) === true) ;
    }


    public function getStateFromCode($state_code = null)
    {
        $state = self::CODE_TO_STATE_MAPPING[$state_code] ?? null;
        if (isset($state))
        {
            return $state;
        }

        return $state_code;
    }


    public function getPresignupDetails($merchantId, $merchantDetails = null)
    {
        if ($merchantDetails === null)
        {
            $merchantDetails = $this->getDetailsFromAPI($merchantId);
        }

        $presignupDetails = [];

        foreach (self::PRE_SIGNUP_FIELDS as $key)
        {
            if (isset($merchantDetails[$key]))
            {
                $presignupDetails[$key] = $merchantDetails[$key];
            }
            else
            {
                $presignupDetails[$key] = null;
            }
        }

        return $presignupDetails;
    }

    public function isPreSignupDetailsSetForNotRegisteredBusiness(array $input)
    {
        foreach (self::NOT_REGISTERED_BUSINESS_PRE_SIGNUP_FIELDS as $key)
        {
            if (empty($input[$key]) === true)
            {
                return false;
            }
        }

        return true;
    }

    public function getDetailsFromAPI($merchantId = null)
    {
        $startTime = microtime(true) * 1000;

        $this->trace->info(TraceCode::GET_MERCHANT_DETAILS_ROUTE_INFO, [
            'action'                => 'FetchStarted',
            'start_time'            => $startTime
        ]);

        if ($merchantId === null)
        {
            $merchantId = $this->merchant->id;
        }

        Trace::debug('MISC_TRACE_CODE', [
            'info'          => "Fetching merchant details from API",
            'merchant_id'   => $merchantId,
        ]);

        $adminUser = Auth::guard('api')->user();

        if (empty($adminUser) === false)
        {
            $this->setAdminCredentials($merchantId);
        }
        else
        {
            $this->setApiCredentials($merchantId);
        }

        list($error, $merchantDetails) = $this->api
                                              ->merchantDetail
                                              ->fetchDetails();

        if (empty($error) === false)
        {
            Trace::debug('MISC_TRACE_CODE', [
                    'error'     => "Error occured while fetching merchant details from API",
                    'exception' => $error,
            ]);
        }

        $endTime  = microtime(true) * 1000;
        $duration = $endTime - $startTime;

        $this->trace->info(TraceCode::GET_MERCHANT_DETAILS_ROUTE_INFO, [
            'action'              => 'FetchEnded',
            'end_time'            => $endTime,
            'duration'            => $duration,
            'controller'          => app('request')->route()->getAction()['controller']

        ]);

        return $merchantDetails;
    }

    public function updateMerchantByAdminOnAPI(array $input, $merchantId)
    {
        $this->setApiCredentials();

        list($error, $merchantDetails) = $this->api
                                              ->merchantDetail
                                              ->updateDetailsByAdmin($merchantId, $input);

        if (empty($error) === false)
        {
            Trace::debug('MISC_TRACE_CODE', [
                    'error'     => "Error occured while updating merchant details on API",
                    'exception' => $error,
            ]);
        }

        return [$error, $merchantDetails];
    }

    public function getWebsiteUrls(array $merchantDetails)
    {
        //
        // Filter = Remove null values
        // Intersect + Flip = filter to the required keys
        //
        return array_filter(array_intersect_key(
            $merchantDetails, array_flip(self::WEBSITE_URLS)
        ));
    }

    public function getUrlKeys()
    {
        return self::WEBSITE_URLS;
    }

    public function updateMerchantDetails($data, $merchantId = null)
    {
        $activated = false;

        $merchantDetails = $this->fetchDetails($merchantId);

        if (((bool) $merchantDetails['merchant']['activated']) === true)
        {
            $activated = true;
        }

        $data = $data + $merchantDetails;

        $data["pre_signup"] = $this->getPresignupDetails($merchantId, $merchantDetails);

        $data = $this->updateInstantActivationExperiment($data);

        $data = $this->updatePresignUpData($data, $activated);

        return $data;
    }

    public function updatePresignUpData($data, $activated)
    {
        $user = Auth::user();

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
            if ($this->isPreSignupDetailsSetForNotRegisteredBusiness($data['pre_signup']) === true)
            {
                $data['pre_signup_complete'] = true;
            }
        }

        //There are approx 3k merchants who have not
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

        $genericUser = Session::get('dashboard_user_payload');
        $currentMerchantId = Session::get('current_merchant_id');
        $currentMerchant = $genericUser->merchants->where('id', $currentMerchantId)->first();

        if (($currentMerchant->role !== 'owner') and
            ($currentMerchant->banking_role !== 'owner'))
        {
            $data['pre_signup_complete'] = true;
        }

        // Using this because test balance is not getting created for X
        // as pre_signup_complete becomes true when experiment remove_presignup_functionality is on
        // Slack thread: https://razorpay.slack.com/archives/C017XUC6V44/p1632118553161500?thread_ts=1631973030.154000&cid=C017XUC6V44
        $isPrimaryRequest = ApiUrl::isPrimaryOriginRequest();

        if($data['pre_signup_complete'] === false and
            $isPrimaryRequest === true)
        {
            $skipPreSignup = (new Merchant\Service)->getTreatment('remove_presignup_functionality');

            if($skipPreSignup['result'] === 'on')
            {
                $data['pre_signup_complete'] = true;
            }
        }

        return $data;
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

    public function isPartnerIntentTrue(array $data): bool
    {
        return (
            isset($data['partner_intent']) and
            $data['partner_intent'] === true
        );
    }

    public function isExperimentOnAndIsUnregisteredBusinessType(array $data): bool
    {


        // check business_type

        $businessType = $data['pre_signup']['business_type'] ?? null;

        if (BusinessType::isBusinessTypeForNotRegisteredBusiness($businessType) === true)
        {
            return true;
        }

        return false;
    }
}

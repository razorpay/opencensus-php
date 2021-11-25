<?php

namespace App\MerchantDetails;

use App\Trace\TraceCode;
use Auth;
use Mail;
use Queue;
use Trace;
use Config;
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
    const NOT_REGISTERED_BUSINESS_PRE_SIGNUP_FIELDS = [
      'contact_name',
      'contact_mobile'
    ];

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
}

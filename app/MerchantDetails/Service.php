<?php

namespace App\MerchantDetails;

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
    const UPLOAD_KEYS = [
        'business_proof_url'   => 'business_proof',
        'business_pan_url'     => 'business_pan_proof',
        'address_proof_url'    => 'address_proof',
        'promoter_address_url' => 'promoter_address_proof',
    ];

    const UPLOAD_KEYS_ACCOUNT = [
        'address_proof_url'    => 'address_proof',
        'promoter_pan_url'     => 'promoter_pan_proof',
    ];

    const PRE_SIGNUP_FIELDS = [
        'business_type',
        'transaction_volume',
        'role',
        'department',
        'contact_name',
        'business_name',
        'contact_mobile',
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

    /**
     * @var bool
     */
    protected $linkedAccount;

    /**
     * @var bool
     */
    protected $linkedAccountKYCRequired;

    public function __construct()
    {
        $user = Auth::user();

        if ($user)
        {
            $this->merchant = $user->currentMerchant();

            $this->user = $user;
        }

        $this->linkedAccount = false;

        $this->linkedAccountRequiresKYC = false;
    }

    public function fetchDetails($merchantId = null)
    {
        $merchantDetails = $this->getDetailsFromAPI($merchantId);

        $merchantDetails['submitted'] = (int) ($merchantDetails['submitted'] ?? 0);

        $merchantDetails['locked'] = (int) ($merchantDetails['locked'] ?? 0);

        $merchantDetails['activated'] = (int) ($merchantDetails['activated'] ?? 0);

        return $merchantDetails;
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

    public function getDetailsFromAPI($merchantId = null)
    {
        if ($merchantId === null)
        {
            $merchantId = $this->merchant->id;
        }

        Trace::debug('MISC_TRACE_CODE', [
            'info'          => "Fetching merchant details from API",
            'merchant_id'   => $merchantId,
        ]);

        $this->setApiCredentials($merchantId);

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

        return $merchantDetails;
    }

    public function saveDetailsOnAPI(array $input, $merchantId = null)
    {
        if ($merchantId === null)
        {
            $merchantId = $this->merchant->id;
        }

        $this->setApiCredentials($merchantId);

        list($error, $merchantDetails) = $this->api
                                              ->merchantDetail
                                              ->submitDetails($input);

        if (empty($error) === false)
        {
            Trace::debug('MISC_TRACE_CODE', [
                    'error'     => "Error occured saving merchant details on API",
                    'exception' => $error,
            ]);
        }

        return [$error, $merchantDetails];
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

    protected function isLinkedAccount(): bool
    {
        return $this->linkedAccount;
    }

    protected function isLinkedAccountKYCRequired(): bool
    {
        return $this->linkedAccountKYCRequired;
    }

    public function setLinkedAccount(bool $linkedAccount)
    {
        $this->linkedAccount = $linkedAccount;
    }

    public function setLinkedAccountKYCRequired(bool $kycRequired)
    {
        $this->linkedAccountKYCRequired = $kycRequired;
    }

    public function getUrlKeys()
    {
        return self::WEBSITE_URLS;
    }
}

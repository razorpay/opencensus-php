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
    const STEP_MAP = [
            'contact_name'                => 1,
            'contact_email'               => 1,
            'transaction_report_email'    => 1,
            'contact_mobile'              => 1,
            'contact_landline'            => 1,

            'business_type'               => 2,
            'business_name'               => 2,
            'business_dba'                => 2,
            'business_international'      => 2,
            'business_paymentdetails'     => 2,
            'business_model'              => 2,
            'business_registered_address' => 2,
            'business_registered_state'   => 2,
            'business_registered_city'    => 2,
            'business_registered_pin'     => 2,
            'business_operation_address'  => 2,
            'business_operation_state'    => 2,
            'business_operation_city'     => 2,
            'business_operation_pin'      => 2,
            'business_doe'                => 2,
            'transaction_volume'          => 2,
            'transaction_value'           => 2,
            'gstin'                       => 2,
            'p_gstin'                     => 2,
            'promoter_pan'                => 2,
            'promoter_pan_name'           => 2,

            'business_website'            => 3,
            'website_about'               => 3,
            'website_contact'             => 3,
            'website_privacy'             => 3,
            'website_terms'               => 3,
            'website_refund'              => 3,
            'website_pricing'             => 3,

            'bank_branch_ifsc'            => 4,
            'bank_account_number'         => 4,
            'bank_account_type'           => 4,
            'bank_account_name'           => 4,
            'bank_beneficiary_address1'   => 4,
            'bank_beneficiary_address2'   => 4,
            'bank_beneficiary_address3'   => 4,
            'bank_beneficiary_city'       => 4,
            'bank_beneficiary_state'      => 4,
            'bank_beneficiary_pin'        => 4,

            'business_proof_url'          => 5,
            'business_pan_url'            => 5,
            'address_proof_url'           => 5,
            'promoter_address_url'        => 5,
    ];

    const STEP_MAP_ACCOUNT = [
            'business_type'               => 1,
            'business_name'               => 1,

            'bank_branch_ifsc'            => 2,
            'bank_account_number'         => 2,
            'bank_account_type'           => 2,
            'bank_account_name'           => 2,
    ];

    const STEP_MAP_ACCOUNT_WITH_KYC = [
            'company_pan'                 => 1,
            'promoter_pan'                => 1,

            'address_proof_url'           => 3,
            'promoter_pan_url'            => 3,
    ];

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

        $steps = $this->getStepsList();

        if ($merchantDetails !== null)
        {
            if ($merchantDetails['can_submit'] === true)
            {
                $merchantDetails['steps_finished'] = $steps;
            }
            else
            {
                $stepFinished = $this->calculateSteps($merchantDetails);

                if (count($stepFinished) !== 0)
                {
                    $unfinishedSteps = array_unique($stepFinished);

                    $finishedSteps = array_values(array_diff($steps, $unfinishedSteps));

                    $merchantDetails['steps_finished'] = $finishedSteps;
                }
            }
        }

        $merchantDetails['submitted'] = (int) ($merchantDetails['submitted'] ?? 0);

        $merchantDetails['locked'] = (int) ($merchantDetails['locked'] ?? 0);

        $merchantDetails['activated'] = (int) ($merchantDetails['activated'] ?? 0);

        $merchantDetails['files'] = $this->getFileDetails($merchantDetails);

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

    public function getActivationFiles($merchantId)
    {
        $this->setApiCredentials();

        list($error, $files) = $this->api
                                    ->merchantDetail
                                    ->getActivationFilesByAdmin($merchantId);

        if (empty($error) === false)
        {
            Trace::debug('MISC_TRACE_CODE', [
                    'error'     => "Error occured while getting activation files from API",
                    'exception' => $error,
            ]);

            return ['files' => []];
        }

        $fileUrls = [];

        $uploadKeys = $this->getUploadDocumentKeys();

        foreach ($uploadKeys as $key => $value)
        {
            if (isset($files[$key]) === true)
            {
                $fileUrls[$value] = $files[$key];
            }
        }

        return ['files' => $fileUrls];
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

    protected function calculateSteps(array $response = null) : array
    {
        $stepFinished = [];

        if ($response === null)
        {
            return $stepFinished;
        }

        $stepMap = $this->getFieldsToStepMap();

        $requiredFields = $response['verification']['required_fields'] ?? [];

        foreach ($requiredFields as $key)
        {
            if (array_key_exists($key, $stepMap) === true)
            {
                $stepFinished[] = $stepMap[$key];
            }
        }

        return $stepFinished;
    }

    /**
     * Returns a map of activation field to their corresponding step numbers
     * for the current merchant
     *
     * @return array
     */
    protected function getFieldsToStepMap() : array
    {
        return ($this->isLinkedAccount() === true) ? self::STEP_MAP_ACCOUNT : self::STEP_MAP;
    }

    /**
     * Array of step numbers for the current merchant activation form
     * Ex: For parent merchant, returns [1, 2, 3, 4, 5]
     *
     * @return array
     */
    protected function getStepsList() : array
    {
        $stepsList = array_values($this->getFieldsToStepMap());

        return array_values(array_unique($stepsList));
    }

    /**
     * Returns an array that maps document upload field names in the DB to the
     * keys sent in the API response
     *
     * @return array
     */
    protected function getUploadDocumentKeys() : array
    {
        return ($this->isLinkedAccount() === true) ? self::UPLOAD_KEYS_ACCOUNT : self::UPLOAD_KEYS;
    }

    protected function getFileDetails($merchantDetail)
    {
        $fileResponse = [];

        $uploadKeys = $this->getUploadDocumentKeys();

        foreach ($uploadKeys as $key => $value)
        {
            if (empty($merchantDetail[$key]) === false)
            {
                $fileResponse[] = $value;
            }
        }

        return $fileResponse;
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

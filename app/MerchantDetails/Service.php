<?php

namespace App\MerchantDetails;

use Auth;
use Aws\Laravel\AwsFacade as AWS;
use Aws\Sdk;
use Illuminate\Support\Facades\App as App;
use Carbon\Carbon;
use Config;
use Mail;
use App\Base;
use Queue;
use App\Mailers\MerchantMailer;
use Requests;
use Trace;

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

     const UPLOAD_KEYS = [
         'business_proof_url'   => 'business_proof',
         'business_pan_url'     => 'business_pan_proof',
         'address_proof_url'    => 'address_proof',
         'promoter_address_url' => 'promoter_address_proof',
    ];

    const UPLOAD_DOCUMENTS = [
        'business_proof'         => "Please upload business proof",
        'business_pan_proof'     => "Please upload business pan card scan.",
        'address_proof'          => "Please upload address proof.",
        'promoter_address_proof' => "Please upload authorised signatory address  proof."
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

    const STEP_FINISHED  = [1, 2, 3, 4, 5];

    public function __construct()
    {
        $user = Auth::user();

        if ($user)
        {
            $this->merchant = $user->currentMerchant;

            $this->user = $user;
        }
    }

    public function fetchDetails($merchantId = null)
    {
        $merchantDetails = $this->getDetailsFromAPI($merchantId);

        if ($merchantDetails !== null)
        {
            if ($merchantDetails['can_submit'] === true)
            {
                $merchantDetails['steps_finished'] = json_encode([1, 2, 3, 4, 5]);

                $merchantDetails['activation_progress'] = 100;
            }
            else
            {
                $stepFinished = $this->calculateSteps($merchantDetails);

                if (count($stepFinished) !== 0)
                {
                    $unfinishedSteps = array_unique($stepFinished);

                    $finishedSteps = array_values(array_diff(self::STEP_FINISHED, $unfinishedSteps));

                    $merchantDetails['steps_finished'] = json_encode($finishedSteps);

                    $merchantDetails['activation_progress'] = intval(count($finishedSteps) * 100/ 5);
                }
            }
        }

        // Hack: Have to change the submitted and locked to int instead of boolean
        if (isset($merchantDetails['submitted']))
        {
            $merchantDetails['submitted'] = ($merchantDetails['submitted'] === true)? 1 : 0;
        }
        if (isset($merchantDetails['locked']))
        {
            $merchantDetails['locked'] = ($merchantDetails['locked'] === true) ? 1: 0;
        }

        $merchantDetails['files'] = $this->getFileDetails($merchantDetails);

        return $merchantDetails;
    }

    public function submitDetails()
    {
        $input = ['submit' => true];

        list($error, $merchantDetails) = $this->saveDetailsOnAPI($input);

        if (empty($error))
        {
            if ($merchantDetails['can_submit'] === false)
            {
                $error = [ "Some mandatory fields are required" ];
            }
            else
            {
                $this->fireActivationTrigger($merchantDetails);
            }
        }

        return $error;
    }

    public function saveDetails(int $step, array $input)
    {
        // 4 is the Bank Account Details
        // We disable this because this doesn't edit the Bank Account
        // on the API side, causing confusion. We have a separate
        // method in merchant details to accomplish the same
        if ($this->merchant->isActive() and ($step === 4))
        {
            return ["Bank account updation not allowed after account is updated"];
        }

        $error = (new Entity)->edit($input, 'step'.$step);

        // Save the finished steps if there are no errors
        if (empty($error))
        {
            list($error, $merchantDetails) = $this->saveDetailsOnAPI($input);
        }

        return $error;
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

        $fileUrl = [];

        if (empty($error))
        {
            foreach (self::UPLOAD_KEYS as $key => $value)
            {
                if (isset($files[$key]))
                {
                    $fileUrl[$value] = $files[$key];
                }
            }
        }
        else
        {
            Trace::debug('MISC_TRACE_CODE', [
                    'error'     => "Error occured while getting activation files from API",
                    'exception' => $error,
            ]);
        }

        $response['files'] = $fileUrl;

        return $response;
    }

    public function checkUploads()
    {
        $error = [];

        $merchantDetails = $this->fetchDetails();

        $files = $merchantDetails['files'];

        foreach (self::UPLOAD_DOCUMENTS as $key => $value)
        {
            if (in_array($key, $files) === false)
            {
                $error[] = $value;
            }
        }

        return $error;
    }

    public function saveUploadedFile($input)
    {
        $error = Validator::checkFileUpload($input);

        if (empty($error))
        {
            $data = Entity::getFileUploadData($input);

            $params = [
                $data['field'] => $data['file']
            ];

            $this->uploadFileToAPI($params);
        }

        return $error;
    }

    /**
     * On submission of activation form by user, send email
     * to the customer and sales team notifying them about the activity
     */
    protected function fireActivationTrigger($merchantDetails)
    {
        $customer = [
            'id'            => $this->merchant->id,
            'name'          => $merchantDetails['contact_name'],
            'email'         => $merchantDetails['contact_email'],
            'business_name' => $merchantDetails['business_name'],
            'dba'           => $merchantDetails['business_dba'],
            'website'       => $merchantDetails['business_website']
        ];

        $user = Auth::user();

        $mailer = new MerchantMailer($user->currentMerchant, $merchantDetails);

        $mailer->confirmActivationSubmission()->queueAndDeliver();

        $mailer->notifyActivationSubmission()->queueAndDeliver();

        // Take screenshots as well
        $urls = $this->getWebsiteUrls($merchantDetails);

        Queue::push('App\Admin\Creevey', [
            $customer['id'],
            $urls,
            $customer['business_name']
        ]);

        // We also send over details to slack
        $link = "<https://dashboard.razorpay.com/admin#/app/merchants/{$customer['id']}/activation|See activation form>";

        $this->slackPost('New activation form submitted', $customer, '#activations_log', $link);

        $zapierData = $this->activationZapierData($customer);

        Queue::push('App\MerchantDetails\Service@postFormSubmissionToZapier', $zapierData);
    }

    protected function activationZapierData(array $customer)
    {
        $customer['date'] =  Carbon::createFromTimeStamp(time(), "Asia/Kolkata")->format('j/m/Y');

        $customer['contact_name'] = $this->user->name;

        return $customer;
    }

    public function postFormSubmissionToZapier($job, $data)
    {
        if (Config::get('razorpay.zapier.mock'))
        {
            return;
        }

        $url = Config::get('razorpay.zapier.submissions');
        Requests::post($url, [], $data);

        $job->delete();
    }

    public function getDetailsFromAPI($merchantId = null)
    {
        if ($merchantId === null)
        {
            $merchantId = $this->merchant->id;
        }

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
        $input = $this->unsetExtraValues($input);

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


    protected function uploadFileToAPI(array $input)
    {
        $this->setApiCredentials($this->merchant['id']);

        $response = $this->api
                         ->merchantDetail
                         ->uploadActivationFile($this->merchant['id'], $input);
    }

    protected function unsetExtraValues(array $input)
    {
        $fieldsToDrop = [
            '1', '2', '3', '4', '5', '6',
            'steps_finished', 'submitted', 'submitted_at', 'created_at', 'updated_at', 'verification',
            'can_submit', 'bank_account_number_confirmation', 'locked', 'activation_progress',
            'agree_terms', 'files', 'business_proof_url', 'business_operation_proof_url',
            'business_pan_url', 'address_proof_url', 'promoter_proof_url', 'promoter_pan_url', 'promoter_address_url',
            'activated'
        ];

        $dropIfEmpty = [
            'transaction_volume',
            'transaction_value',
            'business_international',
        ];

        foreach ($fieldsToDrop as $key)
        {
            unset($input[$key]);
        }

        foreach ($dropIfEmpty as $key)
        {
            if (isset($input[$key]) and empty($input[$key]))
            {
                unset($input[$key]);
            }
        }

        if (isset($input['business_international']))
        {
            // This is boolean, but needs to be passed as 0, 1 to API
            $input['business_international'] = intval($input['business_international']);
        }

        return $input;

    }

    protected function calculateSteps(array $response = null)
    {
        $stepFinished = [];

        if ($response === null)
        {
            return $stepFinished;
        }

        if (isset($response['verification']['required_fields']))
        {
            foreach ($response['verification']['required_fields'] as $key)
            {
                if (array_key_exists($key, self::STEP_MAP))
                {
                    $stepFinished[] = self::STEP_MAP[$key];
                }
            }
        }

        return $stepFinished;
    }

    protected function getFileDetails($merchantDetail)
    {
        $fileResponse = [];

        foreach (self::UPLOAD_KEYS as $key => $value)
        {
            if (empty($merchantDetail[$key]) === false)
            {
                $fileResponse[] = $value;
            }
        }

        return $fileResponse;
    }

    public function getWebsiteUrls(array $merchantDetails)
    {
        // Filter = Remove null values
        // Intersect + Flip = filter to the required keys
        return array_filter(array_intersect_key(
            $merchantDetails, array_flip(self::WEBSITE_URLS)
        ));
    }
}

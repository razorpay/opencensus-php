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
         'business_proof_url'           => 'business_proof',
         'business_operation_proof_url' => 'business_operation_proof',
         'business_pan_url'             => 'business_pan_proof',
         'address_proof_url'            => 'address_proof',
         'promoter_proof_url'           => 'promoter_proof',
         'promoter_pan_url'             => 'promoter_pan_proof',
         'promoter_address_url'         => 'promoter_address_proof',
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

        $merchantDetails['files'] = $this->getFileDetails($merchantId);

        return $merchantDetails;
    }

    public function submitDetails()
    {
        $input = ['submit' => true];

        //TODO: Move this check on API side
        $merchantDetails =  Entity::findorfail($this->merchant->id);

        if ($merchantDetails->submitted === 1)
        {
            return;
        }

        list($error, $merchantDetails) = $this->saveDetailsOnAPI($input);

        if (empty($error))
        {
            if ($merchantDetails['can_submit'] === false)
            {
                $error = [ "Some mandatory fields are required" ];
            }
            else
            {
                $merchantDetails = $this->merchant->merchantDetails;

                $merchantDetails->markSubmitted();

                $merchantDetails->saveOrFail();

                $this->fireActivationTrigger($merchantDetails);
            }
        }

        return $error;
    }

    public function saveDetails($step, array $input)
    {
        $step = intval($step);

        // Check if already finished
        $merchantDetails = $this->merchant->merchantDetails;

        if ($merchantDetails->isLocked())
        {
            return $this->isLockedError();
        }

        // 4 is the Bank Account Details
        // We disable this because this doesn't edit the Bank Account
        // on the API side, causing confusion. We have a separate
        // method in merchant details to accomplish the same
        if ($this->merchant->isActive() and ($step === 4))
        {
            return ['Editing bank account is not permitted for activated merchants'];
        }

        $error = $merchantDetails->finishStep($step, $input);

        // Save the finished steps if there are no errors
        if (empty($error))
        {
            $merchantDetails->saveOrFail();

            // Save to API
            $this->saveDetailsOnAPI($input);
        }

        return $error;
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
        $error = array();

        $merchantDetails = $this->merchant->merchantDetails;

        if ($merchantDetails->isLocked())
        {
            return $this->isLockedError();
        }

        $error = $merchantDetails->checkUploadedFiles();

        if (empty($error))
        {
            // $merchantDetails->addStepToStepsFinished(5);

            $merchantDetails->saveOrFail();
        }

        return $error;
    }

    /**
     * This is a static call because we don't need Merchant Auth for this
     * which is checked in the constructor
     * @param  string $id    Merchant Id
     * @param  string $email New Transaction report email
     * @return array Errors
     */
    public function changeTransactionEmail($id, $csvEmail)
    {
        $merchantDetails = Entity::findorfail($id);

        $error = $merchantDetails->changeTransactionEmail($csvEmail);

        if (empty($error))
        {
            $merchantDetails->saveOrFail();

            $input = ['transaction_report_email' => $csvEmail];
            $this->updateMerchantByAdminOnAPI($input, $id);
        }

        return $error;
    }

    public function saveUploadedFile($input)
    {
        $merchantDetails = $this->merchant->merchantDetails;

        if ($merchantDetails->locked)
        {
            return $this->isLockedError();
        }

        $error = Validator::checkFileUpload($input);

        if (empty($error))
        {
            $data = Entity::getFileUploadData($input);
            $error = $this->uploadFileToS3($data);

            // If there is error while uploading, we dont send it to API
            if (empty($error))
            {
                $params = [ $data['field'] => $data['file'] ];
                $this->uploadFileToAPI($params);
            }
        }

        return $error;
    }

    protected function uploadFileToS3($data)
    {
        $merchantDetails = $this->merchant->merchantDetails;
        $id = $merchantDetails->getMerchantId();

        $error = array();

        $extension = $data['file']->getClientOriginalExtension();
        $mime = $data['file']->getMimeType();

        try
        {
            $s3 = $this->getS3Client();

            $s3Obj = [
                'Bucket'        => env('AWS_ACTIVATION_BUCKET'),
                'Key'           => $id.'/'.$data['key'].'.'.$extension,
                'ContentType'   => $mime,
                'SourceFile'    => $data['file']->getRealPath(),
            ];

            $field = $data['field'];

            if (env('S3_MOCK'))
            {
                $url = 'https://example.com';
            }
            else
            {
                $response = $s3->putObject($s3Obj);
                $url = $response->get('ObjectURL');

                Trace::debug('MISC_TRACE_CODE', [
                    'request'  => $s3Obj,
                    'response' => $response->toArray()
                ]);
            }

            $merchantDetails->$field = $url;
            $merchantDetails->saveOrFail();
        }
        catch(\Exception $e)
        {
            $error[] = 'An error occured in file upload.';

            Trace::debug('MISC_TRACE_CODE', [
                    'error'     => "Error occured in file upload",
                    'exception' => $e->getMessage(),
            ]);

        }

        return $error;
    }

    /**
     * On submission of activation form by user, send email
     * to the customer and sales team notifying them about the activity
     */
    protected function fireActivationTrigger($merchantDetails)
    {
        $customer = array(
            'id' => $merchantDetails->getAttribute('merchant_id'),
            'name' => $merchantDetails->getAttribute('contact_name'),
            'email' => $merchantDetails->getAttribute('contact_email'),
            'business_name' => $merchantDetails->getAttribute('business_name'),
            'dba' => $merchantDetails->getAttribute('business_dba'),
            'website' => $merchantDetails->getAttribute('business_website')
        );

        $user = Auth::user();
        $mailer = new MerchantMailer($user->currentMerchant);

        $mailer->confirmActivationSubmission()->queueAndDeliver();

        $mailer->notifyActivationSubmission()->queueAndDeliver();

        // Take screenshots as well
        $urls = $merchantDetails->getUrls();

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
        $customer['date'] =  Carbon::createFromTimeStamp(time(), "Asia/Kolkata")
            ->format('j/m/Y');

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

    protected function isLockedError()
    {
        $error[] = 'Form has been locked for editing by admin.';

        return $error;
    }

    public function getDetailsFromAPI($merchantId = null)
    {
        if ($merchantId === null)
        {
            $merchantId = $this->merchant->id;
        }

        Trace::debug('MISC_TRACE_CODE', [
            'info'     => "Fetching merchant details from API",
            'merchant' => $merchantId,
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
        unset($input['1']);
        unset($input['2']);
        unset($input['3']);
        unset($input['4']);
        unset($input['5']);
        unset($input['6']);
        unset($input['steps_finished']);
        unset($input['submitted']);
        unset($input['submitted_at']);
        unset($input['created_at']);
        unset($input['updated_at']);
        unset($input['verification']);
        unset($input['can_submit']);
        unset($input['bank_account_number_confirmation']);
        unset($input['locked']);
        unset($input['activation_progress']);
        unset($input['agree_terms']);
        unset($input['files']);
        unset($input['business_proof_url']);
        unset($input['business_operation_proof_url']);
        unset($input['business_pan_url']);
        unset($input['address_proof_url']);
        unset($input['promoter_proof_url']);
        unset($input['promoter_pan_url']);
        unset($input['promoter_address_url']);

        if (isset($input['transaction_volume']) && $input['transaction_volume'] === '')
        {
            unset($input['transaction_volume']);
        }

        if (isset($input['transaction_value']) && $input['transaction_value'] === '')
        {
            unset($input['transaction_value']);
        }

        if (isset($input['business_international']))
        {
            $input['business_international'] = intval($input['business_international']);
        }

        return $input;

    }

    public function saveMerchantDetails(array $input)
    {
        $merchantDetails = $this->merchant->merchantDetails;

        $merchantDetails->fill($input);

        $merchantDetails->saveOrFail();
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


    protected function getFileDetails($merchantId)
    {
        if ($merchantId === null)
        {
            $merchantId = $this->merchant->id;
        }

        $merchantDetail = Entity::findorfail($merchantId);

        $merchantDetailArr = $merchantDetail->toArray();

        $fileResponse = [];

        foreach (self::UPLOAD_KEYS as $key => $value)
        {
            if (empty($merchantDetailArr[$key]) === false)
            {
                $fileResponse[] = $value;
            }
        }

        return $fileResponse;
    }
}

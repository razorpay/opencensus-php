<?php

namespace App\MerchantDetails;

use Auth;
use Aws\Laravel\AwsFacade as AWS;
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
    public function __construct()
    {
        $user = Auth::user();

        if ($user)
        {
            $this->merchant = $user->currentMerchant;
            $this->merchantDetails = $user->currentMerchant->MerchantDetails;
            $this->user = $user;
        }
    }

    public function fetchDetails()
    {
        $merchantDetails = $this->merchantDetails;

        $details = $merchantDetails->filterForAjax();

        $details['data'] = Validator::sortDataInSteps($details['data']);

        return $details;
    }

    public function submitDetails()
    {
        $error = array();
        $data = array();

        $merchantDetails = $this->merchantDetails;

        if ($merchantDetails->isLocked())
        {
            return $this->isLockedError();
        }

        if ((int)$merchantDetails->submitted === 0)
        {
            $missingSteps = $merchantDetails->getStepsNotFinished();

            //
            // No missing steps means all details succesfully submitted
            // Mark submitted true
            //
            if (empty($missingSteps))
            {
                $merchantDetails->markSubmitted();

                // Updating the model
                $merchantDetails->saveOrFail();

                $this->fireActivationTrigger($merchantDetails);
            }
            else
            {
                foreach ($missingSteps as $step)
                {
                    $error[] = 'Step ' . $step . ' has not been saved or contains errors. ' .
                               'Please save all steps before submission.';
                }
            }
        }

        return $error;
    }

    public function saveDetails($step, array $input)
    {
        $step = intval($step);

        // Check if already finished
        $merchantDetails = $this->merchantDetails;

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
        }

        return $error;
    }

    public function checkUploads()
    {
        $error = array();

        $merchantDetails = $this->merchantDetails;

        if ($merchantDetails->isLocked())
        {
            return $this->isLockedError();
        }

        $error = $merchantDetails->checkUploadedFiles();

        if (empty($error))
        {
            $merchantDetails->addStepToStepsFinished(5);

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
    public static function changeTransactionEmail($id, $email)
    {
        $merchantDetails = Entity::findorfail($id);

        $error = $merchantDetails->changeTransactionEmail($email);

        if (empty($error))
        {
            $merchantDetails->saveOrFail();
        }

        return $error;
    }

    public function saveUploadedFile($input)
    {
        $merchantDetails = $this->merchantDetails;

        if ($merchantDetails->locked)
        {
            return $this->isLockedError();
        }

        $error = Validator::checkFileUpload($input);

        if (empty($error))
        {
            $data = Entity::getFileUploadData($input);
            $error = $this->uploadFileToS3($data);
        }

        return $error;
    }

    protected function uploadFileToS3($data)
    {
        $merchantDetails = $this->merchantDetails;
        $id = $merchantDetails->getMerchantId();

        $error = array();

        $extension = $data['file']->getClientOriginalExtension();
        $mime = $data['file']->getMimeType();

        $s3 = App::make('aws')->createClient('s3');

        try
        {
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
}

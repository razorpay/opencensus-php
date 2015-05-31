<?php

namespace Models\MerchantDetails;

use AWS;
use Mailgun;
use Models\Base;

class Service extends Base\Service
{
    public function __construct()
    {
        $this->merchantDetails = \Auth::merchant()->user()->MerchantDetails;
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
                $merchantDetails->markSubmittedTrue();

                // Updating the model
                $merchantDetails->saveOrFail();

                $this->sendActivationFormSubmissionMails($merchantDetails);
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
        // Check if already finished
        $merchantDetails = $this->merchantDetails;

        if ($merchantDetails->isLocked())
        {
            return $this->isLockedError();
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
        $id = $merchantDetails->merchant_id;

        $error = array();

        $extension = $data['file']->getClientOriginalExtension();
        $mime = $data['file']->getMimeType();

        $s3 =  AWS::get('s3');

        try
        {
            $s3Obj = array(
                'Bucket'        => $_ENV['AWS_ACTIVATION_BUCKET'],
                'Key'           => $id.'/'.$data['key'].'.'.$extension,
                'ContentType'   => $mime,
                'SourceFile'    => $data['file']->getRealPath(),
            );

            $result = $s3->putObject($s3Obj);

            $merchantDetails->$data['field'] = $result['ObjectURL'];
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
    protected function sendActivationFormSubmissionMails($merchantDetails)
    {
        $customer = array(
            'id' => $merchantDetails->getAttribute('merchant_id'),
            'name' => $merchantDetails->getAttribute('contact_name'),
            'email' => $merchantDetails->getAttribute('contact_email')
        );

        $salesEmail = 'sales@razorpay.com';

        Mailgun::send('emails.submission', $customer, function($mail) use ($customer)
        {

            $mail->to($customer['email'], $customer['name'])
                 ->subject('Your Razorpay acount is pending approval');
        });

        Mailgun::send('emails.admin_notify', $customer, function($mail) use ($customer, $salesEmail)
        {
            $mail->to($salesEmail, 'Razorpay Sales Team')
                 ->subject('New activation form submitted - '.$customer['id']);
        });
    }

    protected function isLockedError()
    {
        $error[] = 'Form has been locked for editing by admin.';

        return $error;
    }
}
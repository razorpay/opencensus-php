<?php

namespace Models\MerchantDetails;

use AWS;
use Models\Base;
use Models\DAL;
use Models\Manager;

class Service extends Base\Service
{
    public function __construct()
    {
        $this->merchantDetails = \Auth::merchant()->user()->MerchantDetails;
    }

    public function fetchDetails()
    {
        $merchantDetails = $this->merchantDetails

        $details = $merchantDetails->filterForAjax();

        $details['data'] = Validator::sortDataInSteps($details['data']);

        return $details;
    }

    public function submitDetails()
    {
        $error = array();
        $data = array();

        $merchantDetails = $this->merchantDetails

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
                $merchantDetails->save();
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
        $merchantDetails = $this->merchantDetails

        if ($merchantDetails->isLocked())
        {
            return $this->isLockedError();
        }

        list($error, $data) = Validator::createValidate($input, 'step'.$step)->getData();

        // Save the finished steps if there are no errors
        if (empty($error))
        {
            $merchantDetails->fill($data);
            $merchantDetails->addStepToStepsFinished($step);
            $merchantDetails->save();
        }

        return $error;
    }

    public function checkUploads()
    {
        $error = array();

        $merchantDetails = $this->merchantDetails

        if ($merchantDetails->isLocked())
        {
            return $this->isLockedError();
        }

        $error = $merchantDetails->checkUploadedFiles();

        if (empty($error))
        {
            $merchantDetails->addStepToStepsFinished(5);

            $merchantDetails->save();
        }

        return $error;
    }

    public function saveUploadedFile($input)
    {
        $merchantDetails = $this->merchantDetails

        if ($merchantDetails->locked)
        {
            return $this->isLockedError();
        }

        $error = Validator::checkFileUpload($input);

        if (empty($error))
        {
            $data = $this->getFileUploadData($input);
            $error = $this->uploadFileToS3($data);
        }

        return $error;
    }

    protected uploadFileToS3($data)
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
            $merchantDetails->save();
        }
        catch(\Exception $e)
        {
            $error[] = 'An error occured in file upload.';
        }

        return $error;
    }

    protected function isLockedError()
    {
        $error[] = 'Form has been locked for editing by admin.';

        return $error;
    }
}
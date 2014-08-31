<?php

namespace Models\Service;

use Models\DAL;
use Models\Manager;

class MerchantDetails extends Service
{
    public function fetchDetails()
    {       
        $merchant_details = \Auth::merchant()->user()->MerchantDetails;

        $merchant_details = DAL\MerchantDetails::filterForAjax($merchant_details);

        $merchant_details['data'] = Manager\MerchantDetails::sortDataInSteps($merchant_details['data']);

        return $merchant_details;
    }
    public function submitDetails()
    {       
        $error = array();
        $data = array();

        $merchant_details = \Auth::merchant()->user()->MerchantDetails;

        if($merchant_details->locked)
        {
            $error[]= 'Form has been locked for editing by admin.';
            return $error;
        }

        if((int)$merchant_details->submitted === 0)
        {
            //Check if already finished
            $steps_finished = json_decode($merchant_details->steps_finished, true);
            
            $missing_steps = Manager\MerchantDetails::validateActivation($steps_finished);
    
            foreach($missing_steps as $step)
            {
                $error[] = 'Step '.$step.' has not been saved or contains errors. Please save all steps before submission.';
            }

            if (empty($missing_steps))
            {
                if(in_array(6, $steps_finished) === false)
                {    
                    $steps_finished[] = 6;
                    
                    $data['steps_finished'] = json_encode($steps_finished);
                }

                $data['submitted'] = 1;

                //Updating the model
                $merchant_details->update($data);
            }
        }
        
        return $error;
    }

    public function saveDetails($id, array $input)
    {       
        //Check if already finished
        $merchant_details = \Auth::merchant()->user()->MerchantDetails;

        if($merchant_details->locked)
        {
            $error[]= 'Form has been locked for editing by admin.';
            return $error;
        }

        list($error, $data) = Manager\MerchantDetails::createValidate($input, 'step'.$id)->getData();

        if (empty($error))
        {   
            $steps_finished = json_decode($merchant_details->steps_finished, true);
        
            if(in_array($id, $steps_finished) == false){
                $steps_finished[] = (int)$id;
            }
            
            $data['steps_finished'] = json_encode($steps_finished);

            //Updating the model
            $merchant_details->update($data);
        }
        return $error;
    }

    public function checkUploads()
    {   
        $error = array();

        $merchant_details = \Auth::merchant()->user()->MerchantDetails;

        if($merchant_details->locked)
        {
            $error[]= 'Form has been locked for editing by admin.';
            return $error;
        }

        //Check if already finished
        $steps_finished = json_decode($merchant_details->steps_finished, true);

        if(in_array(5, $steps_finished)){
            //return success if already finished
            return $error;
        }
                    
        $error = DAL\MerchantDetails::checkUploadedFiles($merchant_details);

        if (empty($error))
        {   
            $steps_finished[] = 5;

            $steps_finished = json_encode($steps_finished);

            //Updating the model
            $merchant_details->steps_finished = $steps_finished;
            $merchant_details->save();
        }
        return $error;
    }

    public function saveUploadedFile($input)
    {   
        $merchant_details = \Auth::merchant()->user()->MerchantDetails;
        
        if($merchant_details->locked)
        {
            $error[]= 'Form has been locked for editing by admin.';
            return $error;
        }

        $error = Manager\MerchantDetails::checkFileUpload($input);

        if (empty($error))
        {   
            $data = DAL\MerchantDetails::getDataForUpload($input);

            $merchant_details = \Auth::merchant()->user()->MerchantDetails;

            $id = $merchant_details->merchant_id;

            $extension = $data['file']->getClientOriginalExtension();
            $mime = $data['file']->getMimeType();

            $s3 =  \AWS::get('s3');

            try
            {
                $result = $s3->putObject(array(
                'Bucket' =>$_ENV['AWS_ACTIVATION_BUCKET'],

                'Key'    => $id.'/'.$data['key'].'.'.$extension,

                'ContentType' => $mime,

                'SourceFile' => $data['file']->getRealPath(),
                ));

                $merchant_details->$data['field'] = $result['ObjectURL'];
                $merchant_details->save();
            }
            catch(\Exception $e)
            {
                $error[] = 'An error occured in file upload.';
            }
        }

        return $error;
    }
}
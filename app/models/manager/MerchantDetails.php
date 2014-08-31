<?php

namespace Models\Manager;

use Models\Service;

class MerchantDetails extends Manager
{
    protected static $step1Rules = array(
        'contact_name'          => 'required|alpha_space|max:255',
        'contact_email'         => 'required|email|max:255',
        'contact_mobile'        => 'required|numeric|digits_between:8,11',
        'contact_landline'      => 'required|numeric|digits_between:8,11'
    );

    protected static $step2Rules = array(
        'bussiness_type'                => 'required|numeric|digits_between:1,10',
        'bussiness_category'             => 'required|alpha_space',
        'bussiness_subcategory'          => 'required|alpha_space',
        'bussiness_registered_address'  => 'required|max:255',
        'bussiness_registered_state'    => 'required|alpha_space|max:255',
        'bussiness_registered_city'     => 'required|alpha_space|max:255',
        'bussiness_registered_pin'      => 'required|max:15',
        'bussiness_operation_address'   => 'required|max:255',
        'bussiness_operation_state'     => 'required|alpha_space|max:255',
        'bussiness_operation_city'      => 'required|alpha_space|max:255',
        'bussiness_operation_pin'       => 'required|max:15',
        'bussiness_doe'                 => 'required|date',
        'company_cin'                   => 'alpha_num|max:20',
        'company_pan'                   => 'alpha_num|max:15',
        'company_pan_name'              => 'alpha_space|max:255|required_with:company_pan',
        'bussiness_model'               => 'required|max:2000',
        'transaction_volume'            => 'required|numeric|digits_between:1,4',
        'transaction_value'             => 'required|numeric|max:10000000'
    );

    protected static $step3Rules = array(
        'promoter_pan'          => 'required|alpha_num|max:15',
        'promoter_pan_name'     => 'required|alpha_space|max:255'
    );

    protected static $step4Rules = array(
        'bank_name'             => 'required|alpha_space|max:255',
        'bank_account_number'   => 'required|numeric|digits_between:1,20',
        'bank_account_name'     => 'required|alpha_space|max:255',
        'bank_account_type'     => 'required|alpha_space|max:20',
        'bank_branch'           => 'required|max:255',
        'bank_branch_ifsc'      => 'required|alpha_num|max:20'
    );

    protected static $uploadKeys = array(
        'bussiness_proof',
        'bussiness_pan_proof',
        'promoter_pan_proof',
        'address_proof'
    );

    protected static $allowed_extensions = array(
        'pdf', 'png', 'jpg'
    );

    protected static $allowed_mimes = array(
       'image/jpeg', 'image/png', 'application/pdf', 'application/x-pdf'
    );

    public static function checkFileUpload($input)
    {
        $error = array();

        $uploadKeys = static::$uploadKeys;

        if (count($input) != 1 OR in_array(key($input), $uploadKeys) == false)
        {
            throw new \InvalidArgumentException('Invalid parameters.');
        }

        $file = current($input);

        if(
            in_array($file->getClientOriginalExtension(), static::$allowed_extensions) == false
            OR
            in_array($file->getMimeType(), static::$allowed_mimes) == false
        )
        {
            $error[] = 'Invalid File format. Only pdf, png and jpg is allowed.';
        }

        return $error;
        
    }

    public static function validateActivation($steps_finished)
    {
        $required_steps = array(1, 2, 3, 4, 5);

        $missing_steps = array_diff($required_steps, $steps_finished);

        return $missing_steps;        
    }

    public static function sortDataInSteps($data)
    {   
        $response = array();
        foreach($data as $key => $value) {
            if (array_key_exists($key, static::$step1Rules) === true) {
                $response['1'][$key] = $value;
            }
            else if (array_key_exists($key, static::$step2Rules) === true) {
                $response['2'][$key] = $value;
            }
            else if (array_key_exists($key, static::$step3Rules) === true) {
                $response['3'][$key] = $value;
            }
            else if (array_key_exists($key, static::$step4Rules) === true) {
                $response['4'][$key] = $value;
            }
        }

        return $response;
    }
}
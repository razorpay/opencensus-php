<?php

namespace Models\MerchantDetails;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $step1Rules = array(
        'contact_name'          => 'required|alpha_space|max:255',
        'contact_email'         => 'required|email|max:255',
        'contact_mobile'        => 'required|numeric|digits_between:8,11',
        'contact_landline'      => 'numeric|digits_between:8,11'
    );

    protected static $step2Rules = array(
        'business_type'                => 'required|numeric|digits_between:1,10',
        'business_name'                => 'required|max:255',
        'business_dba'                 => 'required|max:255',
        'business_website'             => 'required|max:255',
        'business_international'       => 'required|in:0,1',
        'business_paymentdetails'      => 'required|max:2000',
        'business_registered_address'  => 'required|max:255',
        'business_registered_state'    => 'required|alpha_space|max:255',
        'business_registered_city'     => 'required|alpha_space|max:255',
        'business_registered_pin'      => 'required|max:15',
        'business_operation_address'   => 'required|max:255',
        'business_operation_state'     => 'required|alpha_space|max:255',
        'business_operation_city'      => 'required|alpha_space|max:255',
        'business_operation_pin'       => 'required|max:15',
        'business_doe'                 => 'required|date',
        'company_cin'                   => 'alpha_num|max:21',
        'company_pan'                   => 'alpha_num|max:15',
        'company_pan_name'              => 'alpha_space|max:255|required_with:company_pan',
        'business_model'               => 'required|max:2000',
        'transaction_volume'            => 'required|numeric|digits_between:1,4',
        'transaction_value'             => 'required|numeric|min:1|max:10000000'
    );

    protected static $step3Rules = array(
        'promoter_pan'          => 'required|alpha_num|max:15',
        'promoter_pan_name'     => 'required|alpha_space|max:255'
    );

    protected static $step4Rules = array(
        'bank_name'             => 'required|alpha_space|max:255',
        'bank_account_number'   => 'required|numeric|digits_between:1,20',
        'bank_account_name'     => 'required|alpha_space|max:40',
        'bank_account_type'     => 'required|alpha_space|max:20',
        'bank_branch'           => 'required|max:255',
        'bank_branch_ifsc'      => 'required|alpha_num|max:11',
        'bank_beneficiary_address1' => 'required|max:30',
        'bank_beneficiary_address2' => 'max:30',
        'bank_beneficiary_address3' => 'max:30',
        'bank_beneficiary_city'      => 'required|max:30',
        'bank_beneficiary_state'     => 'required|max:2',
        'bank_beneficiary_pin'       => 'required|integer|digits:6'
    );

    protected static $uploadKeys = array(
        'business_proof',
        'business_pan_proof',
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

        if ((count($input) !== 1) or
            (in_array(key($input), $uploadKeys) == false))
        {
            throw new \InvalidArgumentException('Invalid parameters.');
        }

        $file = current($input);

        $extension = $file->getClientOriginalExtension();
        if ((in_array($extension, static::$allowed_extensions) === false) or
            (in_array($file->getMimeType(), static::$allowed_mimes) === false))
        {
            $error[] = 'Invalid File format. Only pdf, png and jpg is allowed.';
        }

        return $error;
    }

    /**
     * Adds each key to an array of step digits
     * The key belongs to that stepRules
     * @param  array $data
     * @return array
     */
    public static function sortDataInSteps($data)
    {
        $response = array();

        foreach ($data as $key => $value)
        {
            $step = self::checkKeyInStepRules($key);

            if ($step !== null)
            {
                $response[$step][$key] = $value;
            }
        }

        return $response;
    }

    protected static function checkKeyInStepRules($key)
    {
        $steps = range(1, 4);

        foreach ($steps as $step)
        {
            $var = 'step'.$step.'Rules';

            if (array_key_exists($key, static::$$var))
            {
                return $step;
            }
        }
    }
}
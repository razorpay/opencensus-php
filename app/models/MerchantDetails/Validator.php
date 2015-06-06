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
        'transaction_value'             => 'required|numeric|min:1|max:10000000',
        'promoter_pan'          => 'required|alpha_num|max:15',
        'promoter_pan_name'     => 'required|alpha_space|max:255'
    );

    protected static $step3Rules = array(
        'business_website'             => 'required|max:255',
        'website_about'                => 'required|max:255',
        'website_contact'              => 'required|max:255',
        'website_privacy'              => 'required|max:255',
        'website_terms'                => 'required|max:255',
        'website_refund'               => 'required|max:255',
        'website_pricing'              => 'required|max:255',
        'website_login'                => 'required|max:255'        
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
        'business_operation_proof',
        'business_pan_proof',
        'address_proof',
        'promoter_proof',
        'promoter_pan_proof',
        'promoter_address_proof',
    );

    protected  $customAttributes = array(
        'contact_name'          => 'Contact Name',
        'contact_email'         => 'Email',
        'contact_mobile'        => 'Mobile',
        'contact_landline'      => 'Landline',
        'business_type'         => 'Organisation Type',
        'business_name'         => 'Full Business Name',
        'business_dba'          => 'Doing Business As',
        'business_international'        => 'International Payments Required?' ,
        'business_paymentdetails'       => 'Payments Accepted For',
        'business_registered_address'   => 'Registered Address',
        'business_registered_state'     => 'Registered Address State',
        'business_registered_city'      => 'Registered Address City',
        'business_registered_pin'       => 'Registered Address Pin',
        'business_operation_address'    => 'Operation Address',
        'business_operation_state'      => 'Operation Address State',
        'business_operation_city'       => 'Operation Address City',
        'business_operation_pin'        => 'Operation Address Pin',
        'promoter_pan'                  => 'Auhorised Signatory PAN',
        'promoter_pan_name'             => 'Authorised Signatory Name',
        'business_doe'                  => 'Date of Establishment',
        'company_cin'                   => 'Company CIN Number',
        'company_pan'                   => 'Company PAN',
        'company_pan_name'              => 'Company Name on PAN', 
        'business_model'                => 'Business Model',
        'transaction_volume'            => 'Transaction Volume',
        'transaction_value'             => 'Average Transaction value',
        'business_website'              => 'Website Address',
        'website_about'                 => 'About us URL',
        'website_contact'               => 'Contact Us URL',
        'website_privacy'               => 'Privacy Policy URL',
        'website_terms'                 => 'Terms & Conditions URL',
        'website_refund'                => 'Refund Policy URL',
        'website_pricing'               => 'Pricing URL',
        'website_login'                 => 'Login URL',
        'bank_name'                     => 'Beneficiary Bank Name',
        'bank_account_number'           => 'Beneficiary Account Number',
        'bank_account_name'             => 'Beneficiary Name',
        'bank_account_type'             => 'Beneficiary Account Type',
        'bank_branch'                   => 'Beneficiary Branch',
        'bank_branch_ifsc'              => 'Beneficiary Bank IFSC',
        'bank_beneficiary_address1'     => 'Beneficiary Address Line 1',
        'bank_beneficiary_address2'     => 'Beneficiary Address Line 2',
        'bank_beneficiary_address3'     => 'Beneficiary Address Line 3',
        'bank_beneficiary_city'         => 'Beneficiary Address City',
        'bank_beneficiary_state'        => 'Beneficiary Address State',
        'bank_beneficiary_pin'          => 'Beneficiary Address PIN',
        'business_proof'                => 'Business Registration Proof',
        'business_operation_proof'      => 'Business Operation Proof',
        'business_pan_proof'            => 'Business PAN Proof',
        'address_proof'                 => 'Business Address Proof',
        'promoter_proof'                => 'Authorised Signatory Proof',
        'promoter_pan_proof'            => 'Authorised Signatory PAN Proof',
        'promoter_address_proof'        => 'Authorised Signatory Address Proof'
    );
    protected static $allowed_extensions = array(
        'pdf', 'png', 'jpg', 'jpeg'
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
        if ((in_array(strtolower($extension), static::$allowed_extensions) === false) or
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
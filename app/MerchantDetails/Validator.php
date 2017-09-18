<?php

namespace App\MerchantDetails;

use App\Base;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Razorpay\IFSC\IFSC;

class Validator extends Base\Validator
{
    const INVALID_IFSC_CODE_MESSAGE = 'Invalid IFSC Code';

    protected static $step1Rules = array(
        'contact_name'              => 'required|alpha_space|max:255',
        'contact_email'             => 'required|email|max:255',
        'transaction_report_email'  => 'required|email|max:255',
        'contact_mobile'            => 'required|numeric|digits_between:8,11',
        'contact_landline'          => 'numeric|digits_between:8,11'
    );

    protected static $editEmailRules = [
        'transaction_report_email'  => 'sometimes'
    ];

    protected static $step4Validators = [
        'ifsc_code'
    ];

    protected function validateIfscCode(array $input)
    {
        if (isset($input['bank_branch_ifsc']))
        {
            $ifsc = $input['bank_branch_ifsc'];

            if (!IFSC::validate($ifsc))
            {
                $this->addError('bank_branch_ifsc', self::INVALID_IFSC_CODE_MESSAGE);
            }
        }
    }

    protected static $step2Rules = array(
        'business_type'                 => 'required|numeric|digits_between:1,10',
        'business_name'                 => 'required|max:255',
        'business_dba'                  => 'required|max:255',
        'business_international'        => 'required|in:0,1',
        'business_paymentdetails'       => 'required|max:2000',
        'business_registered_address'   => 'required|max:255',
        'business_registered_state'     => 'required|alpha_space|max:255',
        'business_registered_city'      => 'required|alpha_space|max:255',
        'business_registered_pin'       => 'required|max:15',
        'business_operation_address'    => 'required|max:255',
        'business_operation_state'      => 'required|alpha_space|max:255',
        'business_operation_city'       => 'required|alpha_space|max:255',
        'business_operation_pin'        => 'required|max:15',
        'business_doe'                  => 'required|date_format:"Y-m-d"|before:"today"',
        'gstin'                         => 'sometimes|string',
        'p_gstin'                       => 'sometimes|string',
        'company_cin'                   => 'alpha_num|max:21',
        'company_pan'                   => 'alpha_num|max:15',
        'company_pan_name'              => 'max:255|required_with:company_pan',
        'business_model'                => 'required|max:255',
        'transaction_volume'            => 'required|numeric|digits_between:1,4',
        'transaction_value'             => 'required|numeric|min:1|max:10000000',
        'promoter_pan'                  => 'required|alpha_num|max:15',
        'promoter_pan_name'             => 'required|max:255',
    );

    protected static $step3Rules = array(
        'business_website'             => 'required|max:255|url',
        'website_about'                => 'required|max:255|url',
        'website_contact'              => 'required|max:255|url',
        'website_privacy'              => 'required|max:255|url',
        'website_terms'                => 'required|max:255|url',
        'website_refund'               => 'required|max:255|url',
        'website_pricing'              => 'required|max:255|url',
        'website_login'                => 'sometimes|max:255|url'
    );

    protected static $step4Rules = array(
        'bank_account_number'       => 'required|alpha_num|between:5,20',
        'bank_account_name'         => 'required|alpha_space_num|max:40',
        'bank_account_type'         => 'required|alpha_space|max:20',
        'bank_branch'               => 'sometimes|max:255',
        'bank_branch_ifsc'          => 'required|alpha_num|max:11',
        'bank_beneficiary_address1' => 'required|max:30',
        'bank_beneficiary_address2' => 'max:30',
        'bank_beneficiary_address3' => 'max:30',
        'bank_beneficiary_city'     => 'required|max:30',
        'bank_beneficiary_state'    => 'required|max:2',
        'bank_beneficiary_pin'      => 'required|integer|digits:6'
    );

    // Rules for vendor account validation
    protected static $step1AccountRules = [
        'business_type'                 => 'required|numeric|digits_between:1,10',
        'business_name'                 => 'required|max:255',
        'company_pan'                   => 'sometimes|alpha_num|max:15',
        'promoter_pan'                  => 'required|alpha_num|max:15',
    ];

    protected static $step2AccountRules = [
        'bank_account_number'   => 'required|alpha_num|between:5,20',
        'bank_account_name'     => 'required|alpha_space_num|max:40',
        'bank_account_type'     => 'required|alpha_space|max:20',
        'bank_branch_ifsc'      => 'required|alpha_num|max:11',
    ];

    protected static $step2_accountValidators = [
        'ifsc_code'
    ];

    protected static $preSignupRules = [
        'business_type'                 => 'sometimes|numeric|digits_between:1,10',
        'transaction_volume'            => 'sometimes|numeric|digits_between:1,4',
        'role'                          => 'sometimes|numeric|digits_between:1,6',
        'department'                    => 'sometimes|numeric|digits_between:1,6',
        'business_name'                 => 'sometimes|max:255',
        'contact_name'                  => 'sometimes|alpha_space|max:255',
        'contact_mobile'                => 'sometimes|numeric|digits_between:8,11',
    ];

    protected $customAttributes = array(
        'contact_name'                  => 'Contact Name',
        'contact_email'                 => 'Email',
        'transaction_report_email'      => 'Transaction Report Email',
        'contact_mobile'                => 'Mobile',
        'contact_landline'              => 'Landline',
        'business_type'                 => 'Organisation Type',
        'business_name'                 => 'Full Business Name',
        'business_dba'                  => 'Billing Label',
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
        'promoter_pan'                  => 'Authorised Signatory PAN',
        'promoter_pan_name'             => 'Authorised Signatory Name',
        'business_doe'                  => 'Date of Establishment',
        'gstin'                         => 'GST Identification Number',
        'p_gstin'                       => 'Provisional GST Identification Number',
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
        'bank_account_number'           => 'Bank Account Number',
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
        // The field is now labelled as "Bank Account Statement with Address"
        'address_proof'                 => 'Bank Account Statement',
        'promoter_proof'                => 'Authorised Signatory Proof',
        'promoter_pan_proof'            => 'Authorised Signatory PAN Proof',
        'promoter_address_proof'        => 'Authorised Signatory Address Proof'
    );

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

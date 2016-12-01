<?php

namespace RZP\Models\Merchant\Detail;

use RZP\Base;
use RZP\Exception;
use Razorpay\IFSC\IFSC;
use Validator as LaravelValidator;
use RZP\Models\Merchant\Detail;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class Validator extends Base\Validator
{
    const INVALID_IFSC_CODE_MESSAGE = 'Invalid IFSC Code';

    protected $allInputRules = [
        Entity::CONTACT_NAME                    => 'alpha_space|max:255',
        Entity::CONTACT_EMAIL                   => 'email|max:255',
        Entity::CONTACT_MOBILE                  => 'numeric|digits_between:8,11',
        Entity::CONTACT_LANDLINE                => 'numeric|digits_between:8,11',
        Entity::BUSINESS_TYPE                   => 'numeric|digits_between:1,10',
        Entity::BUSINESS_NAME                   => 'max:255',
        Entity::BUSINESS_DBA                    => 'max:255',
        Entity::BUSINESS_WEBSITE                => 'max:255|url',
        Entity::BUSINESS_INTERNATIONAL          => 'in:0,1',
        Entity::BUSINESS_PAYMENTDETAILS         => 'max:2000',
        Entity::BUSINESS_MODEL                  => 'max:255',
        Entity::BUSINESS_REGISTERED_ADDRESS     => 'max:255',
        Entity::BUSINESS_REGISTERED_STATE       => 'alpha_space|max:255',
        Entity::BUSINESS_REGISTERED_CITY        => 'alpha_space|max:255',
        Entity::BUSINESS_REGISTERED_PIN         => 'max:15',
        Entity::BUSINESS_OPERATION_ADDRESS      => 'max:255',
        Entity::BUSINESS_OPERATION_STATE        => 'alpha_space|max:255',
        Entity::BUSINESS_OPERATION_CITY         => 'alpha_space|max:255',
        Entity::BUSINESS_OPERATION_PIN          => 'max:15',
        Entity::BUSINESS_DOE                    => 'date_format:"Y-m-d"|before:"today"',
        Entity::COMPANY_CIN                     => 'alpha_num|max:21',
        Entity::COMPANY_PAN                     => 'alpha_num|max:15',
        Entity::COMPANY_PAN_NAME                => 'max:255|required_with:company_pan',
        Entity::TRANSACTION_VOLUME              => 'numeric|digits_between:1,4',
        Entity::TRANSACTION_VALUE               => 'numeric|min:1|max:10000000',
        Entity::PROMOTER_PAN                    => 'alpha_num|max:15',
        Entity::PROMOTER_PAN_NAME               => 'max:255',
        Entity::BANK_NAME                       => 'alpha_num|between:5,20',
        Entity::BANK_ACCOUNT_NUMBER             => 'alpha_num|between:5,20',
        Entity::BANK_ACCOUNT_NAME               => 'alpha_space_num|max:40',
        Entity::BANK_ACCOUNT_TYPE               => 'alpha_space|max:20',
        Entity::BANK_BRANCH                     => 'max:255',
        Entity::BANK_BRANCH_IFSC                => 'alpha_num|max:11|custom_ifsc_validator',
        Entity::BANK_BENEFICIARY_ADDRESS1       => 'max:30',
        Entity::BANK_BENEFICIARY_ADDRESS2       => 'max:30',
        Entity::BANK_BENEFICIARY_ADDRESS3       => 'max:30',
        Entity::BANK_BENEFICIARY_CITY           => 'max:30',
        Entity::BANK_BENEFICIARY_STATE          => 'max:2',
        Entity::BANK_BENEFICIARY_PIN            => 'max:15',
        Entity::WEBSITE_ABOUT                   => 'max:255|url',
        Entity::WEBSITE_CONTACT                 => 'max:255|url',
        Entity::WEBSITE_PRIVACY                 => 'max:255|url',
        Entity::WEBSITE_TERMS                   => 'max:255|url',
        Entity::WEBSITE_REFUND                  => 'max:255|url',
        Entity::WEBSITE_PRICING                 => 'max:255|url',
        Entity::WEBSITE_LOGIN                   => 'max:255|url',
        Entity::BUSINESS_PROOF_URL              => 'file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::BUSINESS_OPERATION_PROOF_URL    => 'file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::BUSINESS_PAN_URL                => 'file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::ADDRESS_PROOF_URL               => 'file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::PROMOTER_PROOF_URL              => 'file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::PROMOTER_PAN_URL                => 'file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::PROMOTER_ADDRESS_URL            => 'file|mimes:pdf,jpeg,jpg,png,zip',
        Entity::TRANSACTION_REPORT_EMAIL        => 'email|max:255',
        Entity::SUBMIT                          => 'sometimes',
    ];

    public function validateParams(array $input)
    {
        $rules = [];

        foreach ($input as $key => $val)
        {
            if (empty($this->allInputRules[$key]) === false)
            {
                $rules[$key] = $this->allInputRules[$key];
            }
        }

        $this->registerCustomValidator();

        $validator = LaravelValidator::make($input, $rules);

        if ($validator->fails())
        {
            $this->processValidationFailure($validator->messages(), 'edit', $input);
        }
    }

    protected function registerCustomValidator()
    {
        LaravelValidator::extend('custom_ifsc_validator', function($attribute, $value, $parameters)
        {
            return IFSC::validate($value);

        }, self::INVALID_IFSC_CODE_MESSAGE);
    }
}

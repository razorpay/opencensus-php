<?php

namespace Models\Merchant\BankAccount;

use EE\Exception;
use Razorpay\IFSC\IFSC;
use Models\Base;
use Illuminate\Support\MessageBag;

class Validator extends Base\Validator
{

    const INVALID_IFSC_CODE_MESSAGE = 'Invalid IFSC Code';

    protected static $addBankAccountRules = array(
        'ifsc_code'             => 'required|alpha_num|size:11',
        'account_number'        => 'required|alpha_num|between:5,20',
        'beneficiary_name'      => 'required|min:4|max:40|alpha_space_num',
        'beneficiary_address1'  => 'required|max:30',
        'beneficiary_address2'  => 'sometimes|max:30',
        'beneficiary_address3'  => 'sometimes|max:30',
        'beneficiary_address4'  => 'sometimes|max:30',
        'beneficiary_city'      => 'required|max:30',
        'beneficiary_state'     => 'required|max:2',
        'beneficiary_pin'       => 'required|integer|digits:6',
        'beneficiary_country'   => 'sometimes|in:IN',
        'beneficiary_email'     => 'required|email',
        'beneficiary_mobile'    => 'required|numeric|digits_between:10,11',
    );

    protected static $addBankAccountValidators = array(
        'ifsc_code',
        'beneficiary_state');

    protected static $beneficiaryStateCodes = array(
        'AN', 'AP', 'AR', 'AS', 'BI', 'CH', 'CT', 'DN',
        'DD', 'GO', 'GJ', 'HA', 'HP', 'JK', 'JH', 'KA',
        'KE', 'MP', 'MH', 'MA', 'ME', 'MI', 'NA', 'DL',
        'OR', 'PO', 'PB', 'RJ', 'SK', 'TG', 'TN', 'TR',
        'UP', 'UT', 'WB');

    protected function validateBeneficiaryState($input)
    {
        $state = $input['beneficiary_state'];

        if (in_array($state, self::$beneficiaryStateCodes) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid state code');
        }
    }

    protected function validateIfscCode($input)
    {
        $ifsc = $input['ifsc_code'];

        $ifsc = strtoupper($ifsc);

        if (!IFSC::validate($ifsc))
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invalid IFSC Code in Bank Account");
        }
    }
}

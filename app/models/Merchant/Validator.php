<?php

namespace Models\Merchant;

use EE\Exception;
use Models\Base;
use Models\Payment\Processor\NetBanking;
use Illuminate\Support\MessageBag;

class Validator extends Base\Validator
{
    protected static $createRules = array(
    	'id'    =>  'required|alpha_num|size:14',
        'name'  =>	'required|alpha_space|max:200',
        'email' =>	'required|email|unique:merchants'
    );

    protected static $addBankAccountRules = array(
        'ifsc_code'             => 'required|alpha_num|size:11',
        'account_number'        => 'required|numeric|digits_between:5,20',
        'beneficiary_name'      => 'required|min:4|max:40|alpha_space',
        'beneficiary_address1'  => 'required|max:30',
        'beneficiary_address2'  => 'sometimes|max:30',
        'beneficiary_address3'  => 'sometimes|max:30',
        'beneficiary_address4'  => 'sometimes|max:30',
        'beneficiary_city'      => 'required|max:30',
        'beneficiary_state'     => 'required|max:2',
        'beneficiary_pin'       => 'required|integer|digits:6',
        'beneficiary_country'   => 'sometimes|in:IN',
        'beneficiary_email'     => 'required|email',
        'beneficiary_mobile'    => 'required|numeric|digits:10',
    );

    protected static $addBankAccountValidators = array(
        'ifsc_code',
        'beneficiary_state');

    protected static $beneficiaryStateCodes = array(
        'AN', 'AP', 'AR', 'AS', 'BI', 'CH', 'CT', 'DN',
        'DD', 'GO', 'GJ', 'HA', 'HP', 'JK', 'JH', 'KA',
        'KE', 'MP', 'MH', 'MA', 'ME', 'MI', 'NA', 'DL',
        'OR', 'PO', 'PB', 'RJ', 'SK', 'TN', 'TR', 'UP',
        'UT', 'WB');

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

        $message = null;

        if (ctype_upper(substr($ifsc, 0, 4)) === false)
        {
            $message = 'First four letters of ifsc_code must be alphabets';
        }

        if ($ifsc[4] !== '0')
        {
            $message = 'IFSC code fifth letter must be 0';
        }

        if ($message !== null)
        {
            $messages = new MessageBag;
            $messages->add('ifsc_code', $message);

            $this->processValidationFailure($messages, 'validateBankAccountInput', $input);
        }
    }
}

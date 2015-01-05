<?php

namespace Models\Merchant;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
    	'id'    =>  'required|alpha_num|size:14',
        'name'  =>	'required|alpha_space|max:200',
        'email' =>	'required|email|unique:merchants'
    );

    protected static $addBankAccountRules = array(
        'ifsc_code'         => 'required|alpha_num|size:11',
        'account_number'    => 'required',
        'beneficiary_name'  => 'required',
    );

    protected static $addBankAccountValidators = array(
        'ifsc_code');

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

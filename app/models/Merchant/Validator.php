<?php

namespace Models\Merchant;

use Models\Base;
use Models\Payment\Processor\NetBanking;

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

    protected static $addBanksRules = array(
        'banks' => 'required|array');

    protected static $addBanksValidators = array(
        'banks');

    protected function validateBanks(array $input)
    {
        $banks = $input['banks'];

        $unsupported = NetBanking::findUnsupportedBanks($banks);

        if (count($unsupported) !== 0)
        {
            $msg = implode(', ', $unsupported) . ' are either invalid or unsupported banks';

            throw new Exception\BadRequestValidationFailureException(
                $msg, 'banks');
        }

        $uniqBanks = array_unique($banks);

        if (count($banks) !== count($uniqBanks))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Some banks are repeated');
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

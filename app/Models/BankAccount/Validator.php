<?php

namespace RZP\Models\BankAccount;

use RZP\Base;
use RZP\Constants\Mode;
use RZP\Exception;
use Razorpay\IFSC\IFSC;
use Illuminate\Support\MessageBag;

class Validator extends Base\Validator
{
    const INVALID_IFSC_CODE_MESSAGE = 'Invalid IFSC Code in Bank Account';

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
        'beneficiary_state');

    protected static $beneficiaryStateCodes = array(
        'AN', 'AP', 'AR', 'AS', 'BI', 'CH', 'CT', 'DN',
        'DD', 'GO', 'GJ', 'HA', 'HP', 'JK', 'JH', 'KA',
        'KE', 'LD', 'MP', 'MH', 'MA', 'ME', 'MI', 'NA',
        'DL', 'OR', 'PO', 'PB', 'RJ', 'SK', 'TG', 'TN',
        'TR', 'UP', 'UT', 'WB');

    protected function validateBeneficiaryState($input)
    {
        $state = $input['beneficiary_state'];

        if (in_array($state, self::$beneficiaryStateCodes) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid state code');
        }
    }

    public function validateIfscCode($mode)
    {
        $ifsc = $this->entity->getIfscCode();

        $ifsc = strtoupper($ifsc);

        // We allow a special IFSC code to pass through
        if ($this->isSpecialIfscCode($ifsc, $mode))
        {
            return;
        }

        if (!IFSC::validate($ifsc))
        {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_IFSC_CODE_MESSAGE);
        }
    }

    /**
     * We allow using the special IFSC code only
     * for the test mode
     * @param  string  $ifsc IFSC code, uppercase
     */
    protected function isSpecialIfscCode($ifsc, $mode)
    {
        return (($mode === Mode::TEST) and
                ($ifsc === Entity::SPECIAL_IFSC_CODE));
    }
}

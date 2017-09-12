<?php

namespace RZP\Models\BankAccount;

use Razorpay\IFSC\IFSC;
use RZP\Base;
use RZP\Constants\Mode;
use RZP\Exception;

class Validator extends Base\Validator
{
    const INVALID_IFSC_CODE_MESSAGE = 'Invalid IFSC Code in Bank Account';

    protected static $addBankAccountRules = [
        Entity::IFSC_CODE              => 'required|alpha_num|size:11',
        Entity::ACCOUNT_NUMBER         => 'required|alpha_num|between:5,20',
        Entity::BENEFICIARY_NAME       => 'required|between:4,40|alpha_space_num',
        Entity::BENEFICIARY_ADDRESS1   => 'required|max:30',
        Entity::BENEFICIARY_ADDRESS2   => 'sometimes|max:30',
        Entity::BENEFICIARY_ADDRESS3   => 'sometimes|max:30',
        Entity::BENEFICIARY_ADDRESS4   => 'sometimes|max:30',
        Entity::MOBILE_BANKING_ENABLED => 'sometimes|in:0,1',
        Entity::MPIN                   => 'sometimes|max:6',
        Entity::BENEFICIARY_CITY       => 'required|max:30',
        Entity::BENEFICIARY_STATE      => 'required|max:2',
        Entity::BENEFICIARY_PIN        => 'required|integer|digits:6',
        Entity::BENEFICIARY_COUNTRY    => 'sometimes|in:IN',
        Entity::BENEFICIARY_EMAIL      => 'required|email',
        Entity::BENEFICIARY_MOBILE     => 'required|numeric|digits_between:10,12',
    ];

    protected static $addVirtualBankAccountRules = [
        Entity::IFSC_CODE             => 'sometimes|alpha_num|nullable',
        Entity::ACCOUNT_NUMBER        => 'required|alpha_num|between:5,20',
        Entity::BENEFICIARY_NAME      => 'required|max:40|alpha_space_num',
    ];

    protected static $addBankAccountValidators = [
        Entity::BENEFICIARY_STATE
    ];

    protected static $beneficiaryStateCodes = [
        'AN', 'AP', 'AR', 'AS', 'BI', 'CH', 'CT', 'DN',
        'DD', 'GO', 'GJ', 'HA', 'HP', 'JK', 'JH', 'KA',
        'KE', 'LD', 'MP', 'MH', 'MA', 'ME', 'MI', 'NA',
        'DL', 'OR', 'PO', 'PB', 'RJ', 'SK', 'TG', 'TN',
        'TR', 'UP', 'UT', 'WB'
    ];

    protected function validateBeneficiaryState($input)
    {
        if (in_array($input[Entity::BENEFICIARY_STATE], self::$beneficiaryStateCodes, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid state code');
        }
    }

    public function validateIfscCode($mode = 'test')
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
        return ((($mode === Mode::TEST) or ($mode === null)) and
                (($ifsc === Entity::SPECIAL_IFSC_CODE) or
                 ($ifsc === 'RAZR0000001')));
    }
}

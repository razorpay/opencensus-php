<?php

namespace RZP\Services\Settlements;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Contact\Validator as fundAccountValidator;
use RZP\Models\Currency\Currency;

class Validator extends Base\Validator
{
    /**
     * @var string[] validation rules for migrating a bank account
     * along with merchant migration (eased up validation checks compared to
     * $createBankAccountRules to allow smooth bank account migrations)
     * this rule is only used by settlement service so changing it.
     */
    protected static $createBankAccountRules = [
        'org_id'             =>  'required_if:type,==,org|string|size:14',
        'merchant_id'         => 'required_if:type,!=,org|string|size:14',
        'account_number'      => 'required|string',
        'account_type'        => 'required|in:current,saving,nodal',
        'ifsc_code'           => 'required|string',
        'beneficiary_name'    => 'required|min:4|custom',
        'beneficiary_address' => 'sometimes|string',
        'beneficiary_city'    => 'sometimes|string',
        'beneficiary_state'   => 'sometimes|string',
        'beneficiary_country' => 'sometimes|string',
        'beneficiary_email'   => 'sometimes|string',
        'beneficiary_mobile'  => 'sometimes|alpha_num',
        'accepted_currency'   => 'required|string|size:3|custom',
        'extra_info'          => 'required|array',
        'extra_info.via'      => 'required|in:payout'
    ];

    protected function validateBeneficiaryName($attribute, $value)
    {
        $trimmedName = substr(trim($value), 0, 40);

        $match = preg_match(fundAccountValidator::NAME_REGEX, $trimmedName);

        if ($match !== 1)
        {
            throw new BadRequestValidationFailureException(
                'The beneficiary name field is invalid',
                'beneficiary_name');
        }
    }

    protected function validateAcceptedCurrency($attribute, $currency)
    {

        if (in_array($currency, Currency::SUPPORTED_CURRENCIES, true) === false)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED,
                'currency');
        }
    }
}

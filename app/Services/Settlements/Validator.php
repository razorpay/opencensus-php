<?php

namespace RZP\Services\Settlements;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Contact\Validator as fundAccountValidator;

class Validator extends Base\Validator
{
    protected static $createBankAccountRules = [
        'merchant_id'         => 'required|string|size:14',
        'account_number'      => 'required|alpha_num',
        'account_type'        => 'required|in:current,saving,nodal',
        'ifsc_code'           => 'required|string',
        'beneficiary_name'    => 'required|min:4|custom',
        'beneficiary_address' => 'sometimes|string',
        'beneficiary_city'    => 'sometimes|string',
        'beneficiary_state'   => 'sometimes|string',
        'beneficiary_country' => 'sometimes|string',
        'beneficiary_email'   => 'sometimes|string',
        'beneficiary_mobile'  => 'sometimes|alpha_num',
        'accepted_currency'   => 'required|in:INR',
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
}

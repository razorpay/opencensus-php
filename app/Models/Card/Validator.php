<?php

namespace RZP\Models\Card;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NUMBER             => 'required|numeric|luhn|digits_between:12,19',
        Entity::EXPIRY_MONTH       => 'sometimes|integer|digits_between:1,2|max:12|min:0',
        Entity::EXPIRY_YEAR        => 'required|integer|digits:4|non_past_year',
        Entity::CVV                => 'sometimes|numeric|digits_between:3,4|nullable',
        Entity::NAME               => 'sometimes|regex:(^[a-zA-Z.\- 0-9\']+$)|max:100',
        Entity::VAULT              => 'sometimes|string|in:tokenex,rzpvault,rzpencryption,mastercard,visa,rupay,amex,hdfc,axis',
        Entity::INTERNATIONAL      => 'sometimes',
        Entity::IS_CVV_OPTIONAL    => 'sometimes|boolean',
        Entity::IS_TOKENIZED_CARD  => 'sometimes|boolean',
        Entity::TOKENISED          => 'sometimes|boolean',
        Entity::CRYPTOGRAM_VALUE   => 'sometimes|string|nullable',
        Entity::TOKEN_PROVIDER     => 'sometimes|string',
        Entity::TOKEN_EXPIRY_MONTH => 'sometimes|integer|digits_between:1,2|max:12|min:0|nullable',
        Entity::TOKEN_EXPIRY_YEAR  => 'sometimes|integer|digits:4|non_past_year|nullable',
        Entity::LAST4              => 'sometimes|numeric|digits:4',
        Entity::TOKEN              => 'sometimes|string',
    ];

    protected static $createCpsRequestRules = [
        Entity::VAULT_TOKEN        => 'required|string',
        Entity::GLOBAL_FINGERPRINT => 'sometimes|string',
        Entity::EXPIRY_MONTH       => 'required|integer|digits_between:1,2|max:12|min:1',
        Entity::EXPIRY_YEAR        => 'required|integer|digits:4|non_past_year',
        Entity::IIN                => 'required|numeric|digits:6',
        Entity::NAME               => 'sometimes|regex:(^[a-zA-Z.\- 0-9\']+$)|max:100',
        Entity::VAULT              => 'sometimes|string|in:tokenex,rzpvault,rzpencryption',
        Entity::INTERNATIONAL      => 'sometimes',
    ];
    protected static $editRules = [
        Entity::NUMBER             => 'required|numeric|luhn|digits_between:12,19',
        Entity::CVV                => 'sometimes|numeric|digits_between:3,4|nullable',
        Entity::NAME               => 'sometimes|alpha_space|max:100',
        Entity::VAULT_TOKEN        => 'sometimes|string',
        Entity::VAULT              => 'required_with:vault_token|in:tokenex,rzpvault,rzpencryption',
        Entity::INTERNATIONAL      => 'sometimes',
    ];

    protected static $tokenizedCardRules = [
        Entity::NAME               => 'sometimes|alpha_space|max:100',
        Entity::VAULT_TOKEN        => 'sometimes|string',
        Entity::GLOBAL_FINGERPRINT => 'sometimes|string',
        Entity::VAULT              => 'required_with:vault_token|in:tokenex,rzpvault,rzpencryption,mastercard,visa,rupay,amex,hdfc,axis,providers',
        Entity::IIN                => 'required|numeric|digits:6',
        Entity::TOKEN_IIN          => 'required|numeric|digits:9',
        Entity::EXPIRY_MONTH       => 'required|integer|digits_between:1,2|max:12|min:0',
        Entity::EXPIRY_YEAR        => 'required|integer|digits:4|non_past_year',
        Entity::LAST4              => 'required|numeric|digits:4',
        Entity::LENGTH             => 'required|integer',
        Entity::TOKEN_EXPIRY_MONTH => 'sometimes|integer|digits_between:1,2|max:12|min:0',
        Entity::TOKEN_EXPIRY_YEAR  => 'required|integer|digits:4|non_past_year',
    ];

    protected static $fetchCryptogramProviderDataRules = [
        Entity::TOKEN_NUMBER       => 'required|numeric|luhn|digits_between:12,19',
        Entity::CRYPTOGRAM_VALUE   => 'sometimes|string|nullable',
        Entity::TOKEN_EXPIRY_MONTH => 'sometimes|integer|digits_between:1,2|max:12|min:0|nullable',
        Entity::TOKEN_EXPIRY_YEAR  => 'sometimes|integer|digits:4|non_past_year|nullable',
    ];

    protected static $recurringRules = [
        Entity::IIN                => 'required|numeric|digits:6'
    ];

    protected static $tokenMigrationRules = [
        'limit'              => 'sometimes|numeric',
    ];

    protected static $createValidators = [
        'expiry_date'
    ];

    protected static $createVaultTokenRules = [
        'namespace'    => 'required|max:30|in:nodal_certs,banking_account_creds',
        'secret'       => 'required',
        'bu_namespace' => 'required|in:razorpayx_nodal_certs'
    ];

    protected function validateExpiryDate($input)
    {
        $month = $input[Entity::EXPIRY_MONTH];
        $year = $input[Entity::EXPIRY_YEAR];

        $currentMonth = date('n');
        $currentYear = (int) date('Y');

        if (($month < $currentMonth) and
            ($year <= $currentYear))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_INVALID_EXPIRY_DATE);
        }
    }
}

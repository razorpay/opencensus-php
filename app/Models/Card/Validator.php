<?php

namespace RZP\Models\Card;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NUMBER             => 'required|numeric|luhn|digits_between:12,19',
        Entity::EXPIRY_MONTH       => 'required|integer|digits_between:1,2|max:12|min:1',
        Entity::EXPIRY_YEAR        => 'required|integer|digits:4|non_past_year',
        Entity::CVV                => 'sometimes|numeric|digits_between:3,4|nullable',
        Entity::NAME               => 'sometimes|regex:(^[a-zA-Z.\- 0-9\']+$)|max:100',
        Entity::VAULT              => 'sometimes|string|in:tokenex,rzpvault,rzpencryption,MC,Visa',
        Entity::INTERNATIONAL      => 'sometimes',
        Entity::IS_CVV_OPTIONAL    => 'sometimes|boolean',
        Entity::IS_TOKENIZED_CARD  => 'sometimes|boolean',
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
        Entity::VAULT              => 'required_with:vault_token|in:tokenex,rzpvault,rzpencryption,MC,Visa',
        Entity::IIN                => 'required|numeric|digits:6',
        Entity::EXPIRY_MONTH       => 'required|integer|digits_between:1,2|max:12|min:1',
        Entity::EXPIRY_YEAR        => 'required|integer|digits:4|non_past_year',
        Entity::LAST4              => 'required|integer|digits:4|non_past_year',
        Entity::LENGTH             => 'required|integer',
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
        'namespace' => 'required|max:30|in:nodal_certs,banking_account_creds',
        'secret'    => 'required'
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

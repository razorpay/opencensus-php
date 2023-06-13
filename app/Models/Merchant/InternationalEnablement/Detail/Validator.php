<?php

namespace RZP\Models\Merchant\InternationalEnablement\Detail;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Currency\Currency;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    public static $createRules      = [];
    public static $createValidators = [];

    public static $createForDraftRules = [
        Entity::GOODS_TYPE                          => 'nullable|sometimes|string|in:' . Constants::GOODS_TYPE_VALIDATOR_CSV,
        Entity::BUSINESS_USE_CASE                   => 'nullable|sometimes|string|min:50|max:1000',
        Entity::ALLOWED_CURRENCIES                  => 'nullable|sometimes|array|between:1,100|custom',
        Entity::ALLOWED_CURRENCIES . '.*'           => 'sometimes|string|size:3',
        Entity::MONTHLY_SALES_INTL_CARDS_MIN        => 'nullable|sometimes|integer|min:0',
        Entity::MONTHLY_SALES_INTL_CARDS_MAX        => 'nullable|required_with:monthly_sales_intl_cards_min|integer',
        Entity::BUSINESS_TXN_SIZE_MIN               => 'nullable|sometimes|integer|min:0',
        Entity::BUSINESS_TXN_SIZE_MAX               => 'nullable|required_with:business_txn_size_min|integer',
        Entity::LOGISTIC_PARTNERS                   => 'nullable|sometimes|string|min:1|max:500',

        Entity::ABOUT_US_LINK                       => 'nullable|sometimes|active_url',
        Entity::CONTACT_US_LINK                     => 'nullable|sometimes|active_url',
        Entity::TERMS_AND_CONDITIONS_LINK           => 'nullable|sometimes|active_url',
        Entity::PRIVACY_POLICY_LINK                 => 'nullable|sometimes|active_url',
        Entity::REFUND_AND_CANCELLATION_POLICY_LINK => 'nullable|sometimes|active_url',
        Entity::SHIPPING_POLICY_LINK                => 'nullable|sometimes|active_url',
        Entity::SOCIAL_MEDIA_PAGE_LINK              => 'nullable|sometimes|active_url',

        Entity::EXISTING_RISK_CHECKS                => 'nullable|sometimes|array|between:1,10',
        Entity::EXISTING_RISK_CHECKS . '.*'         => 'sometimes|string|min:1|max:500',
        Entity::CUSTOMER_INFO_COLLECTED             => 'nullable|sometimes|array|between:1,10',
        Entity::CUSTOMER_INFO_COLLECTED . '.*'      => 'sometimes|string|min:1|max:300',
        Entity::PARTNER_DETAILS_PLUGINS             => 'nullable|sometimes|array|between:1,15',
        Entity::PARTNER_DETAILS_PLUGINS . '.*'      => 'sometimes|string|min:1|max:300',

        Entity::ACCEPTS_INTL_TXNS                   => 'sometimes|boolean',
        Entity::IMPORT_EXPORT_CODE                  => 'nullable|sometimes|alpha_num|size:10',
        Entity::PRODUCTS                            => 'sometimes|array|between:0,4',
        Entity::PRODUCTS . '.*'                     => 'required_with:' . Entity::PRODUCTS . '|string|in:' . Constants::PRODUCTS_VALIDATOR_CSV,
        'documents'                                 => 'nullable|sometimes|array',
    ];

    public static $createForSubmitRules = [
        Entity::GOODS_TYPE                          => 'required|string|in:' . Constants::GOODS_TYPE_VALIDATOR_CSV,
        Entity::BUSINESS_USE_CASE                   => 'required|string|min:50|max:1000',
        Entity::ALLOWED_CURRENCIES                  => 'nullable|sometimes|array|between:1,100|custom',
        Entity::ALLOWED_CURRENCIES . '.*'           => 'nullable|string|size:3',
        Entity::MONTHLY_SALES_INTL_CARDS_MIN        => 'nullable|sometimes|integer|min:0',
        Entity::MONTHLY_SALES_INTL_CARDS_MAX        => 'nullable|required_with:monthly_sales_intl_cards_min|integer',
        Entity::BUSINESS_TXN_SIZE_MIN               => 'nullable|sometimes|integer|min:0',
        Entity::BUSINESS_TXN_SIZE_MAX               => 'nullable|required_with:business_txn_size_min|integer',
        Entity::LOGISTIC_PARTNERS                   => 'nullable|string|min:1|max:500',

        Entity::ABOUT_US_LINK                       => 'nullable|active_url',
        Entity::CONTACT_US_LINK                     => 'nullable|active_url',
        Entity::TERMS_AND_CONDITIONS_LINK           => 'nullable|active_url',
        Entity::PRIVACY_POLICY_LINK                 => 'nullable|active_url',
        Entity::REFUND_AND_CANCELLATION_POLICY_LINK => 'nullable|active_url',
        Entity::SHIPPING_POLICY_LINK                => 'nullable|active_url',
        Entity::SOCIAL_MEDIA_PAGE_LINK              => 'nullable|sometimes|active_url',

        Entity::EXISTING_RISK_CHECKS                => 'required|array|between:1,10',
        Entity::EXISTING_RISK_CHECKS . '.*'         => 'required|string|min:1|max:500',
        Entity::CUSTOMER_INFO_COLLECTED             => 'nullable|array|between:1,10',
        Entity::CUSTOMER_INFO_COLLECTED . '.*'      => 'nullable|string|min:1|max:300',
        Entity::PARTNER_DETAILS_PLUGINS             => 'nullable|array|between:1,15',
        Entity::PARTNER_DETAILS_PLUGINS . '.*'      => 'nullable|string|min:1|max:300',

        Entity::ACCEPTS_INTL_TXNS                   => 'required|boolean',
        Entity::IMPORT_EXPORT_CODE                  => 'nullable|sometimes|alpha_num|size:10',
        Entity::PRODUCTS                            => 'required|array|filled|between:1,4',
        Entity::PRODUCTS . '.*'                     => 'required|string|in:' . Constants::PRODUCTS_VALIDATOR_CSV,
        'documents'                                 => 'nullable|sometimes|array',
    ];

    public static $createForDraftValidators = [
        'monthly_sales_intl_cards_range',
        'business_txn_size_range',
    ];

    public static $createForSubmitValidators = [
        'monthly_sales_intl_cards_range',
        'business_txn_size_range',
    ];

    public function validateAllowedCurrencies($attribute, $arrayOfCurrencies)
    {
        // handling 'sometimes'
        if (is_sequential_array($arrayOfCurrencies) === false)
        {
            return;
        }

        foreach ($arrayOfCurrencies as $currency)
        {
            if (Currency::isSupportedCurrency($currency) === false)
            {
                $errorMessage = sprintf('Not a valid currency: (%s)', $currency);

                $errors[Entity::ALLOWED_CURRENCIES][] = $errorMessage;
                $errors['internal_error_code'] = ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE;

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
                    null,
                    $errors);
            }
        }
    }

    protected function validateMonthlySalesIntlCardsRange(array $input)
    {
        if (isset($input[Entity::MONTHLY_SALES_INTL_CARDS_MIN]) === false)
        {
            return;
        }

        $minVal = $input[Entity::MONTHLY_SALES_INTL_CARDS_MIN];

        $maxVal = $input[Entity::MONTHLY_SALES_INTL_CARDS_MAX];

        if (($maxVal != Constants::MAX_VALUE_IDENTIFIER) && ($minVal > $maxVal))
        {
            $errorMessage = sprintf('Not a valid range: (%d, %d)', $minVal, $maxVal);

            $errors[Entity::MONTHLY_SALES_INTL_CARDS_MIN][] = $errorMessage;
            $errors['internal_error_code'] = ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE;

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
                null,
                $errors);
        }
    }

    protected function validateBusinessTxnSizeRange(array $input)
    {
        if (isset($input[Entity::BUSINESS_TXN_SIZE_MIN]) === false)
        {
            return;
        }

        $minVal = $input[Entity::BUSINESS_TXN_SIZE_MIN];

        $maxVal = $input[Entity::BUSINESS_TXN_SIZE_MAX];

        if (($maxVal != Constants::MAX_VALUE_IDENTIFIER) && ($minVal > $maxVal))
        {
            $errorMessage = sprintf('Not a valid range: (%d, %d)', $minVal, $maxVal);

            $errors[Entity::BUSINESS_TXN_SIZE_MIN][] = $errorMessage;
            $errors['internal_error_code'] = ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE;

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
                null,
                $errors);
        }
    }

    protected function processValidationFailure($messages, $operation, $input)
    {
        $errors = $messages->toArray();

        $errors['internal_error_code'] =
            ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE;

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
            null,
            $errors);
    }
}

<?php

namespace RZP\Models\FundAccount;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Vpa;
use RZP\Models\Card;
use RZP\Models\Feature;
use RZP\Models\BankAccount;
use RZP\Models\Card\Network;
use RZP\Models\WalletAccount;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Validator
 *
 * @package RZP\Models\FundAccount
 */
class Validator extends Base\Validator
{
    const BEFORE_CREATE = 'before_create';
    const PUBLIC_CREATE = 'public_create';

    /**
     * 1lac in paise
     */
    const MAX_UPI_AMOUNT = 10000000;

    /**
     * Rate limit on items sending for bulk fund_account create.
     */
    const MAX_BULK_FUND_ACCOUNT_LIMIT = 15;

    /**
     * Rs 10k in paise
     */
    const MAX_WALLET_ACCOUNT_AMAZON_PAY_AMOUNT = 1000000;

    protected static $createRules = [
        Entity::CUSTOMER_ID                              => 'sometimes|public_id',
        Entity::CONTACT_ID                               => 'sometimes|public_id',
        Entity::ACCOUNT_TYPE                             => 'required|string|custom',
        Entity::VPA                                      => 'filled|associative_array|custom',
        Entity::BANK_ACCOUNT                             => 'filled|associative_array|custom',
        Entity::CARD                                     => 'filled|associative_array|custom',
        Entity::WALLET_ACCOUNT                           => 'filled|associative_array|custom',
        // This is required to even create the card because we need to fill a
        // dummy cvv and that requires network and that requires card number.
        // The other card details are validated as part of card creation.
        Entity::CARD . '.' . Card\Entity::NUMBER         => 'sometimes:card|numeric|luhn|digits_between:12,19',
        Entity::CARD . '.' . Card\Entity::NAME           => 'sometimes:card|regex:([a-zA-Z-.\' ]+$)|max:100',
        Entity::IDEMPOTENCY_KEY                          => 'sometimes|string',
        //Validation if vault token is received for payout creation
        //If card number is not present then vault token must be there
        Entity::CARD . '.' . Card\Entity::TOKEN          => 'sometimes:card|string',
        Entity::CARD . '.' . Card\Entity::INPUT_TYPE     => 'sometimes:card|string|in:razorpay_token,service_provider_token,card',
        Entity::CARD . '.' . Card\Entity::TOKEN_ID       => 'sometimes:card|public_id',
        Entity::CARD . '.' . Card\Entity::TOKEN_PROVIDER => 'sometimes:card|string',
        // These parameters are needed for scrooge use case for detokenisation of international and
        // bajaj finserv cards
        Entity::CARD . '.' . Card\Entity::NETWORK        => 'sometimes:card|string',
        Entity::CARD . '.' . Card\Entity::INTERNATIONAL  => 'sometimes:card|bool',
        Entity::CARD . '.' . Card\Entity::TRIVIA         => 'sometimes:card|string|nullable',
        Entity::BATCH_ID                                 => 'sometimes|string',
    ];

    protected static $beforeCreateRules = [
        Entity::CONTACT_ID  => 'required_without:customer_id|public_id',
        Entity::CUSTOMER_ID => 'required_without:contact_id|public_id',
    ];

    protected static $editRules = [
        Entity::ACTIVE => 'filled|boolean',
    ];

    protected static $createValidators = [
        'accountAttribute'
    ];

    public function validateAccountType($attribute, $value)
    {
        Type::validateType($value);
    }

    protected function validateAccountAttribute($input)
    {
        // Only one of card, vpa, bank_account or wallet_account can be present.

        $correctPresence = ((isset($input[Entity::CARD]) === true) xor
                            (isset($input[Entity::VPA]) === true) xor
                            (isset($input[Entity::BANK_ACCOUNT]) === true) xor
                            (isset($input[Entity::WALLET_ACCOUNT]) === true));

        if ($correctPresence === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Only one of card, vpa, bank_account or wallet can be present',
                null,
                [
                    'input' => $input,
                ]);
        }

        if (isset($input[$input[Entity::ACCOUNT_TYPE]]) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Account type doesn\'t match the details provided');
        }
    }

    protected function validateCard($attribute, $value)
    {
        if (empty($value) === true)
        {
            return;
        }

        if (empty($this->entity) === true)
        {
            return;
        }

        /** @var Entity $fundAccount */
        $fundAccount = $this->entity;

        $merchant = $fundAccount->merchant;

        //
        // The merchant needs to be PCI-DSS compliant to send card information.
        //
        // On public auth, it's fine, since the merchant would be sending the card information
        // through their frontend itself and the card details don't go through their server.
        // In case of non-public auth, the card details might go through their servers and hence
        // S2S feature needs to be enabled to ensure that the the merchant is PCI-DSS compliant.
        //
        // But, of course, it's possible that the merchant takes the card details onto their server
        // and makes a public auth API call to us from server. Nothing that we can do about it.
        //
        if ((app('basicauth')->isPublicAuth() === false) and
            ($merchant->isFeatureEnabled(Feature\Constants::S2S) === false))
        {
            // Not logging the value since card details will be present.
            throw new Exception\BadRequestValidationFailureException(
                'card is/are not required and should not be sent',
                null,
                [
                    'message' => 's2s feature not enabled',
                ]);
        }

        if ($merchant->isFeatureEnabled(Feature\Constants::PAYOUT_TO_CARDS) === false)
        {
            // Not logging the value since card details will be present.
            throw new Exception\BadRequestValidationFailureException(
                'card is/are not required and should not be sent',
                null,
                [
                    'message' => 'payout_to_cards feature not enabled',
                ]);
        }

        $this->validateExclusiveFieldsForCard($attribute, $value);

        $this->validateInputTypeForCard($attribute, $value);

        if (isset($value[Card\Entity::NETWORK]) === true)
        {
            $this->validateCardNetwork($value[Card\Entity::NETWORK]);
        }
    }

    public function validateExclusiveFieldsForCard($attribute, $value)
    {
        //Validating here if token and card number both has been received in the request.
        //There shall be either of them.
        //Validating it here as could not find any inbuilt validator for the use case.
        if ((isset($value[Card\Entity::NUMBER]) === true) and
            (isset($value[Card\Entity::TOKEN]) === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'both card.token and card.number should not be sent'
            );
        }

        //Validating here if token_id and card number both has been received in the request.
        //There shall be either of them.
        if ((isset($value[Card\Entity::NUMBER]) === true) and
            (isset($value[Card\Entity::TOKEN_ID]) === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'both card.token_id and card.number should not be sent'
            );
        }

        //Validating here if token_id and token both has been received in the request.
        //There shall be either of them.
        if ((isset($value[Card\Entity::TOKEN]) === true) and
            (isset($value[Card\Entity::TOKEN_ID]) === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'both card.token_id and card.token should not be sent'
            );
        }

        if (((isset($value[Card\Entity::INTERNATIONAL]) === true) or
             (isset($value[Card\Entity::NETWORK]) === true) or
             (isset($value[Card\Entity::TRIVIA]) === true)) and
            (isset($value[Card\Entity::TOKEN]) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'card.token should be sent if card.network/card.international/card.trivia is passed.'
            );
        }
    }

    public function validateInputTypeForCard($attribute, $value)
    {
        if ((isset($value[Card\Entity::INPUT_TYPE]) === true) and
            (app('basicauth')->isScroogeApp() === false))
        {
            $inputType = $value[Card\Entity::INPUT_TYPE];

            if ((in_array($inputType, [Card\InputType::SERVICE_PROVIDER_TOKEN, Card\InputType::CARD]) === true) and
                (isset($value[Card\Entity::NUMBER]) === false))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'card.number should be sent for input type as ' . $inputType
                );
            }

            if (($inputType === Card\InputType::RAZORPAY_TOKEN) and
                (isset($value[Card\Entity::TOKEN_ID]) === false))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'card.token_id should be sent for input type as ' . $inputType
                );
            }

            if ((in_array($inputType, [Card\InputType::SERVICE_PROVIDER_TOKEN, Card\InputType::RAZORPAY_TOKEN]) === true) and
                (isset($value[Card\Entity::TOKEN_PROVIDER]) === false))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'card.token_provider should be sent for input type as ' . $inputType
                );
            }

            $isExpiryMonthOrYearNotSet = ((isset($value[Card\Entity::EXPIRY_YEAR]) === false) or
                                          (isset($value[Card\Entity::EXPIRY_MONTH]) === false));

            if ((in_array($inputType, [Card\InputType::SERVICE_PROVIDER_TOKEN]) === true) and
                ($isExpiryMonthOrYearNotSet === true))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'card.expiry_year and card.expiry_month are mandatory fields when input type is sent as ' . $inputType
                );
            }
        }
        else
        {
            if (isset($value[Card\Entity::TOKEN_ID]) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'card.input_type should be sent along with card.token_id'
                );
            }
        }
    }

    public function validateCardNetwork($network)
    {
        if (Network::isValidNetworkCode($network) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $network . ' is not a valid network'
            );
        }
    }

    /**
     * @param array $input
     * Rate limit on number of fund account creation in Bulk Route
     * @throws BadRequestValidationFailureException
     */
    public function validateBulkFundAccountCount(array $input)
    {
        if (count($input) > self::MAX_BULK_FUND_ACCOUNT_LIMIT)
        {
            throw new BadRequestValidationFailureException(
                'Current batch size ' . count($input) . ', max limit of Bulk Fund Account is ' . self::MAX_BULK_FUND_ACCOUNT_LIMIT,
                null,
                null
            );
        }
    }

    public function validateVpa($attribute, $value)
    {
        (new Vpa\Validator())->setStrictFalse()->validateInput('create', $value);
    }

    public function validateBankAccount($attribute, $value)
    {
        (new BankAccount\Validator())->setStrictFalse()->validateInput('addFundAccountBankAccount', $value);
    }

    public function validateWalletAccount($attribute, $value)
    {
        (new WalletAccount\Validator())->setStrictFalse()->validateInput('create', $value);
    }
}

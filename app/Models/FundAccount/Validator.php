<?php

namespace RZP\Models\FundAccount;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Vpa;
use RZP\Models\Card;
use RZP\Models\Feature;
use RZP\Models\BankAccount;
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

    const NAME_REGEX = '/[a-zA-Z-.\' ]+$/';

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
        Entity::CUSTOMER_ID                         => 'sometimes|public_id',
        Entity::CONTACT_ID                          => 'sometimes|public_id',
        Entity::ACCOUNT_TYPE                        => 'required|string|custom',
        Entity::VPA                                 => 'filled|associative_array|custom',
        Entity::BANK_ACCOUNT                        => 'filled|associative_array|custom',
        Entity::CARD                                => 'filled|associative_array|custom',
        Entity::WALLET_ACCOUNT                      => 'filled|associative_array|custom',
        // This is required to even create the card because we need to fill a
        // dummy cvv and that requires network and that requires card number.
        // The other card details are validated as part of card creation.
        Entity::CARD . '.' . Card\Entity::NUMBER    => 'sometimes:card|required_without:card.token|numeric|luhn|digits_between:12,19',
        Entity::CARD . '.' . Card\Entity::NAME      => 'sometimes:card|max:100|custom:card_name',
        Entity::IDEMPOTENCY_KEY                     => 'sometimes|string',
        //Validation if vault token is received for payout creation
        //If card number is not present then vault token must be there
        Entity::CARD . '.' . Card\Entity::TOKEN     => 'sometimes:card|required_without:card.number|string',
        Entity::CARD . '.' . Card\Entity::TOKENISED => 'sometimes:card|bool',
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

        //Validating here if token and card number both has been received in the request.
        //There shall be either of them.
        //Validating it here as could not find any inbuilt validator for the use case.
        if ((isset($value[Card\Entity::NUMBER]) === true) and
            (isset($value[Card\Entity::TOKEN]) === true)) {
            throw new Exception\BadRequestValidationFailureException(
                'both card.token and card.number should not be sent'
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

    public function validateCardName($attribute, $value)
    {
        if (empty($this->entity) === true)
        {
            return;
        }

        /** @var Entity $fundAccount */
        $fundAccount = $this->entity;

        $merchant = $fundAccount->merchant;

        if($merchant->isFeatureEnabled(Feature\Constants::ALLOW_CARD_NAME_CHANGES) === true)
        {
            return;
        }

        $match = preg_match(self::NAME_REGEX, trim($value));

        if ($match !== 1)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The card.name format is invalid.',
                Entity::NAME);
        }
    }
}

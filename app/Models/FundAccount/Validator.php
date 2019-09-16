<?php

namespace RZP\Models\FundAccount;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Models\Feature;

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

    protected static $createRules = [
        Entity::CUSTOMER_ID                         => 'sometimes|public_id',
        Entity::CONTACT_ID                          => 'sometimes|public_id',
        Entity::ACCOUNT_TYPE                        => 'required|string|custom',
        Entity::VPA                                 => 'sometimes|associative_array',
        Entity::BANK_ACCOUNT                        => 'sometimes|associative_array',
        Entity::CARD                                => 'sometimes|associative_array|custom',
        // This is required to even create the card because we need to fill a
        // dummy cvv and that requires network and that requires card number.
        // The other card details are validated as part of card creation.
        Entity::CARD . '.' . Card\Entity::NUMBER    => 'required_with:card|numeric|luhn|digits_between:12,19',
        Entity::IDEMPOTENCY_KEY                     => 'sometimes|string',
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
        // Only one of card, vpa and bank_account can be present.

        $correctPresence = ((isset($input[Entity::CARD]) === true) xor
                            (isset($input[Entity::VPA]) === true) xor
                            (isset($input[Entity::BANK_ACCOUNT]) === true));

        if ($correctPresence === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Only one of card, vpa or bank_account can be present',
                null,
                [
                    'input' => $input,
                ]);
        }
    }

    protected function validateCard($attribute, $value)
    {
        if (empty($value) === true)
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
    }
}

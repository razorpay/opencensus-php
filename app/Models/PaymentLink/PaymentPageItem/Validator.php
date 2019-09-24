<?php

namespace RZP\Models\PaymentLink\PaymentPageItem;

use RZP\Base;
use RZP\Models\Item;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\PaymentLink;
use RZP\Models\Currency\Currency;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Validator
 *
 * @package RZP\Models\PaymentLink\PaymentPageItem
 *
 * @property Entity $entity
 */
class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ITEM            => 'required|array',
        Entity::MANDATORY       => 'filled|bool',
        Entity::IMAGE_URL       => 'sometimes|nullable|string|max:512',
        Entity::STOCK           => 'sometimes|nullable|mysql_unsigned_int',
        Entity::MIN_PURCHASE    => 'sometimes|nullable|mysql_unsigned_int',
        Entity::MAX_PURCHASE    => 'sometimes|nullable|mysql_unsigned_int',
        Entity::MIN_AMOUNT      => 'sometimes|nullable|mysql_unsigned_int|min_amount',
        Entity::MAX_AMOUNT      => 'sometimes|nullable|mysql_unsigned_int|min_amount|custom',
        Entity::SETTINGS        => 'nullable|array',

        Entity::SETTINGS . '.' . Entity::POSITION => 'nullable|int|min:0|max:1000',
    ];

    protected static $createValidators = [
        Entity::MIN_PURCHASE,
        Entity::MIN_AMOUNT,
    ];

    protected static $createManyRules = [
        Entity::PAYMENT_PAGE_ITEMS        => 'required|array|min:1|max:25',
        Entity::PAYMENT_PAGE_ITEMS . '.*' => 'required|array',
    ];

    protected static $editRules = [
        Entity::ITEM            => 'sometimes|array',
        Entity::MANDATORY       => 'sometimes|bool',
        Entity::IMAGE_URL       => 'sometimes|nullable|string|max:512',
        Entity::STOCK           => 'sometimes|nullable|mysql_unsigned_int',
        Entity::MIN_PURCHASE    => 'sometimes|nullable|mysql_unsigned_int',
        Entity::MAX_PURCHASE    => 'sometimes|nullable|mysql_unsigned_int',
        Entity::MIN_AMOUNT      => 'sometimes|nullable|mysql_unsigned_int|min_amount',
        Entity::MAX_AMOUNT      => 'sometimes|nullable|mysql_unsigned_int|min_amount|custom',
        Entity::SETTINGS        => 'nullable|array',

        Entity::SETTINGS . '.' . Entity::POSITION => 'nullable|int|min:0|max:1000',
    ];

    protected static $editValidators = [
        Entity::MIN_PURCHASE,
        Entity::MIN_AMOUNT,
    ];

    /**
     * @param  string   $attribute
     * @param  int|null $amount
     * @throws BadRequestValidationFailureException
     */
    public function validateAmount(string $attribute, int $amount = null)
    {
        $paymentPageItem = $this->entity;

        if ($amount === null)
        {
            return;
        }

        // If amount is set, validate that it doesn't exceeds max payment amount allowed for merchant
        $maxAmountAllowed = $paymentPageItem->merchant->getMaxPaymentAmount();

        if ($amount > $maxAmountAllowed)
        {
            throw new BadRequestValidationFailureException(
                $attribute . ' exceeds maximum payment amount allowed',
                $attribute,
                [
                    $attribute                          => $amount,
                    Merchant\Entity::MAX_PAYMENT_AMOUNT => $maxAmountAllowed,
                ]
            );
        }
    }

    public function validateMaxAmount(string $attribute, int $amount = null)
    {
        $this->validateAmount($attribute, $amount);
    }

    public function validateCurrency(string $attribute, string $currency)
    {
        $paymentPageItem = $this->entity;

        $international = $paymentPageItem->merchant->isInternational();

        // Non International accounts should not create PL in other currencies.
        if (($international !== true) and ($currency !== Currency::INR))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_INTERNATIONAL_NOT_ENABLED,
                $attribute,
                [
                    'currency' => $currency
                ]
            );
        }

        if ($this->entity->paymentLink->getCurrency() !== $currency)
        {
            throw new BadRequestValidationFailureException(
                'currency of payment_page_item should be equal to payment_page',
                $attribute,
                [
                    'currency' => $currency,
                    'payment_page_currency' => $this->entity->paymentLink->getCurrency(),
                ]
            );
        }
    }

    public function validateMinPurchase(array $input)
    {
        if ((isset($input[Entity::MAX_PURCHASE]) === true) and
            (isset($input[Entity::MIN_PURCHASE])))
        {
            if ($input[Entity::MAX_PURCHASE] < $input[Entity::MIN_PURCHASE])
            {
                throw new BadRequestValidationFailureException(
                    'min_purchase should not be greater than max_purchase',
                    Entity::MIN_PURCHASE,
                    [
                        Entity::MIN_PURCHASE => $input[Entity::MIN_PURCHASE],
                        Entity::MAX_PURCHASE => $input[Entity::MAX_PURCHASE],
                    ]
                );
            }
        }
    }

    public function validateMinAmount(array $input)
    {
        if (
            (isset($input[Entity::ITEM]) === true) and
            (isset($input[Entity::ITEM][Item\Entity::AMOUNT]) === true) and
            (
                (isset($input[Entity::MIN_AMOUNT]) === true) or
                (isset($input[Entity::MAX_AMOUNT]) === true)
            )
        )
        {
            throw new BadRequestValidationFailureException(
                'amount not required when min_amount or max_amount is present'
            );
        }

        if ((isset($input[Entity::MAX_AMOUNT]) === true) and
            (isset($input[Entity::MIN_AMOUNT])))
        {
            if ($input[Entity::MAX_AMOUNT] < $input[Entity::MIN_AMOUNT])
            {
                throw new BadRequestValidationFailureException(
                    'min_amount should not be greater than max_amount',
                    Entity::MIN_AMOUNT,
                    [
                        Entity::MIN_AMOUNT => $input[Entity::MIN_AMOUNT],
                        Entity::MAX_AMOUNT => $input[Entity::MAX_AMOUNT],
                    ]
                );
            }
        }

        if (isset($input[Entity::MIN_AMOUNT]) === true)
        {
            $this->validateAmount(Entity::MIN_AMOUNT, $input[Entity::MIN_AMOUNT]);
        }
    }

    public function validateStock(string $attribute, int $stock)
    {
        if ($this->entity->getQuantitySold() > $stock)
        {
            throw new BadRequestValidationFailureException(
                'stock should not be lesser than already sold quantity',
                $attribute,
                [
                    Entity::STOCK         => $stock,
                    Entity::QUANTITY_SOLD => $this->entity->getQuantitySold(),
                ]
            );
        }
    }

    public function validateItemCurrency(Item\Entity $item, PaymentLink\Entity $paymentLink)
    {
        if ($item->getCurrency() !== $paymentLink->getCurrency())
        {
            throw new BadRequestValidationFailureException(
                'payment page currency and payment page item currency should be same'
            );
        }
    }
}

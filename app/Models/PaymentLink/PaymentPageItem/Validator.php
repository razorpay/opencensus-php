<?php

namespace RZP\Models\PaymentLink\PaymentPageItem;

use Dotenv\Exception\ValidationException;
use RZP\Base;
use RZP\Models\Item;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\LineItem;
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
    const MIN_MAX_AMOUNT = 'min_max_amount';

    const EMPTY_STRING_FOR_INTEGERS = 'empty_string_for_integers';

    protected static $createRules = [
        Entity::ITEM            => 'required|array',
        Entity::MANDATORY       => 'filled|bool',
        Entity::IMAGE_URL       => 'sometimes|nullable|string|max:512',
        Entity::STOCK           => 'sometimes|nullable|mysql_unsigned_int|min:1',
        Entity::MIN_PURCHASE    => 'sometimes|nullable|mysql_unsigned_int|min:0',
        Entity::MAX_PURCHASE    => 'sometimes|nullable|mysql_unsigned_int|min:1',
        Entity::MIN_AMOUNT      => 'sometimes|nullable|mysql_unsigned_int',
        Entity::MAX_AMOUNT      => 'sometimes|nullable|mysql_unsigned_int',
        Entity::SETTINGS        => 'nullable|array',

        Entity::SETTINGS . '.' . Entity::POSITION => 'nullable|int|min:0|max:1000',
    ];

    protected static $createValidators = [
        Entity::MIN_PURCHASE,
        Entity::MIN_AMOUNT,
        Entity::MAX_AMOUNT,
        self::EMPTY_STRING_FOR_INTEGERS,
    ];

    protected static $createManyRules = [
        Entity::PAYMENT_PAGE_ITEMS        => 'required|array|min:1|max:25',
        Entity::PAYMENT_PAGE_ITEMS . '.*' => 'required|array',
    ];

    protected static $editRules = [
        Entity::ITEM            => 'sometimes|array',
        Entity::MANDATORY       => 'sometimes|bool',
        Entity::IMAGE_URL       => 'sometimes|nullable|string|max:512',
        Entity::STOCK           => 'sometimes|nullable|mysql_unsigned_int|min:1',
        Entity::MIN_PURCHASE    => 'sometimes|nullable|mysql_unsigned_int|min:0',
        Entity::MAX_PURCHASE    => 'sometimes|nullable|mysql_unsigned_int|min:1',
        Entity::MIN_AMOUNT      => 'sometimes|nullable|mysql_unsigned_int',
        Entity::MAX_AMOUNT      => 'sometimes|nullable|mysql_unsigned_int',
        Entity::SETTINGS        => 'nullable|array',

        Entity::SETTINGS . '.' . Entity::POSITION => 'nullable|int|min:0|max:1000',
    ];

    protected static $editValidators = [
        Entity::MIN_PURCHASE,
        Entity::MIN_AMOUNT,
        Entity::MAX_AMOUNT,
        self::MIN_MAX_AMOUNT,
        self::EMPTY_STRING_FOR_INTEGERS,
    ];

    /**
     * @param  string   $attribute
     * @param  int|null $amount
     * @param  string $currency
     * @throws BadRequestValidationFailureException
     */
    public function validateAmount(string $attribute,
                                   int $amount = null,
                                   $currency = Currency::INR)
    {
        $minAmount = Currency::getMinAmount($currency);

        if ($amount < $minAmount)
        {
            throw new BadRequestValidationFailureException(
            $attribute . ' must be atleast ' . $currency . ' ' . $minAmount / 100
            );
        }

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

    public function validateUpdatePaymentPageItems(array $paymentPageItemsDetails)
    {
        $paymentPageItemIds = [];

        foreach ($paymentPageItemsDetails as $paymentPageItemDetails)
        {
            if (isset($paymentPageItemDetails[Entity::ID]) === true)
            {
                if (isset($paymentPageItemIds[$paymentPageItemDetails[Entity::ID]]) === true)
                {
                    throw new BadRequestValidationFailureException(
                        'multiple payment page with same id not allowed'
                    );
                }

                $paymentPageItemIds[$paymentPageItemDetails[Entity::ID]] = true;
            }
        }
    }

    public function validateMaxAmount(array $input)
    {
        if (isset($input[Entity::MAX_AMOUNT]) === true)
        {
            $currency = Currency::INR;

            if ((empty($input[Entity::ITEM]) === false) and
                (empty($input[Entity::ITEM][Item\Entity::CURRENCY]) === false))
            {
                $currency = $input[Entity::ITEM][Item\Entity::CURRENCY];
            }
            else if (empty($this->entity->item) === false)
            {
                $currency = $this->entity->item->getCurrency();
            }

            $this->validateAmount(
                Entity::MAX_AMOUNT,
                $input[Entity::MAX_AMOUNT],
                $currency
            );
        }
    }

    public function validateMinMaxAmount(array $input)
    {
        if (
            (
                (empty($this->entity->item->getAmount()) === false)
            ) and
            (
                (isset($input[Entity::MAX_AMOUNT]) === true) or
                (isset($input[Entity::MIN_AMOUNT]) === true)
            )
        )
        {
            throw new BadRequestValidationFailureException(
                'max amount or min amount is not required if amount is set'
            );
        }
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
                'currency of payment page item should be equal to payment page',
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
                    'min purchase should not be greater than max purchase',
                    Entity::MIN_PURCHASE,
                    [
                        Entity::MIN_PURCHASE => $input[Entity::MIN_PURCHASE],
                        Entity::MAX_PURCHASE => $input[Entity::MAX_PURCHASE],
                    ]
                );
            }
        }

        if ((isset($input[Entity::MIN_PURCHASE]) === true) and
            (isset($input[Entity::STOCK]) === true) and
            ($input[Entity::MIN_PURCHASE] > $input[Entity::STOCK]))
        {
            throw new BadRequestValidationFailureException(
                'min purchase should not be greater than stock'
            );
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
                'amount not required when min amount or max amount is present'
            );
        }

        if ((isset($input[Entity::MAX_AMOUNT]) === true) and
            (isset($input[Entity::MIN_AMOUNT])))
        {
            if ($input[Entity::MAX_AMOUNT] < $input[Entity::MIN_AMOUNT])
            {
                throw new BadRequestValidationFailureException(
                    'min amount should not be greater than max amount',
                    Entity::MIN_AMOUNT,
                    [
                        Entity::MIN_AMOUNT => $input[Entity::MIN_AMOUNT],
                        Entity::MAX_AMOUNT => $input[Entity::MAX_AMOUNT],
                    ]
                );
            }
        }

        $currency = Currency::INR;

        if ((empty($input[Entity::ITEM]) === false) and
            (empty($input[Entity::ITEM][Item\Entity::CURRENCY]) === false))
        {
            $currency = $input[Entity::ITEM][Item\Entity::CURRENCY];
        }
        else if (empty($this->entity->item) === false)
        {
            $currency = $this->entity->item->getCurrency();
        }

        if (isset($input[Entity::MIN_AMOUNT]) === true)
        {
            $this->validateAmount(
                Entity::MIN_AMOUNT,
                $input[Entity::MIN_AMOUNT],
                $currency
            );
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

    public function validateAmountQuantityAndStockOfPPI(Entity $paymentPageItem, array $input)
    {
        if ((is_null($paymentPageItem->item->getAmount()) === false) and
            ($paymentPageItem->item->getAmount() !== $input[Item\Entity::AMOUNT]))
        {
            throw new BadRequestValidationFailureException(
                'amount should be equal to payment page item amount'
            );
        }

        if ((is_null($paymentPageItem->getMinAmount()) === false) and
            ($paymentPageItem->getMinAmount() > $input[Item\Entity::AMOUNT]))
        {
            throw new BadRequestValidationFailureException(
                'amount should not be lesser than to payment page item min amount'
            );
        }

        if ((is_null($paymentPageItem->getMaxAmount()) === false) and
            ($paymentPageItem->getMaxAmount() < $input[Item\Entity::AMOUNT]))
        {
            throw new BadRequestValidationFailureException(
                'amount should not be greater than to payment page item max amount'
            );
        }

        $quantity = $input[LineItem\Entity::QUANTITY] ?? 1;

        if ((is_null($paymentPageItem->getMinPurchase()) === false) and
            ($paymentPageItem->getMinPurchase() > $quantity))
        {
            throw new BadRequestValidationFailureException(
                'quantity should not be lesser than to payment page item min purchase'
            );
        }

        if ((is_null($paymentPageItem->getMaxPurchase()) === false) and
            ($paymentPageItem->getMaxPurchase() < $quantity))
        {
            throw new BadRequestValidationFailureException(
                'quantity should not be greater than to payment page item max purchase'
            );
        }

        if (is_null($paymentPageItem->getStock()) === false)
        {
            $availableStock = $paymentPageItem->getStock() - $paymentPageItem->getQuantitySold();

            if ($availableStock < $quantity)
            {
                throw new BadRequestValidationFailureException(
                    'no stock left'
                );

            }
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

    public function validateEmptyStringForIntegers(array $input)
    {
        $this->validateEmptyStringForInteger(Entity::MIN_AMOUNT, $input[Entity::MIN_AMOUNT] ?? null);
        $this->validateEmptyStringForInteger(Entity::MAX_AMOUNT, $input[Entity::MAX_AMOUNT] ?? null);
        $this->validateEmptyStringForInteger(Entity::MIN_PURCHASE, $input[Entity::MIN_PURCHASE] ?? null);
        $this->validateEmptyStringForInteger(Entity::MAX_PURCHASE, $input[Entity::MAX_PURCHASE] ?? null);
        $this->validateEmptyStringForInteger(Entity::STOCK, $input[Entity::STOCK] ?? null);
    }

    public function validateEmptyStringForInteger(string $attribute, $number)
    {
        if ((isset($number)) and
            (empty($number) === true) and
            ($number !== 0))
        {
            throw new BadRequestValidationFailureException(
                $attribute . ' should be null or valid integer'
            );
        }
    }

    public function validateInputForUpdate(array $input)
    {
        if (empty($input[Entity::STOCK]) === false)
        {
            if ($input[Entity::STOCK] < $this->entity->getQuantitySold())
            {
                throw new BadRequestValidationFailureException(
                    'stock cannot be lesser than the quantity sold'
                );
            }
        }
    }
}

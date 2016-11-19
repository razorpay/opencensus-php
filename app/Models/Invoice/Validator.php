<?php

namespace RZP\Models\Invoice;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        // Entity::DISCOUNT_FLAT       => 'sometimes|integer|min:1',
        // Entity::DISCOUNT_PERCENT    => 'sometimes|integer|min:1|max:100',
        // Entity::ADJUSTMENT          => 'sometimes|integer',
        // Entity::SHIPPING            => 'sometimes|integer|min:1',

        // If due_in is 0, it will get expired immediately. Hence the minimum value of 1.
        // Entity::DUE_IN              => 'sometimes|integer|min:1|max:365',
        // Entity::SCHEDULED_IN        => 'sometimes|integer|min:0|max:365',
        Entity::SMS_NOTIFY          => 'sometimes|boolean',
        Entity::EMAIL_NOTIFY        => 'sometimes|boolean',
        Entity::DATE                => 'sometimes|integer',
        Entity::TERMS               => 'sometimes|string|max:2048',
        Entity::NOTES               => 'sometimes|notes',
        Entity::REF_NUM             => 'sometimes|string|min:1|max:14',
        Entity::VIEW_LESS           => 'sometimes|in:1',
        Entity::SOURCE              => 'sometimes|string|max:32|custom',
        Entity::TYPE                => 'sometimes|string|max:16|custom',
        Entity::CUSTOMER            => 'sometimes',
        Entity::CUSTOMER_ID         => 'sometimes|string|size:19',
        Entity::LINE_ITEMS          => 'required|custom',
        Entity::CURRENCY            => 'sometimes|in:INR',
        Entity::USER_ID             => 'sometimes|alpha_num|size:14',
    ];

    public function validateSource($attribute, $value)
    {
        Source::checkSource($value);
    }

    public function validateType($attribute, $value)
    {
        Type::checkType($value);
    }

    public function validateLineItems($attribute, $value)
    {
        $itemsCount = count($value);

        if ($itemsCount === 0)
        {
            throw new BadRequestValidationFailureException(
                'Invoice must contain at least one line item.'
            );
        }

        if ($itemsCount > 10)
        {
            throw new BadRequestValidationFailureException(
                'Invoice cannot have more than 10 line items.'
            );
        }
    }

    // protected static $createValidators = [
    //     Entity::DISCOUNT_FLAT,
    //     Entity::DISCOUNT_PERCENT,
    // ];

    // public function validateDiscountFlat($input)
    // {
    //     if (isset($input[Entity::DISCOUNT_FLAT]) === false)
    //     {
    //         return;
    //     }
    //
    //     if (isset($input[Entity::DISCOUNT_PERCENT]) === true)
    //     {
    //         throw new BadRequestValidationFailureException(
    //             'Both discount_flat and discount_percent should not be set.'
    //         );
    //     }
    //
    //     $totalAmount = $discountableAmount = $input[Entity::TOTAL_AMOUNT];
    //
    //     if (isset($input[Entity::TOTAL_TAX]) === true)
    //     {
    //         $discountableAmount = $totalAmount - $input[Entity::TOTAL_TAX];
    //     }
    //
    //     if ($input[Entity::DISCOUNT_FLAT] > $discountableAmount)
    //     {
    //         throw new BadRequestValidationFailureException(
    //             'Discount cannot be greater than the total amount of the invoice',
    //             null,
    //             [
    //                 'total_amount'  => $input[Entity::TOTAL_AMOUNT],
    //                 'discount_flat' => $input[Entity::DISCOUNT_FLAT],
    //             ]
    //         );
    //     }
    // }
    //
    // public function validateDiscountPercent($input)
    // {
    //     if (isset($input[Entity::DISCOUNT_PERCENT]) === false)
    //     {
    //         return;
    //     }
    //
    //     if (isset($input[Entity::DISCOUNT_FLAT]) === true)
    //     {
    //         throw new BadRequestValidationFailureException(
    //             'Both discount_flat and discount_percent should not be set.'
    //         );
    //     }
    // }
}

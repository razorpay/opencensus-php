<?php

namespace RZP\Models\Invoice;

use Carbon\Carbon;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        // Entity::DISCOUNT_FLAT       => 'sometimes|integer|min:1',
        // Entity::DISCOUNT_PERCENT    => 'sometimes|integer|min:1|max:100',
        // Entity::ADJUSTMENT          => 'sometimes|integer',
        // Entity::SHIPPING            => 'sometimes|integer|min:1',

        Entity::DUE_BY              => 'sometimes|integer',
        Entity::SCHEDULED_AT        => 'sometimes|integer',
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

    protected static $createValidators = [
        // Entity::DISCOUNT_FLAT,
        // Entity::DISCOUNT_PERCENT,
        Entity::DUE_BY,
        Entity::SCHEDULED_AT,
    ];

    public function validateDueBy($input)
    {
        // Should be greater than current timestamp
        if (isset($input[Entity::DUE_BY]) === false)
        {
            return;
        }

        if ($input[Entity::DUE_BY] < Carbon::now('Asia/Kolkata')->timestamp)
        {
            throw new BadRequestValidationFailureException(
                'due_by must be greater than current time'
            );
        }
    }

    public function validateScheduledAt($input)
    {
        if (isset($input[Entity::SCHEDULED_AT]) === false)
        {
            return;
        }

        if (isset($input[Entity::DUE_BY]))
        {
            $dueBy = $input[Entity::DUE_BY];
        }
        else
        {
            $dueBy = Carbon::now('Asia/Kolkata')->addDays(Entity::DEFAULT_DUE_DAYS)->timestamp;
        }

        if ($input[Entity::SCHEDULED_AT] >= $dueBy)
        {
            throw new BadRequestValidationFailureException(
                'scheduled_at must be less than due_by'
            );
        }
    }

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

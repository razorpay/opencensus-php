<?php

namespace RZP\Models\Invoice;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        // Entity::DISCOUNT_FLAT       => 'sometimes|integer|min:1',
        // Entity::DISCOUNT_PERCENT    => 'sometimes|integer|min:1|max:100',
        // Entity::ADJUSTMENT          => 'sometimes|integer',
        // Entity::SHIPPING            => 'sometimes|integer|min:1',
        Entity::DUE_IN              => 'sometimes|integer|min:1|max:365',
        Entity::SMS_NOTIFY          => 'sometimes|boolean',
        Entity::EMAIL_NOTIFY        => 'sometimes|boolean',
    ];

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
<?php

namespace RZP\Models\PaymentLink\CustomDomain\Plans;

use RZP\Exception\BadRequestValidationFailureException;

class Plans
{
    /**
     * For each new alias, we will add the corresponding pricing
     * details in the PLAN array and also we will have to add
     * the ALIAS in PLAN_ALIAS_SEQUENCE array to define its
     * sequence during fetch call
     */
    const PLAN_ALIAS_SEQUENCE = [
        Aliases::MONTHLY_ALIAS   => 1,
        Aliases::QUARTERLY_ALIAS => 2,
        Aliases::BIYEARLY_ALIAS  => 3
    ];

    const PLAN = [
        Aliases::MONTHLY_ALIAS => [
            Constants::ALIAS        => Aliases::MONTHLY_ALIAS,
            Constants::NAME         => '1 Month',
            Constants::METADATA     => [
                Constants::MONTHLY_AMOUNT     => 500,
                Constants::PLAN_AMOUNT        => 500,
                Constants::DISCOUNT           => 0
            ]
        ],
        Aliases::QUARTERLY_ALIAS => [

                Constants::ALIAS        => Aliases::QUARTERLY_ALIAS,
                Constants::NAME         => '3 Months',
                Constants::METADATA     => [
                    Constants::MONTHLY_AMOUNT       => 400,
                    Constants::PLAN_AMOUNT          => 1200,
                    Constants::DISCOUNT             => 20
                ]

        ],
        Aliases::BIYEARLY_ALIAS => [
            Constants::ALIAS        => Aliases::BIYEARLY_ALIAS,
            Constants::NAME         => '6 Months',
            Constants::METADATA     => [
                Constants::MONTHLY_AMOUNT       => 350,
                Constants::PLAN_AMOUNT          => 2100,
                Constants::DISCOUNT             => 30
            ]
        ],
    ];

    public static function getByAlias(string $alias)
    {
        if(array_key_exists($alias, self::PLAN) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid alias: ' . $alias);
        }

        return self::PLAN[$alias];
    }

    public static function getPlanSequence()
    {
        return self::PLAN_ALIAS_SEQUENCE;
    }
}

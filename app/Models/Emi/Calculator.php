<?php

namespace RZP\Models\Emi;

class Calculator
{
    public static function calculateMerchantPayback(int $interest, int $term)
    {
        $monthlyInterest = $interest / (12 * 10000);

        $num = pow(1 + $monthlyInterest, $term);

        $mp = 100 * (($term * $monthlyInterest * $num) - $num + 1) / ($term * $monthlyInterest * $num);

        return (int) round(100 * $mp);
    }

    public static function calculateMinAmount(int $minAmount, int $merchantPayback)
    {
        return (int) ceil((100 * $minAmount) / (100 - $merchantPayback/100));
    }

    public static function calculateSubventedAmount(int $amount, int $merchantPayback)
    {
        return (int) ceil($amount - ($amount * $merchantPayback/10000));
    }
}

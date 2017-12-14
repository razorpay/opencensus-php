<?php

namespace RZP\Models\Emi;

class Calculator
{
    public static function calculateMerchantPayback(int $interest, int $term)
    {
        $monthlyInterest = $interest / (12 * 10000);

        $num = pow(1 + $monthlyInterest, $term);

        $mp = 100 * (($term * $monthlyInterest * $num) - $num + 1) / ($term * $monthlyInterest * $num);

        return intval(100 * $mp);
    }
}
<?php

namespace RZP\Reconciliator\Base;

class Helper
{
    /**
     * Used to convert recon amount in format to
     * check with amount stored in the entity.
     * @param $amount
     * @return int
     */
    public static function getIntegerFormattedAmount(string $amount)
    {
        $amountToBeFormatted = floatval($amount) * 100;

        // We are converting to int after casting to string as PHP randomly
        // returns wrong int values due to differing floating point precisions
        // So something like intval(31946.0) may give 31945 or 31946.
        // Converting to string using number_format and then converting
        // is a hack to avoid this issue
        return intval(number_format($amountToBeFormatted, 2, '.', ''));
    }
}

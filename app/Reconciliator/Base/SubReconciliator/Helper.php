<?php

namespace RZP\Reconciliator\Base\SubReconciliator;

class Helper
{
    /**
     * Used to convert recon amount in format to
     * check with amount stored in the entity.
     * @param $amount
     * @return int
     */
    public static function getIntegerFormattedAmount($amount)
    {
        // We are using filter_var to remove comma and other characters that may come in the amount field
        // e.g. in Amazonpay recon file, they send amount as 1,700.00
        $amount = str_replace(',', '', $amount);

        $amountToBeFormatted = floatval($amount) * 100;

        //
        // We are converting to int after casting to string as PHP randomly
        // returns wrong int values due to differing floating point precisions
        // So something like intval(31946.0) may give 31945 or 31946.
        // Converting to string using number_format and then converting
        // is a hack to avoid this issue
        // It can be negative in case of refunds, returning absolute value
        //
        return abs(intval(number_format($amountToBeFormatted, 2, '.', '')));
    }

    /**
     * Search for fields in the row one by one and returns first value at first occurrence
     *
     * @param array $row
     * @param array $fields
     * @return mixed
     */
    public static function getArrayFirstValue(array $row, array $fields)
    {
        $value = array_first(
            $fields,
            function($field) use ($row)
            {
                return (isset($row[$field]) === true);
            });

        return $row[$value] ?? null;
    }
}

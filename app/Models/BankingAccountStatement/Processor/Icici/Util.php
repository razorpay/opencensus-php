<?php

namespace RZP\Models\BankingAccountStatement\Processor\Icici;

class Util
{
    public static function stringInsert($input, $insertstr, $pos)
    {
        $input = substr($input, 0, $pos) . $insertstr . substr($input, $pos);

        return $input;
    }

    public static function convertAmountInPaiseToScientificNotation($balance)
    {
        $balance = stringify($balance);

        $len = strlen($balance);
        $balance = self::stringInsert($balance, ".", 1);
        $balance = $balance . "E" . stringify($len - 2 - 1); // -2 because of paisa

        return $balance;
    }

    public static function convertAmountInPaiseToINR($balance)
    {
        $balance = stringify($balance);

        $len = strlen($balance);

        if ($len === 1)
        {
            $balance = self::stringInsert($balance, ".0", $len - 2);
        }
        else
        {
            $balance = self::stringInsert($balance, ".", $len - 2);
        }

        return $balance;
    }
}

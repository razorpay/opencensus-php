<?php

namespace RZP\Models\BankingAccount;

use Carbon\Carbon;

class Rbl
{
    protected function getMappedAttributes($map, array $input)
    {
        $attr = [];

        foreach ($input as $key => $value)
        {
            if (isset($map[$key]))
            {
                $newKey        = $map[$key];
                $attr[$newKey] = $value;
            }
        }

        return $attr;
    }

    protected function parseAndFormatRblDate(string $date)
    {
        $date = Carbon::parse($date)->format('Y-m-d');

        return $date;
    }
}

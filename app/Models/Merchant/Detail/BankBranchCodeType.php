<?php

namespace RZP\Models\Merchant\Detail;

class BankBranchCodeType
{
    const IFSC = 'IFSC'; // Indian Financial System Code
    const MBC  = 'MBC' ; // Malaysian Bank Code

    public static function getAllowableEnumValues()
    {
        return [
            self::IFSC,
            self::MBC,
        ];
    }
}

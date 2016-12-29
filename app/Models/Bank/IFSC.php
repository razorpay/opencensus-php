<?php

namespace RZP\Models\Bank;

use Razorpay\IFSC\Bank as BaseBank;

class IFSC extends BaseBank
{
    public static function exists($code)
    {
        return defined(get_class().'::'.$code);
    }
}

<?php

namespace RZP\Models\Bank;

use Razorpay\IFSC\Bank as BaseBank;
use RZP\Models\Payment\Processor\Netbanking;

class IFSC extends BaseBank
{
    public static function exists($code)
    {
        return ((defined(get_class() . '::' . $code)) or
                (in_array($code, Netbanking::$inconsistentIfsc, true) === true));
    }
}

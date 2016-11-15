<?php

use Razorpay\IFSC\IFSC as BaseIFSC;

namespace RZP\Models\Bank;

class Name extends BaseIFSC
{
    public static function getName($ifsc)
    {
        return BaseIFSC::getBankName($ifsc);
    }
}

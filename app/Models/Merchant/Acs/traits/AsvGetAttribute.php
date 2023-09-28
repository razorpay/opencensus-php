<?php

namespace RZP\Models\Merchant\Acs\traits;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\ImplicitJoinHelper;


trait AsvGetAttribute
{
    public function getMerchantAttribute()
    {
        return (new ImplicitJoinHelper\ImplicitJoinHelper())->getMerchantAttributeByMerchantId($this, $this->entity);
    }
}

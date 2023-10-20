<?php

namespace RZP\Models\Merchant\Acs\Traits;

use RZP\Models\Merchant\Acs\ImplicitJoinHelper;
trait AsvMerchantDetailGetAttribute
{

    public function getMerchantDetailAttribute()
    {
        return (new ImplicitJoinHelper\ImplicitJoinHelper())->getMerchantDetailAttributeByMerchantId($this, $this->entity);
    }
}

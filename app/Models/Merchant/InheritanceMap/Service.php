<?php

namespace RZP\Models\Merchant\InheritanceMap;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function postResourceParent($merchantId, $parentMerchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $parentMerchant = $this->repo->merchant->findOrFailPublic($parentMerchantId);
        
        $inheritanceMap = $this->core()->create($merchant, $parentMerchant);
        
        return $inheritanceMap;
    }

}

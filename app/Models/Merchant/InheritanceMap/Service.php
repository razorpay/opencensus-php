<?php

namespace RZP\Models\Merchant\InheritanceMap;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function postInheritanceParent($merchantId, $parentMerchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $parentMerchant = $this->repo->merchant->findOrFailPublic($parentMerchantId);
        
        $inheritanceMap = $this->core()->create($merchant, $parentMerchant);
        
        return $inheritanceMap;
    }

    public function getInheritanceParent($merchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $inheritanceMap = $merchant->merchantInheritanceMap;

        return $inheritanceMap;
    }

    public function deleteInheritanceParent($merchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $inheritanceMap = $merchant->merchantInheritanceMap;

        $this->repo->deleteOrFail($inheritanceMap);

        return [];
    }

}

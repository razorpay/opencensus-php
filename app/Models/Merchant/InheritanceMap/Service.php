<?php

namespace RZP\Models\Merchant\InheritanceMap;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;

class Service extends Base\Service
{
    public function postInheritanceParent($merchantId, $parentMerchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);
        
        $partners = (new Merchant\Core())->fetchAffiliatedPartners($merchantId);

        //submerchant can belong to only one aggregator or fully managed at a time
        $partner = $partners->filter(function(Merchant\Entity $partner)
        {
            return (($partner->isAggregatorPartner() === true) or ($partner->isFullyManagedPartner() === true));
        })->first();

        if (($partner === null) or
            ($partner->getId() !== $parentMerchantId))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INHERITANCE_PARENT_SHOULD_BE_PARTNER_PARENT_OF_SUBMERCHANT);
        }

        $inheritanceMap = $this->core()->create($merchant, $partner);
        
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

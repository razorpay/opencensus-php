<?php

namespace RZP\Models\Merchant\InheritanceMap;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function create(
        Merchant\Entity $merchant,
        Merchant\Entity $parentMerchant
       )
    {
        $inheritanceMapping = (new Entity)->build();

        // need this because merchant_inheritance_map is synced in test and live, removing this will fail tests
        $inheritanceMapping->generateId();

        $inheritanceMapping->merchant()->associate($merchant);

        $inheritanceMapping->parentMerchant()->associate($parentMerchant);

        $this->repo->merchant_inheritance_map->saveOrFail($inheritanceMapping);

        return $inheritanceMapping;
    }

}

<?php

namespace RZP\Models\Merchant\InheritanceMap;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function create(
        Merchant\Entity $parentMerchant,
        Merchant\Entity $merchant
       )
    {
        $resourceMapping = (new Entity)->build();

        $resourceMapping->generateId();

        $resourceMapping->merchant()->associate($merchant);

        $resourceMapping->parentMerchant()->associate($parentMerchant);

        $this->repo->merchant_inheritance_map->saveOrFail($resourceMapping);

        return $resourceMapping;
    }

}

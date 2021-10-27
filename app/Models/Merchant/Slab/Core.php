<?php

namespace RZP\Models\Merchant\Slab;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    /**
     * @throws \RZP\Exception\LogicException
     */
    public function createAndSaveSlab(Merchant\Entity $merchant, array $input): Entity
    {
        $input[Entity::MERCHANT_ID] = $merchant->getId();

        $slab = (new Entity)->build($input);
        $slab->generateId();
        $this->repo->merchant_slabs->saveOrFail($slab);

        return $slab;
    }
}

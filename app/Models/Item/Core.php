<?php

namespace RZP\Models\Item;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Exception;

class Core extends Base\Core
{
    /**
     * @param array $input
     * @param Merchant\Entity $merchant
     * @return Entity
     */
    public function create(array $input, Merchant\Entity $merchant)
    {
        $item = (new Entity)->build($input);

        $item->merchant()->associate($merchant);

        $this->repo->saveOrFail($item);

        return $item;
    }

    public function put(Entity $item, array $input)
    {
        $item->edit($input);

        $this->repo->saveOrFail($item);

        return $item;
    }
}

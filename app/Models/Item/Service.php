<?php

namespace RZP\Models\Item;

use RZP\Models\Base;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create($input)
    {
        $item = $this->core->create($input, $this->merchant);

        return $item->toArrayPublic();
    }

    public function fetch($id)
    {
        $item = $this->repo->item->findByPublicIdAndMerchant($id, $this->merchant);

        return $item->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $items = $this->repo->item->fetch($input, $this->merchant->getId());

        return $items->toArrayPublic();
    }

    public function update(string $id, array $input)
    {
        $item = $this->repo->item->findByPublicIdAndMerchant($id, $this->merchant);

        return $this->core->update($item, $input)->toArrayPublic();
    }

    public function delete(string $id)
    {
        $item = $this->repo->item->findByPublicIdAndMerchant($id, $this->merchant);

        return $this->core->delete($item);
    }

    public function updateItemActiveAttribute(string $id, string $active)
    {
        $item = $this->repo->item->findByPublicIdAndMerchant($id, $this->merchant);

        $input[Entity::ACTIVE] = boolval($active);

        return $this->core->update($item, $input, false)->toArrayPublic();
    }
}

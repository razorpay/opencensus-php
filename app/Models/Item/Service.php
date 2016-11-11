<?php

namespace RZP\Models\Item;

use RZP\Models\Base;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function create($input)
    {
        $item = $this->core->create($input, $this->merchant);

        return $item->toArrayPublic();
    }

    public function fetch($id)
    {
        Entity::verifyIdAndStripSign($id);

        $item = $this->repo->item->findByIdAndMerchantId($id, $this->merchant->getId());

        return $item->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $items = $this->repo->item->fetch($input, $this->merchant->getId());

        return $items->toArrayPublic();
    }
}

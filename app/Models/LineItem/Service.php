<?php

namespace RZP\Models\LineItem;

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
}

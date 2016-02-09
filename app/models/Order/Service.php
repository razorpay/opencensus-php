<?php

namespace Models\Order;

use Models\Base;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create(array $input)
    {
        $order = $this->core->create($input);

        return $order;
    }
}

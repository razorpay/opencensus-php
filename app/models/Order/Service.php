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
        $merchant = $this->merchant;

        $order = $this->core->create($input, $merchant);

        return $order;
    }

    // public function
}

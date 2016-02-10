<?php

namespace Models\Order;

use Models\Base;
use Models\Order;

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

        return $order->toArray();
    }

    public function fetch($id)
    {
        Order\Entity::verifyIdAndStripSign($id);

        $order = (new Repository)->findByIdAndMerchantId($id, $this->merchant->getId());

        return $order->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $orders = (new Repository)->fetch($input, $this->merchant->getId());

        return $orders->toArrayPublic();
    }
}

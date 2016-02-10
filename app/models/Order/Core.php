<?php

namespace Models\Order;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;

class Core extends Base\Core
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new Repository;
    }

    public function create($input, $merchant)
    {
        $order = (new Entity)->build($input);

        $order->merchant()->associate($merchant);

        $order->setStatus(Status::CREATED);

        $this->repo->saveOrFail($order);

        return $order;
    }
}

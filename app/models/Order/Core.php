<?php

namespace Models\Order;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;

class Core extends Base\Core
{
    public function create($input, $merchant)
    {
        $order = (new Entity)->build($input);

        $order->merchant()->associate($merchant);

        $order->getValidator()->validateMerchantSpecificData($order);

        $this->repo->saveOrFail($order);

        return $order;
    }
}

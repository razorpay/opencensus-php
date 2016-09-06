<?php

namespace RZP\Models\Order;

use RZP\Models\Base;
use RZP\Exception;

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

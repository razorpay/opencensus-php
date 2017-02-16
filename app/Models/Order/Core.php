<?php

namespace RZP\Models\Order;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * @param $input
     * @param $merchant
     *
     * @return Entity
     */
    public function create(array $input, Merchant\Entity $merchant)
    {
        $this->trace->info(
            TraceCode::ORDER_CREATE_REQUEST,
            $input
        );

        $order = new Entity;

        // Needs to be associated first cause merchant entity is required
        // in orders create validators.
        $order->merchant()->associate($merchant);

        $order->build($input);

        $order->getValidator()->validateMerchantSpecificData($order);

        $this->repo->saveOrFail($order);

        return $order;
    }
}

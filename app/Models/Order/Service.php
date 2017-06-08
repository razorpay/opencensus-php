<?php

namespace RZP\Models\Order;

use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $merchant = $this->merchant;

        $order = (new Core)->create($input, $merchant);

        return $order->toArrayPublic();
    }

    public function fetch($id)
    {
        $order = $this->repo->order->findByPublicIdAndMerchant($id, $this->merchant);

        return $order->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $orders = $this->repo->order->fetch($input, $this->merchant->getId());

        return $orders->toArrayPublic();
    }

    public function fetchPaymentsFor($id)
    {
        $options = ['order_id' => $id];

        $payments = $this->repo->payment->fetch($options, $this->merchant->getKey());

        return $payments->toArrayPublic();
    }
}

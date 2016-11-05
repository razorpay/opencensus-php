<?php

namespace RZP\Models\Order;

use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;

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

        return $order->toArrayPublic();
    }

    public function fetch($id)
    {
        Order\Entity::verifyIdAndStripSign($id);

        $order = $this->repo->order->findByIdAndMerchantId($id, $this->merchant->getId());

        return $order->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $orders = $this->repo->order->fetch($input, $this->merchant->getId());

        return $orders->toArrayPublic();
    }

    public function fetchPaymentsFor($id)
    {
        $options = [
            'order_id' => $id,
        ];

        $payments = $this->repo->payment->fetch($options, $this->merchant->getKey());

        return $payments->toArrayPublic();
    }

    public function fetchOrderBankAndAccountNumberForMerchant($id)
    {
        Order\Entity::verifyIdAndStripSign($id);

        $order = $this->repo->order->findByIdAndMerchantId($id, $this->merchant->getId());

        return [
            'bank'           => $order->getBank(),
            'account_number' => $order->getMaskedAccountNumber(),
        ];
    }
}

<?php

namespace RZP\Models\UpiMandate;

use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(array $input, Order\Entity $order = null, Customer\Entity $customer = null)
    {
        $this->trace->info(
            TraceCode::UPI_MANDATE_CREATE_REQUEST,
            [
                'customer_id'  => $customer->getPublicId(),
                'merchant_id'  => $this->merchant->getPublicId(),
                'order_id'     => $order->getPublicId(),
                'input'        => $input,
            ]
        );

        $upiMandate = (new Entity)->build($input);

        $upiMandate->merchant()->associate($this->merchant);

        $upiMandate->order()->associate($order);

        $upiMandate->customer()->associate($customer);

        $this->repo->saveOrFail($upiMandate);

        $this->trace->info(
            TraceCode::UPI_MANDATE_CREATED,
            [
                'customer_id'  => $customer->getPublicId(),
                'merchant_id'  => $this->merchant->getPublicId(),
                'order_id'     => $order->getPublicId(),
                'mandate_id'   => $upiMandate->getPublicId(),
            ]
        );

        return $upiMandate;
    }

    public function validateTokenInput($input)
    {
        $validator = new Validator();

        $validator->setStrictFalse();

        $validator->validateInput('create', $input);
    }
}

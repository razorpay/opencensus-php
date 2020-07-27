<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Carbon\Carbon;
use RZP\Models\Order;
use RZP\Models\UpiMandate;

class UpiRecurring extends Base
{
    public function createOrder(array $orderInput = [], array $mandateInput = [])
    {
        $orderInput =  array_merge([
            'amount'          => 50000,
            'currency'        => 'INR',
            'method'          => 'upi',
            'customer_id'     => 'cust_100000customer',
            'payment_capture' => 1,
        ], $orderInput);

        $mandateInput = array_merge([
            'max_amount'      => 150000,
            'frequency'       => 'monthly',
            'recurring_type'  => 'before',
            'recurring_value' => 30,
            'start_at'        => Carbon::now()->addDay(1)->getTimestamp(),
            'expire_at'       => Carbon::now()->addDay(60)->getTimestamp(),
        ], $tokenInput);

        $order = new Order\Entity();
        $order->forceFill($orderInput);

        $order->saveOrFail();
    }
}

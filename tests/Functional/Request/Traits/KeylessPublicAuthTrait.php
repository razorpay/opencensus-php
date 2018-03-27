<?php

namespace RZP\Tests\Functional\Request\Traits;

use RZP\Constants\Mode;

/**
 * Helper trait for KeylessPublicAuthTest test classes.
 */
trait KeylessPublicAuthTrait
{
    // Helper methods to create needed fixtures.

    protected function createPayment(string $connection = Mode::LIVE)
    {
        return $this->fixtures->on($connection)->create('payment:captured', ['id' => '1000000payment']);
    }

    protected function createOrder(string $connection = Mode::LIVE)
    {
        return $this->fixtures->on($connection)->create('order', ['id' => '100000000order']);
    }

    protected function createInvoice(string $connection = Mode::LIVE)
    {
        $this->fixtures->on($connection)->create('order', ['id' => '100000invorder', 'payment_capture' => true]);

        $invoiceAttributes = ['id' => '1000000invoice', 'order_id' => '100000invorder', 'customer_id' => null, 'amount' => 1000000];

        return $this->fixtures->on($connection)->create('invoice', $invoiceAttributes);
    }

    protected function createCustomer(string $connection = Mode::LIVE)
    {
        return $this->fixtures->on($connection)->create('customer', ['id' => '110000customer']);
    }
}

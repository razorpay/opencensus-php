<?php

namespace Tests\Functional\Order;

use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;

class OrderTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/OrderTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testCreateOrder()
    {
        $order = $this->startTest();

        return $order;
    }

    public function testGetOrder()
    {
        $this->testCreateOrder();

        $order = $this->getLastEntity('order');

        sd($order);
    }
}

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
        $order = $this->testCreateOrder();

        $order = $this->getEntityById('order', $order['id']);

        $this->assertArraySelectiveEquals($this->testData[__FUNCTION__], $order);
    }

    public function testGetMultipleOrders()
    {
        $createdOrders = $this->fixtures->times(2)->create('order');
        $createdOrders = array_reverse($createdOrders);
        $collection = new \Models\Base\PublicCollection($createdOrders);
        $array = $collection->toArrayPublic();

        $this->testData[__FUNCTION__]['response']['content'] = $array;

        $this->startTest();
    }
}

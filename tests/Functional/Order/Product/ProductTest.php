<?php

namespace RZP\Tests\Functional\Order\Product;


use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class ProductTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/ProductTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    protected function createOrderWithoutProduct()
    {
        return $this->startTest($this->testData['testCreateOrderNoProducts']);
    }

    protected function createOrderWithProducts()
    {
        return $this->startTest($this->testData['testCreateOrderWithProducts']);
    }

    public function testCreateOrderWithProducts()
    {
        $this->startTest();

        $entities = $this->getEntities('product', [], true)['items'];

        $this->assertCount(2, $entities);

        foreach ($this->getEntities('product', [], true)['items'] as $productEntity)
        {
            $this->assertArrayNotHasKey('type', $productEntity['product']); // assert that while its stored, `product` column doesnt store `type`
        }
    }

    public function testCreateOrderInvalidProductType()
    {
        $this->startTest();
    }

    public function testCreateOrderMissingProductType()
    {
        $this->startTest();
    }

    public function testCreateOrderMutualFundProductInvalidKey()
    {
        $this->startTest();
    }

    // send products as an (assoc) array instead of list of products
    public function testCreateOrderProductsAssocArrayFail()
    {
        $this->startTest();
    }

    public function testGetOrderByIdWithProducts()
    {
        $orderId = $this->createOrderWithProducts()['id'];

        $fetchedOrder = $this->getLastEntity('order');

        $this->assertEquals($orderId, $fetchedOrder['id']);

        $this->assertArrayHasKey('products', $fetchedOrder);

        $this->assertEquals(2, count($fetchedOrder['products']));
    }

    public function testFetchOrdersWithProducts()
    {
        for ($i = 0; $i < 5; $i++)
        {
            $this->createOrderWithProducts();
        }

        $orderFetchRequest = [
            'url'    => '/orders',
            'method' => 'get',
        ];

        $orderFetchResponse = $this->makeRequestAndGetContent($orderFetchRequest);

        foreach ($orderFetchResponse['items'] as $order)
        {
            $this->assertArrayHasKey('products', $order);

            $this->assertEquals(2, count($order['products']));
        }
    }

    public function testCreateOrderNoProducts()
    {
        $response = $this->createOrderWithoutProduct();

        $this->assertArrayNotHasKey('products', $response);
    }

    public function testGetOrderByIdNoProducts()
    {
        $orderCreateResponse = $this->createOrderWithoutProduct();

        $orderGetRequest = [
            'url'    => '/orders/' . $orderCreateResponse['id'],
            'method' => 'get',
        ];

        $orderGetResponse = $this->makeRequestAndGetContent($orderGetRequest);

        $this->assertArrayNotHasKey('products', $orderGetResponse);
    }

    public function testFetchOrdersNoProducts()
    {
        for ($i = 0; $i < 5; $i++)
        {
            $this->createOrderWithoutProduct();
        }

        $orderFetchRequest = [
            'url'    => '/orders',
            'method' => 'get',
        ];

        $orderFetchResponse = $this->makeRequestAndGetContent($orderFetchRequest);

        foreach ($orderFetchResponse['items'] as $order)
        {
            $this->assertArrayNotHasKey('products', $order);
        }
    }

    public function testAmountMismatchError()
    {
        $this->fixtures->merchant->addFeatures(['cart_api_amount_check']);

        $this->startTest();
    }

    public function testAmountMismatchNoError()
    {
        $this->startTest();
    }
}

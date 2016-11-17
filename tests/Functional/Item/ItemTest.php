<?php

namespace RZP\Tests\Functional\Item;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

use Carbon\Carbon;
use Mockery;

class ItemTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/ItemTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['invoice']);

        $this->ba->privateAuth();
    }

    public function testCreateItem()
    {
        $response = $this->startTest();

        $item = $this->getLastEntity('item', true);

        $this->assertEquals($item['id'], $response['id']);
    }

    public function testGetItem()
    {
        $this->fixtures->create('item');

        $this->startTest();
    }

    public function testGetMultipleItems()
    {
        $this->fixtures->create('item');
        $this->fixtures->create('item', ['id' => '1000000001item', 'name' => 'A different product']);

        $this->startTest();
    }

    public function testPutItem()
    {
        $this->fixtures->create('item');

        $this->startTest();
    }

    public function testPutItemHavingLineItemsAssociated()
    {
        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->startTest();
    }

    public function testDeleteItem()
    {
        $this->fixtures->create('item');

        $this->startTest();

        $item = $this->getLastEntity('item', true);

        $this->assertEmpty($item);
    }

    public function testDeleteItemHavingLineItemsAssociated()
    {
        $this->fixtures->create('item');
        $this->fixtures->create('line_item');

        $this->startTest();
    }
}

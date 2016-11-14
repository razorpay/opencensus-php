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
}

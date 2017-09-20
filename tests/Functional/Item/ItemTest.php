<?php

namespace RZP\Tests\Functional\Item;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class ItemTest extends TestCase
{
    use RequestResponseFlowTrait;

    use \Illuminate\Foundation\Testing\DatabaseMigrations;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/ItemTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['invoice']);

        $this->ba->privateAuth();

        $this->seed('TaxGroupAndTaxSeeder');
    }

    public function testCreateItem()
    {
        $response = $this->startTest();
    }

    public function testCreateItem2()
    {
        $this->startTest();

        $this->assertResponseWithLastEntity('item', __FUNCTION__);
    }

    public function testCreateItemWithTaxId()
    {
        $this->startTest();
    }

    public function testCreateItemWithTaxGroupId()
    {
        $this->startTest();
    }

    public function testCreateItemWithBothTaxIdAndTaxGroupId()
    {
        $this->startTest();
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

    public function testUpdateItem()
    {
        $this->fixtures->create(
                            'item',
                            [
                                'unit'   => 'Kg',
                                'tax_id' => '00000000000001',
                            ]);

        $this->startTest();

        $this->assertResponseWithLastEntity('item', __FUNCTION__);
    }

    public function testUpdateItem2()
    {
        $this->fixtures->create('item');

        $this->startTest();

        $this->assertResponseWithLastEntity('item', __FUNCTION__);
    }

    public function testUpdateItemWithNewTaxId()
    {
        $this->fixtures->create('item', ['tax_id' => '00000000000001']);

        $this->startTest();
    }

    public function testUpdateItemWithNewTaxGroupId()
    {
        $this->fixtures->create('item', ['tax_group_id' => '00000000000001']);

        $this->startTest();
    }

    public function testUpdateItemWithTaxIdWhenTaxGroupIdExists()
    {
        $this->fixtures->create('item', ['tax_group_id' => '00000000000001']);

        $this->startTest();
    }

    public function testUpdateItemWithTaxIdAndRemoveTaxGroupId()
    {
        $this->fixtures->create('item', ['tax_group_id' => '00000000000001']);

        $this->startTest();

        $this->assertResponseWithLastEntity('item', __FUNCTION__);
    }

    public function testUpdateItemOfTypeNonInvoice()
    {
        $this->fixtures->create('item', ['type' => 'plan']);

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

    public function testDeleteItemOfTypeNonInvoice()
    {
        $this->fixtures->create('item', ['type' => 'plan']);

        $this->startTest();
    }
}

<?php

namespace RZP\Tests\Functional\Item;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class ItemTest extends TestCase
{
    use RequestResponseFlowTrait;

    use \Illuminate\Foundation\Testing\DatabaseMigrations;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/ItemTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['invoice']);

        $this->ba->privateAuth();

        $this->seed('TaxGroupAndTaxSeeder');
    }

    public function testCreateItem()
    {
        $this->startTest();
    }

    public function testCreateItem2()
    {
        $this->startTest();

        $this->assertResponseWithLastEntity('item', __FUNCTION__);
    }

    public function testCreateItemWithoutCurrency()
    {
        $this->startTest();
    }

    public function testCreateItemWithInvalidTaxRate()
    {
        $this->startTest();
    }

    public function testCreateItemWithTaxId()
    {
        $this->startTest();
    }

    public function testCreateItemWithTaxIdInternational()
    {
        $this->fixtures->merchant->edit('10000000000000', ['convert_currency' => 1]);

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

    public function testCreateItemWithHsnAndSacCode()
    {
        $this->startTest();
    }

    public function testUpdateItemToContainBothHsnAndSacCode()
    {
        $this->fixtures->create('item', ['id' => '1000000001item', 'hsn_code' => '01010101']);

        $this->startTest();
    }

    public function testGetItem()
    {
        $this->fixtures->create('item');

        $this->startTest();
    }

    public function testGetItemWithExpandTax()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('item', ['tax_id' => '00000000000001']);

        $this->startTest();
    }

    public function testGetMultipleItems()
    {
        $this->fixtures->create('item');
        $this->fixtures->create('item', ['id' => '1000000001item', 'name' => 'A different product']);

        $this->startTest();
    }

    public function testGetMultipleItemsWithExpandTax()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('item', ['tax_id' => '00000000000001']);
        $this->fixtures->create('item', ['id' => '1000000001item', 'name' => 'A different product']);

        $this->startTest();
    }

    /**
     * Tests fetching items by query string i.e. auto complete use case.
     */
    public function testGetMultipleItemsViaEs()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('item', ['id' => '1000000001item', 'name' => 'A different product']);

        $this->createEsMockAndSetExpectations(__FUNCTION__);

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

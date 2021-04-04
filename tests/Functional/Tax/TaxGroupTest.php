<?php

namespace RZP\Tests\Functional\Tax;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class TaxGroupTest extends TestCase
{
    use RequestResponseFlowTrait;

    use \Illuminate\Foundation\Testing\DatabaseMigrations;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/TestData/TaxGroupTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        $this->seed('TaxGroupAndTaxSeeder');
    }

    public function testGetTaxGroup()
    {
        $this->startTest();
    }

    public function testGetMultipleTaxGroups()
    {
        $this->startTest();
    }

    public function testCreateTaxGroup()
    {
        $this->startTest();
    }

    public function testCreateTaxGroupWithInvalidNumberOfTaxIds()
    {
        $this->startTest();
    }

    public function testUpdateTaxGroup()
    {
        $this->startTest();
    }

    public function testDeleteTaxGroup()
    {
        $this->startTest();
    }

    public function testDeleteTaxGroupAndCacadeNullInItem()
    {
        $this->fixtures->create('item', ['tax_group_id' => '00000000000001']);

        $this->testData[__FUNCTION__] = $this->testData['testDeleteTaxGroup'];

        $this->startTest();

        $item = $this->getLastEntity('item', true);

        $this->assertNull($item['tax_group_id']);
    }
}

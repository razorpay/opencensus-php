<?php

namespace RZP\Tests\Functional\Tax;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class TaxTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/TestData/TaxTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testGetTax()
    {
        $this->fixtures->create('tax', ['id' => '00000000000001']);

        $this->startTest();
    }

    public function testGetMultipleTaxes()
    {
        $this->fixtures->create('tax', ['id' => '00000000000001']);
        $this->fixtures->create('tax', ['id' => '00000000000002']);

        $this->startTest();
    }

    public function testCreateTax()
    {
        $this->startTest();
    }

    public function testCreateTaxWithInvalidPercentageRateValue()
    {
        $this->startTest();
    }

    public function testUpdateTax()
    {
        $this->fixtures->create('tax', ['id' => '00000000000001']);

        $this->startTest();
    }

    public function testUpdateTaxWithInvalidRateTypeAndValueCombination()
    {
        $this->fixtures->create(
            'tax',
            [
                'id'        => '00000000000001',
                'rate_type' => 'flat',
                'rate'      => '1050000',
            ]);

        $this->startTest();
    }

    public function testDeleteTax()
    {
        $this->fixtures->create('tax', ['id' => '00000000000001']);

        $this->startTest();

        $tax = $this->getLastEntity('tax', true);

        $this->assertEmpty($tax);
    }

    public function testDeleteTaxAndCacadeNullInItem()
    {
        $this->fixtures->create('tax', ['id' => '00000000000001']);

        $this->fixtures->create('item', ['tax_id' => '00000000000001']);

        $this->testData[__FUNCTION__] = $this->testData['testDeleteTax'];

        $this->startTest();

        $item = $this->getLastEntity('item', true);

        $this->assertNull($item['tax_id']);
    }

    public function testGetTaxMetaTaxRates()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetTaxMetaStates()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }
}

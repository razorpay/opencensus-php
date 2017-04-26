<?php

namespace RZP\Tests\Functional\Tax;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class TaxGroupTest extends TestCase
{
    use RequestResponseFlowTrait;

    use \Illuminate\Foundation\Testing\DatabaseMigrations;

    public function setUp()
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
    }
}

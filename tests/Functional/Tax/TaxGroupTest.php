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

        // Following asserts ensures that cascade=restrict is working fine

        $groupsTaxIds = ['tax_00000000000001', 'tax_00000000000002'];

        $taxEntities = $this->getEntities('tax', [], true);

        $taxIdsInDb = array_values(array_column($taxEntities['items'], 'id'));

        $this->assertEmpty(
            array_diff($groupsTaxIds, $taxIdsInDb),
            'Some/all of the tax ids associated with group got deleted with group.'
        );
    }
}

<?php

namespace Functional\Merchant\Slabs;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class MerchantSlabsTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/MerchantSlabsTestData.php';
        parent::setUp();
    }

    public function testCreateCodSlabs()
    {
        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testCreateShippingSlabs()
    {
        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testCreateCodSlabWithInvalidRange()
    {
        $this->ba->privateAuth();
        $this->startTest();
    }
}

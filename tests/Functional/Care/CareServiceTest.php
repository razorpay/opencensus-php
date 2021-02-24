<?php


namespace Functional\Care;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class CareServiceTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CareServiceTestData.php';

        parent::setUp();
    }

    public function testInternalMerchantFetch()
    {
        $this->ba->careAppAuth();

        $this->startTest();
    }
}

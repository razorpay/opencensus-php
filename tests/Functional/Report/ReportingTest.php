<?php

namespace RZP\Tests\Functional\Report;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class ReportingTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/ReportingTestData.php';

        parent::setUp();
    }

    public function testMerchantConfigsForInvalidReportType()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGettingPartnerConfigsByNonPartner()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }
}

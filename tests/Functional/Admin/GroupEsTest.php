<?php

namespace RZP\Tests\Functional\Admin;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Merchant\Traits\MakesEsDocumentAssertions;

class GroupEsTest extends TestCase
{
    // use HeimdallTrait;
    use RequestResponseFlowTrait;
    use MakesEsDocumentAssertions;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/GroupEsTestData.php';

        parent::setUp();
    }

    public function testEditGroup()
    {
        $this->assertMerchantEsDocs(__FUNCTION__ . 'BeforeEsAssertions');

        $this->ba->adminAuth('test');

        $this->startTest();

        $this->assertMerchantEsDocs(__FUNCTION__ . 'AfterEsAssertions');
    }

    public function testDeleteGroup()
    {
        $this->assertMerchantEsDocs(__FUNCTION__ . 'BeforeEsAssertions');

        $this->ba->adminAuth('test');

        $this->startTest();

        $this->assertMerchantEsDocs(__FUNCTION__ . 'AfterEsAssertions');
    }

    private function assertMerchantEsDocs(string $testDataIndex)
    {
        $testData = $this->testData[$testDataIndex];

        foreach ($testData as $id => $expected)
        {
            $this->getAndAssertEsDocForEntityAndMode($id, $expected, 'merchant', 'test');
        }
    }
}

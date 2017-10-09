<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class MerchantEsCreateTest extends TestCase
{
    use RequestResponseFlowTrait;
    use Traits\MakesEsDocumentAssertions;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantEsCreateTestData.php';

        parent::setUp();
    }

    public function testCreateMerchant()
    {
        list($response, $testEsDoc, $liveEsDoc) = $this->startTestAndMakeAssertionsOnEsDoc(__FUNCTION__, '1X4hRFHFx4UiXt');

        $this->assertNotEmpty($testEsDoc['created_at']);
        $this->assertNotEmpty($testEsDoc['updated_at']);
        $this->assertNotEmpty($testEsDoc['merchant_detail']['updated_at']);

        $this->assertNotEmpty($liveEsDoc['created_at']);
        $this->assertNotEmpty($liveEsDoc['updated_at']);
        $this->assertNotEmpty($liveEsDoc['merchant_detail']['updated_at']);
    }

    public function testUpdateMerchantWithBasicDatapoints()
    {
        $this->startTestAndMakeAssertionsOnEsDoc(__FUNCTION__);
    }

    public function testUpdateMerchantWithGroups()
    {
        $this->startTestAndMakeAssertionsOnEsDoc(__FUNCTION__);
    }

    public function testUpdateMerchantWithAdminsAndGroups()
    {
        $this->startTestAndMakeAssertionsOnEsDoc(__FUNCTION__);
    }

    public function testAddMerchantTags()
    {
        $this->startTestAndMakeAssertionsOnEsDoc(__FUNCTION__);
    }

    public function testRemoveMerchantTag()
    {
        $this->startTestAndMakeAssertionsOnEsDoc(__FUNCTION__);
    }

    private function startTestAndMakeAssertionsOnEsDoc(
        string $callee,
        string $id = '10000000000016'): array
    {
        $this->ba->appAuthTest();

        $testData = $this->testData[$callee];
        $response = $this->startTest($testData);

        // Asserts indexed document in both modes
        $expected  = $this->testData[$callee . 'ExpectedEsTestDoc'];
        $testEsDoc = $this->getAndAssertMerchantEsDocForMode($id, $expected, 'test');

        $expected  = $this->testData[$callee . 'ExpectedEsLiveDoc'];
        $liveEsDoc = $this->getAndAssertMerchantEsDocForMode($id, $expected, 'live');

        return [$response, $testEsDoc, $liveEsDoc];
    }

    private function getAndAssertMerchantEsDocForMode(
        string $id,
        array $expected,
        string $mode)
    {
        return $this->getAndAssertEsDocForEntityAndMode($id, $expected, 'merchant', $mode);
    }
}

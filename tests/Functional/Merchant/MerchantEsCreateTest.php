<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class MerchantEsCreateTest extends TestCase
{
    use RequestResponseFlowTrait;
    use Traits\MakesEsDocumentAssertions;

    //
    // VERIFY:
    // - Test if test and lives are getting updated and shit is fine.
    // - Assert with admins, groups, details, tags, is_marketplace, referrer
    // - Create and assert that it exists in ES
    // - Write a trait to get documents from ES
    //

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantEsCreateTestData.php';

        parent::setUp();
    }

    public function testCreateMerchant()
    {
        $this->ba->appAuthTest();

        $this->startTest();

        //
        // Asserts indexed document in both modes
        //
        $expected = $this->testData[__FUNCTION__ . 'ExpectedEsTestDoc'];

        $actual = $this->getAndAssertMerchantEsDocForMode('1X4hRFHFx4UiXt', $expected, 'test');

        $this->assertNotEmpty($actual['created_at']);
        $this->assertNotEmpty($actual['updated_at']);
        $this->assertNotEmpty($actual['merchant_detail']['updated_at']);

        $expected = $this->testData[__FUNCTION__ . 'ExpectedEsLiveDoc'];

        $actual = $this->getAndAssertMerchantEsDocForMode('1X4hRFHFx4UiXt', $expected, 'live');

        $this->assertNotEmpty($actual['created_at']);
        $this->assertNotEmpty($actual['updated_at']);
        $this->assertNotEmpty($actual['merchant_detail']['updated_at']);
    }

    public function testUpdateMerchantWithBasicDatapoints()
    {
    }

    public function testUpdateMerchantWithAdmins()
    {
    }

    public function testUpdateMerchantWithGroups()
    {
    }

    public function testUpdateMerchantWithAdminsAndGroups()
    {
    }

    public function testAddMerchantTags()
    {
    }

    public function testRemoveMerchantTag()
    {
    }

    private function getAndAssertMerchantEsDocForMode(
        string $id,
        array $expected,
        string $mode)
    {
        return $this->getAndAssertEsDocForEntityAndMode('1X4hRFHFx4UiXt', $expected, 'merchant', $mode);
    }
}

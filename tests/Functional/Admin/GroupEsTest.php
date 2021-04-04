<?php

namespace RZP\Tests\Functional\Admin;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Merchant\Traits\MakesEsDocumentAssertions;

/**
 * Has tests to assert that MerchantSync is working fine. We update/delete
 * groups (their parents) and assert that all related/affected merchant docs
 * in Es are getting refreshed.
 *
 * We have the group hierarchy on which we operate below in tests created
 * in fixtures.
 * Ref:
 * - \RZP\Tests\Functional\Fixtures\Entity\Merchant's setUpHiemdallHierarcyForRazorpayOrg()
 * - 2nd image of https://gist.github.com/jitendra-1217/0d8f74c1bf3683aad112fa7e97dc527c
 */
class GroupEsTest extends TestCase
{
    use RequestResponseFlowTrait;
    use MakesEsDocumentAssertions;

    protected function setUp(): void
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

    /**
     * Test data is an assoc array containing expected partial Es doc (only
     * concerned with groups attribute) against merchant ids. We iterate over
     * that and assert the same by retrieving the document from ES. This method
     * gets called before and after doing the group operation.
     *
     * @param string $testDataIndex
     */
    private function assertMerchantEsDocs(string $testDataIndex)
    {
        $testData = $this->testData[$testDataIndex];

        foreach ($testData as $id => $expected)
        {
            $this->getAndAssertEsDocForEntityAndMode(
                    $id,
                    $expected,
                    'merchant',
                    'test');
        }
    }
}

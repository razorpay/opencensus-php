<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class MerchantEsFetchTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantEsFetchTestData.php';

        parent::setUp();
    }

    //
    // testGetMerchantsFromEsByQ: Search happens for a q=jitendra.
    // There are 2 merchants and both have the same first name(jitendra). But
    // they belong to different groups and admins.
    //
    // Ref to diagram in Fixtures/Entity/Merchant.php
    //

    public function testGetMerchantsFromEsByQForAdmin11()
    {
        $this->startTestAndMakeAssertions(
            'testGetMerchantsFromEsByQ',
            '10000000000011',
            [
                '10000000000011',
                '10000000000012',
            ]);
    }



    public function testGetMerchantsFromEsByQForAdmin12()
    {
        $this->startTestAndMakeAssertions(
            'testGetMerchantsFromEsByQ',
            '10000000000012',
            [
                '10000000000012',
            ]);
    }



    public function testGetMerchantsFromEsByQForAdmin13()
    {
        $this->startTestAndMakeAssertions(
            'testGetMerchantsFromEsByQ',
            '10000000000013',
            [
                '10000000000011',
            ]);
    }



    public function testGetMerchantsFromEsByQForAdmin14()
    {
        $this->startTestAndMakeAssertions(
            'testGetMerchantsFromEsByQ',
            '10000000000014',
            [
                '10000000000011',
                '10000000000012',
            ]);
    }



    public function testGetMerchantsFromEsByQForAdmin15()
    {
        $this->startTestAndMakeAssertions(
            'testGetMerchantsFromEsByQ',
            '10000000000015',
            [
                '10000000000011',
            ]);
    }



    public function testGetMerchantsFromEsByQForAdmin16()
    {
        $this->startTestAndMakeAssertions(
            'testGetMerchantsFromEsByQ',
            '10000000000016',
            [
            ]);
    }



    public function testGetMerchantsFromEsByAccountStatusAll()
    {
        $this->startTestAndMakeAssertions(
            __FUNCTION__,
            '10000000000011',
            [
                '10000000000011',
                '10000000000012',
                '10000000000013',
                '10000000000014',
                '10000000000015',
            ]);
    }

    public function testGetMerchantsFromEsByAccountStatusArchived()
    {
        $this->startTestAndMakeAssertions(
            __FUNCTION__,
            '10000000000011',
            [
                '10000000000013',
            ]);
    }

    public function testGetMerchantsFromEsByQAndAccountStatusArchived()
    {
        $this->startTestAndMakeAssertions(
            __FUNCTION__,
            '10000000000011',
            [
            ]);
    }

    public function testGetMerchantsFromEsByAllSubAccounts()
    {
        $this->startTestAndMakeAssertions(
            __FUNCTION__,
            '10000000000011',
            [
                '10000000000014',
                '10000000000015',
            ]);
    }

    public function testGetMerchantsFromEsBySubAccounts()
    {
        $this->startTestAndMakeAssertions(
            __FUNCTION__,
            '10000000000011',
            [
                '10000000000014',
            ]);
    }

    public function testGetMerchantsFromEsByQAndSubAccounts()
    {
        $this->startTestAndMakeAssertions(
            __FUNCTION__,
            '10000000000011',
            [
                '10000000000015',
            ]);
    }

    public function testGetMerchantsFromEsByAccountStatusAndSubAccounts()
    {
        $this->startTestAndMakeAssertions(
            __FUNCTION__,
            '10000000000011',
            [
            ]);
    }

    public function testGetMerchantsFromEsByQAndAssertExactResponse()
    {
        $this->ba->adminAuth('test', '10000000000011');

        $this->startTest();
    }

    public function testGetMerchantIdsFromEsByQAndAssertExactResponse()
    {
        $this->ba->adminAuth('test', '10000000000011');

        $this->startTest();
    }

    /**
     * - Start tests for given callee after setting up authentication for given
     *   admin id.
     * - Makes assertions with fetch response against given expected ids.
     *
     * @param string $testDataIndex
     * @param string $adminId
     * @param array  $expectedIds
     *
     * @return array
     */
    private function startTestAndMakeAssertions(
        string $testDataIndex,
        string $adminId,
        array $expectedIds): array
    {
        $this->ba->adminAuth('test', $adminId);

        $testData = $this->testData[$testDataIndex];

        $response = $this->startTest($testData);

        $this->assertEsFetchResults($expectedIds, $response);

        return $response;
    }

    /**
     * Asserts that the expected ids are there in fetch results.
     *
     * @param array $expectedIds
     * @param array $actualResponse
     */
    private function assertEsFetchResults(
        array $expectedIds,
        array $actualResponse)
    {
        $actualIds = array_pluck($actualResponse['items'], 'id');

        sort($expectedIds);
        sort($actualIds);

        $this->assertEquals($expectedIds, $actualIds);
    }
}

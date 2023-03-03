<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Error\ErrorCode;
use RZP\Models\Admin\Admin\Token;
use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\Artisan;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class MerchantEsFetchTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
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

    /**Below Two tests are route '/admins/unified_dashboard_merchants'
     * 1) Happy flow -> admin has the onboarding_and_activations_view permission
     * 2) UnHappy flow -> admin doesn't have onboarding_and_activations_view permission and throws Exception
     */
    public function testGetMerchantsFromEsByMiqSharingDateHappy()
    {
        $requestToken = $this->getAdminRequestToken('10000000000014');

        $this->ba->adminAuth("test", $requestToken);

        $this->setAdminPermission('onboarding_and_activations_view');

        $testData = $this->testData['testGetMerchantsFromEsByMiqSharingDateHappy'];

        $this->startTest($testData);
    }

    public function testGetMerchantsFromEsByMiqSharingDateUnHappy()
    {
        $requestToken = $this->getAdminRequestToken('10000000000014');

        $this->ba->adminAuth("test", $requestToken);

        $testData = $this->testData['testGetMerchantsFromEsByMiqSharingDateUnHappy'];

        $this->startTest($testData);
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

    public function testGetMerchantsFromEsByBusinessTypeRegistered()
    {
        $this->startTestAndMakeAssertions(
            __FUNCTION__,
            '10000000000011',
            [
                '10000000000013',
                '10000000000014',
                '10000000000015',
            ]);
    }

    public function testGetMerchantsFromEsByBusinessTypeNonRegistered()
    {
        $this->startTestAndMakeAssertions(
            __FUNCTION__,
            '10000000000011',
            [
                '10000000000011',
                '10000000000012',
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
        $this->markTestSkipped('Todo: Debug the different order of ids in drone & local!');

        $requestToken = $this->getAdminRequestToken('10000000000011');

        $this->ba->adminAuth('test', $requestToken);

        $this->setAdminPermission('admin_fetch_merchants');

        $this->startTest();
    }

    public function testGetMerchantIdsFromEsByQAndAssertExactResponse()
    {
        $requestToken = $this->getAdminRequestToken('10000000000011');

        $this->ba->adminAuth('test', $requestToken);

        $this->setAdminPermission('admin_fetch_merchants');

        $this->startTest();
    }

    public function testGetMerchantsFromEsByPartnerType()
    {
        $this->fixtures->merchant->edit('10000000000014', ['partner_type' => 'reseller']);

        Artisan::call('rzp:index', ['mode' => 'live', 'entity' => 'merchant']);
        Artisan::call('rzp:index', ['mode' => 'test', 'entity' => 'merchant']);

        $requestToken = $this->getAdminRequestToken('10000000000011');

        $this->ba->adminAuth('test', $requestToken);

        $this->setAdminPermission('admin_fetch_merchants');

        $this->startTest();
    }

    public function testGetPartnerActivationFromEsByActivationStatus()
    {
        $this->fixtures->merchant->edit('10000000000014', ['partner_type' => 'reseller']);

        $this->fixtures->create('partner_activation', ['merchant_id' => '10000000000014', 'activation_status' => 'under_review']);

        Artisan::call('rzp:index', ['mode' => 'live', 'entity' => 'partner_activation', '--primary_key' => 'merchant_id']);
        Artisan::call('rzp:index', ['mode' => 'test', 'entity' => 'partner_activation', '--primary_key' => 'merchant_id']);

        $requestToken = $this->getAdminRequestToken('10000000000011');

        $this->ba->adminAuth('test', $requestToken);

        $this->setAdminPermission('admin_fetch_merchants');

        $this->startTest();
    }

    public function testGetPartnerActivationFromEsByQ()
    {
        $this->fixtures->merchant->edit('10000000000014', ['partner_type' => 'reseller']);

        $this->fixtures->create('partner_activation', ['merchant_id' => '10000000000014']);

        Artisan::call('rzp:index', ['mode' => 'live', 'entity' => 'partner_activation', '--primary_key' => 'merchant_id']);
        Artisan::call('rzp:index', ['mode' => 'test', 'entity' => 'partner_activation', '--primary_key' => 'merchant_id']);

        $requestToken = $this->getAdminRequestToken('10000000000011');

        $this->ba->adminAuth('test', $requestToken);

        $this->setAdminPermission('admin_fetch_merchants');

        $this->startTest();
    }

    public function testGetMerchantsFromEsByActivationSource()
    {
        $this->fixtures->merchant->edit('10000000000014', ['activation_source' => 'banking']);

        Artisan::call('rzp:index', ['mode' => 'live', 'entity' => 'merchant']);
        Artisan::call('rzp:index', ['mode' => 'test', 'entity' => 'merchant']);

        $requestToken = $this->getAdminRequestToken('10000000000011');

        $this->ba->adminAuth('test', $requestToken);

        $this->setAdminPermission('admin_fetch_merchants');

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
        $requestToken = $this->getAdminRequestToken($adminId);

        $this->ba->adminAuth("test", $requestToken);

        $this->setAdminPermission('admin_fetch_merchants');

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

    /**
     * @param string $adminId
     *
     * @return string
     */
    private function getAdminRequestToken(string $adminId): string
    {
        $adminToken = $this->getDbEntity('admin_token', [Token\Entity::ADMIN_ID => $adminId]);

        // As admin token(without encryption) and admin id is same .
        $requestToken = $adminToken->getAdminId() . $adminToken->getId();

        return $requestToken;
    }

    protected function setAdminPermission($permissionName)
    {
        $admin = $this->ba->getAdmin();

        $admin->roles()->sync([Org::ADMIN_ROLE]);

        $roleOfAdmin = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => $permissionName]);

        $roleOfAdmin->permissions()->attach($perm->getId());
    }
}

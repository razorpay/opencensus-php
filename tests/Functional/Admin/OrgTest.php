<?php

namespace RZP\Tests\Functional\Admin;

use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\TestCase;
use RZP\Services\Settlements;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use  RZP\Models\Feature;
use RZP\Tests\Traits\MocksSplitz;

class OrgTest extends TestCase
{
    use RequestResponseFlowTrait;
    use HeimdallTrait;
    use MocksSplitz;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/OrgData.php';

        parent::setUp();

        $this->ba->adminAuth('test');
    }

    public function mockSettlementsForOrgBankAccount() {
        $settlementsDashboardMock = $this->getMockBuilder( Settlements\Dashboard::class)
            ->setConstructorArgs([$this->app])
            ->getMock();

        $settlementsApiMock = $this->getMockBuilder( Settlements\Api::class)
            ->setConstructorArgs([$this->app])
            ->getMock();;

        $this->app->instance('settlements_dashboard', $settlementsDashboardMock);

        $this->app->instance('settlements_api', $settlementsApiMock);

        $this->app->settlements_dashboard->method('orgBankAccountUpdate')->willReturn([]);

        $this->app->settlements_dashboard->method('createBankAccount')->willReturn([]);
    }

    public function testCreateOrgBankAccount()
    {
        $this->mockSettlementsForOrgBankAccount();

        $this->fixtures->create('feature', [
            'name' => Feature\Constants::ENABLE_ORG_ACCOUNT,
            'entity_id' => '100000razorpay',
            'entity_type' => 'org',
        ]);

        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

        $this->startTest();
    }

    public function testCreateOrgBankAccount2()
    {
        $this->mockSettlementsForOrgBankAccount();

        $this->fixtures->create('org', [
           'id' => '100001razorpay'
        ]);

        $this->fixtures->create('feature', [
            'name' => Feature\Constants::ENABLE_ORG_ACCOUNT,
            'entity_id' => '100001razorpay',
            'entity_type' => 'org',
        ]);

        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

        $this->startTest();
    }

    public function testCreateOrgBankAccountFailureWithFeatureFlag()
    {
        $this->mockSettlementsForOrgBankAccount();

        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

        $this->startTest();
    }

    public function testGetOrgBankAccount()
    {
        $this->mockSettlementsForOrgBankAccount();

        $this->testCreateOrgBankAccount();

        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

        $this->startTest();
    }


    public function testGetOrgBankAccount2()
    {
        $this->mockSettlementsForOrgBankAccount();

        $this->testCreateOrgBankAccount2();

        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

        $this->startTest();
    }

    public function testUpdateOrgBankAccount()
    {
        $this->testCreateOrgBankAccount();

        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

        $this->startTest();
    }

    public function testUpdateOrgBankAccount2()
    {
        $this->testCreateOrgBankAccount2();

        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

       $this->startTest();
    }

    public function testCreateOrg()
    {
        $permIds = $this->getPermissionsByIds('assignable');

        $this->testData[__FUNCTION__]['request']['content']['permissions'] = $permIds;

        $this->startTest();

        $org = $this->getLastEntity('org', true);

        $this->assertEquals($org['type'], 'restricted');
    }

    public function testEditOrg()
    {
        $org = $this->fixtures->create('org');

        $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $authToken = $this->getAuthTokenForOrg($org);

        $this->ba->adminAuth('test', $authToken);

        $this->testData[__FUNCTION__]['request']['url'] .= '/' . $org->getPublicId();

        $this->startTest();
    }

    public function testEditOrgMerchant2FaAuth()
    {
        $org = $this->fixtures->create('org');

        $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $authToken = $this->getAuthTokenForOrg($org);

        $this->ba->adminAuth('test', $authToken);

        $this->testData[__FUNCTION__]['request']['url'] .= '/' . $org->getPublicId();

        $this->startTest();
    }

    public function testEditOrgAdmin2FaAuth()
    {
        $org = $this->fixtures->create('org');

        $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $authToken = $this->getAuthTokenForOrg($org);

        $this->ba->adminAuth('test', $authToken);

        $this->testData[__FUNCTION__]['request']['url'] .= '/' . $org->getPublicId();

        $this->startTest();
    }

    public function testEditOrg2FaAuthMode()
    {
        $org = $this->fixtures->create('org');

        $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $authToken = $this->getAuthTokenForOrg($org);

        $this->ba->adminAuth('test', $authToken);

        $this->testData[__FUNCTION__]['request']['url'] .= '/' . $org->getPublicId();

        $this->startTest();
    }

    public function testEditOrgWithPermissions()
    {
        $org = $this->fixtures->create('org');

        $firstOrgHost = $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $secondOrgHost = $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $authToken = $this->getAuthTokenForOrg($org);

        $this->addAssignablePermissionsToOrg($org);

        $this->ba->adminAuth('test', $authToken);

        $this->testData[__FUNCTION__]['request']['url'] .= '/' . $org->getPublicId();

        $workflowPermissions = $this->getPermissionsByIds('workflow');

        $newWorkflowPerms = array_slice($workflowPermissions, 0, 2);

        $permissions = $this->getPermissionsByIds('assignable');

        $newPermissions = array_slice($permissions, 0, 3);
        $newPermissions = array_unique(array_merge($newPermissions, $newWorkflowPerms));

        $this->testData[__FUNCTION__]['request']['content']['permissions'] = $newPermissions;

        $this->testData[__FUNCTION__]['request']['content']['workflow_permissions'] = $newWorkflowPerms;

        $result = $this->startTest();

        $this->assertEquals(count($newPermissions), count($result['permissions']));

        $role = $this->ba->getAdmin()->roles()->get()[0];

        $rolePermissions = $role->permissions()->allRelatedIds()->toArray();

        $this->assertEquals(count($newWorkflowPerms), count($result['workflow_permissions']));

        $this->assertEquals(count($newPermissions), count($rolePermissions));
    }

    public function testEditOtherOrg()
    {
        $org = $this->fixtures->create('org', ['cross_org_access' => true]);

        $authToken = $this->getAuthTokenForOrg($org);

        $this->ba->adminAuth('test', $authToken);

        $otherOrg = $this->fixtures->create('org');

        $this->testData[__FUNCTION__]['request']['url'] .= '/' . $otherOrg->getPublicId();

        $this->startTest();
    }

    public function testDeleteOrg()
    {
        $org = $this->fixtures->create('org');

        $authToken = $this->getAuthTokenForOrg($org);

        $this->ba->adminAuth('test', $authToken);

        $firstOrgHost = $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $secondOrgHost = $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $this->testData[__FUNCTION__]['request']['url'] .= '/' . $org->getPublicId();

        $this->startTest();

        $data = $this->testData['deleteOrgException'];

        $this->runRequestResponseFlow($data, function() use ($org) {
            $this->getEntityById('org', $org->getPublicId(), true);
        });

        $this->runRequestResponseFlow($data, function() use ($org) {
            $this->getEntityById('org', $org->getPublicId(), true, 'live');
        });
    }

    public function testFetchMultipleOrg()
    {
        $this->startTest();
    }

    public function testCreateOrgInvalidAuthType()
    {
        $permIds = $this->getPermissionsByIds('assignable');

        $this->testData[__FUNCTION__]['request']['content']['permissions'] = $permIds;

        $this->startTest();
    }

    public function testCreateOrgInvalidHostname()
    {
        $permIds = $this->getPermissionsByIds('assignable');

        $this->testData[__FUNCTION__]['request']['content']['permissions'] = $permIds;

        $this->startTest();
    }

    public function testCreateOrgNotUniqueHostname()
    {
        $org = $this->fixtures->create('org');

        $firstOrgHost = $this->fixtures->create('org_hostname',
            ['org_id' => $org->getId(), 'hostname' => 'test1.com']);

        $permIds = $this->getPermissionsByIds('assignable');

        $this->testData[__FUNCTION__]['request']['content']['permissions'] = $permIds;

        $res = $this->startTest();
    }

    public function testGetOrg()
    {
        $this->ba->adminAuth();

        $org = $this->fixtures->create('org', ['email' => 'sreeram12@gmail.com']);

        $firstOrgHost = $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $secondOrgHost = $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $this->testData[__FUNCTION__]['request']['url'] .= '/' . $org->getPublicId() . '/self';

        $result = $this->startTest();

        $this->assertNotEmpty($result['hostname']);

        $this->assertArrayHasKey('features', $result);

        $hostnames = $result['hostname'];
        $hostnames = explode(',', $result['hostname']);

        $this->assertEquals(2, count($hostnames));
    }

    public function testGetOrgWithFeatureEnabled()
    {
        $this->ba->adminAuth();

        $org = $this->fixtures->create('org', ['email' => 'sreeram12@gmail.com']);

        $this->fixtures->create('feature', [
            'name'          => Constants::ORG_CUSTOM_BRANDING,
            'entity_id'     => $org->getId(),
            'entity_type'   => 'org',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Constants::WHITE_LABELLED_PAYMENT_PAGES,
            'entity_id'     => $org->getId(),
            'entity_type'   => 'org',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Constants::WHITE_LABELLED_PAYMENT_BUTTONS,
            'entity_id'     => $org->getId(),
            'entity_type'   => 'org',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Constants::WHITE_LABELLED_SUBS_BUTTONS,
            'entity_id'     => $org->getId(),
            'entity_type'   => 'org',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Constants::WHITE_LABELLED_MARKETPLACE,
            'entity_id'     => $org->getId(),
            'entity_type'   => 'org',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Constants::WHITE_LABELLED_STORES,
            'entity_id'     => $org->getId(),
            'entity_type'   => 'org',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Constants::WHITE_LABELLED_OFFERS,
            'entity_id'     => $org->getId(),
            'entity_type'   => 'org',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Constants::WHITE_LABELLED_CHECKOUT_REWARDS,
            'entity_id'     => $org->getId(),
            'entity_type'   => 'org',
        ]);

        $this->fixtures->create('feature', [
            'name'          => Constants::ORG_CUSTOM_CHECKOUT_LOGO,
            'entity_id'     => $org->getId(),
            'entity_type'   => 'org',
        ]);

        $firstOrgHost = $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $secondOrgHost = $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $this->testData[__FUNCTION__]['request']['url'] .= '/' . $org->getPublicId() . '/self';

        $result = $this->startTest();

        $this->assertNotEmpty($result['hostname']);

        $this->assertNotEmpty($result['features']);

        $hostnames = $result['hostname'];
        $hostnames = explode(',', $result['hostname']);

        $features = $result['features'];

        $expectedFeatures = [
                Constants::ORG_CUSTOM_BRANDING,
                Constants::WHITE_LABELLED_PAYMENT_PAGES,
                Constants::WHITE_LABELLED_PAYMENT_BUTTONS,
                Constants::WHITE_LABELLED_SUBS_BUTTONS,
                Constants::WHITE_LABELLED_MARKETPLACE,
                Constants::WHITE_LABELLED_STORES,
                Constants::WHITE_LABELLED_OFFERS,
                Constants::WHITE_LABELLED_CHECKOUT_REWARDS,
                Constants::ORG_CUSTOM_CHECKOUT_LOGO
            ];

        $this->assertEquals(2, count($hostnames));
        $this->assertEquals($expectedFeatures, $features);
    }

    public function testGetOtherOrg()
    {
        $org = $this->fixtures->create('org', [
            'cross_org_access' => true,
        ]);

        $authToken = $this->getAuthTokenForOrg($org);

        $this->ba->adminAuth('test', $authToken);

        $otherOrg = $this->fixtures->create('org', [
            'email' => 'testotherrzp@gmail.com',
        ]);

        $this->testData[__FUNCTION__]['request']['url'] .= '/' . $otherOrg->getPublicId();

        $result = $this->startTest();
    }

    public function testGetOrgByHostname()
    {
        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testGetOrgByHostnameDevstack()
    {
        $org = $this->fixtures->create('org', ['email' => 'bankingtest@axis.com']);

        $this->orgHostName = $this->fixtures->create('org_hostname', [
            'org_id'        => $org->getId(),
            'hostname'      => 'dashboard-bankingaxis.dev.razorpay.in',
        ]);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testGetOrgByHostnameCurlecDevstack()
    {
        $org = $this->fixtures->create('org', ['email' => 'testing@curlec.com']);

        $this->orgHostName =  $this->fixtures->create('org_hostname', [
            'org_id' => $org->getId(),
            'host_name'=>'dashboard-curlec.dev.razorpay.in',
            ]);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testGetOrgByHostnamePreviewURLDevstack()
    {
        $org = $this->fixtures->create('org', ['email' => 'testingpreview@razorpay.com']);

        $this->orgHostName = $this->fixtures->create('org_hostname', [
            'org_id'        => $org->getId(),
            'hostname'      => 'dashboard-pr-6000.dev.razorpay.in',
        ]);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testFeatureForOrg()
    {
        $this->ba->dashboardGuestAppAuth();

        $this->fixtures->create('feature', [
            'name'          => Constants::ORG_ANNOUNCEMENT_TAB_DISABLE,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $result = $this->startTest();
        $this->assertEquals(['disable_announcements'], $result['features']);
    }

    // Test for an exception
    public function testCreateWithoutPassword()
    {
        $permIds = $this->getPermissionsByIds('assignable');

        $this->testData[__FUNCTION__]['request']['content']['permissions'] = $permIds;

        $this->startTest();
    }

    public function testEditOrgWithPricingPlan()
    {
        $org = $this->fixtures->org->createHdfcOrg();

        $this->ba->adminAuth('test', 'SuperSecretTokenForRazorpaySuprHdfcbToken', $org->getPublicId(), 'hdfcbank.com');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] .= '/' . $org->getPublicId();

        $testData['request']['content']['default_pricing_plan_id'] = '1hDYlICobzOCYt';

        $this->makeRequestAndCatchException(
            function () use ($testData)
            {
                $this->runRequestResponseFlow($testData);
            },
            \RZP\Exception\BadRequestException::class,
            'The id provided does not exist');

        $this->fixtures->pricing->createPricingPlanForDifferentOrg($org->getId());

        $testData['request']['content']['default_pricing_plan_id'] = '1hDYlICxbxOCYx';

        $response = $this->runRequestResponseFlow($testData);

        $this->assertEquals('1hDYlICxbxOCYx', $response['default_pricing_plan_id']);
    }


    public function testFeatureAddVAExpiry()
    {
        $this->ba->dashboardGuestAppAuth();

        $this->fixtures->create('feature', [
            'name' => Constants::SET_VA_DEFAULT_EXPIRY,
            'entity_id' => "100000razorpay",
            'entity_type' => 'org',
        ]);

        $result = $this->startTest();
        $this->assertEquals(['set_va_default_expiry'], $result['features']);
    }

    protected function enableSplitzMerchantSessionTimeout($orgId)
    {
        $requestData = '{"org_id":"' . $orgId . '"}';

        $input = [
            "experiment_id" => "LIiQLbE44UOA2K",
            "id"            => $orgId,
            "request_data"  => $requestData,
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);
    }

    public function testCreateOrgWithMerchantSessionTimeoutWithoutSplitz()
    {
        $permIds = $this->getPermissionsByIds('assignable');

        $this->testData[__FUNCTION__]['request']['content']['permissions'] = $permIds;

        $result = $this->startTest();

        $this->assertNotEmpty($result['merchant_session_timeout_in_seconds']);

        $this->assertEquals(43200, $result['merchant_session_timeout_in_seconds']);
    }

    public function testCreateOrgWithoutMerchantSessionTimeoutWithoutSplitz()
    {
        $permIds = $this->getPermissionsByIds('assignable');

        $this->testData[__FUNCTION__]['request']['content']['permissions'] = $permIds;

        $this->startTest();
    }

    public function testEditOrgWithoutMerchantSessionTimeout()
    {
        $org = $this->fixtures->create('org');

        $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $authToken = $this->getAuthTokenForOrg($org);

        $this->ba->adminAuth('test', $authToken);

        $this->testData[__FUNCTION__]['request']['url'] .= '/' . $org->getPublicId();

        $this->startTest();
    }

    public function testEditOrgNonIntMerchantSessionTimeout()
    {
        $org = $this->fixtures->create('org');

        $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $authToken = $this->getAuthTokenForOrg($org);

        $this->ba->adminAuth('test', $authToken);

        $this->enableSplitzMerchantSessionTimeout($org->getId());

        $this->testData[__FUNCTION__]['request']['url'] .= '/' . $org->getPublicId();

        $this->startTest();
    }

    public function testEditOrgLessThanMinMerchantSessionTimeout()
    {
        $org = $this->fixtures->create('org');

        $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $authToken = $this->getAuthTokenForOrg($org);

        $this->ba->adminAuth('test', $authToken);

        $this->enableSplitzMerchantSessionTimeout($org->getId());

        $this->testData[__FUNCTION__]['request']['url'] .= '/' . $org->getPublicId();

        $this->startTest();
    }

    public function testEditOrgWithMerchantSessionTimeout()
    {
        $org = $this->fixtures->create('org');

        $this->fixtures->create('org_hostname', ['org_id' => $org->getId()]);

        $authToken = $this->getAuthTokenForOrg($org);

        $this->ba->adminAuth('test', $authToken);

        $this->enableSplitzMerchantSessionTimeout($org->getId());

        $this->testData[__FUNCTION__]['request']['url'] .= '/' . $org->getPublicId();

        $result = $this->startTest();

        $this->assertNotEmpty($result['merchant_session_timeout_in_seconds']);

        $this->assertEquals(600, $result['merchant_session_timeout_in_seconds']);
    }

    public function testGetOrgWithMerchantSessionTimeout()
    {
        $this->ba->adminAuth();

        $org = $this->fixtures->create('org', ['email' => 'testrzp@gmail.com', 'merchant_session_timeout_in_seconds' => 600]);

        $this->testData[__FUNCTION__]['request']['url'] .= '/' . $org->getPublicId() . '/self';

        $result = $this->startTest();

        $this->assertNotEmpty($result['merchant_session_timeout_in_seconds']);

        $this->assertEquals(600, $result['merchant_session_timeout_in_seconds']);
    }
}

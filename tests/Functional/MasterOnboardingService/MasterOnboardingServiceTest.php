<?php

namespace RZP\Tests\Functional\MasterOnboardingService;

use DB;
use Config;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class MasterOnboardingServiceTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $config;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/MasterOnboardingServiceTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testProxyIntentApplyApplication()
    {
        $this->startTest();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testProxyFetchApplication()
    {
        $this->startTest();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testProxyFetchMultipleApplications()
    {
        $this->startTest();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testProxyCreateIntent()
    {
        $this->startTest();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testProxyFetchIntent()
    {
        $this->startTest();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testProxyFetchMultipleIntents()
    {
        $this->startTest();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testProxyGetWorkflow()
    {
        $this->startTest();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testProxySaveWorkflow()
    {
        $this->startTest();

        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function addRoleAndPermissionForAdmin()
    {
        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $role = $this->fixtures->create('role', [
            'org_id' => Org::RZP_ORG,
        ]);

        DB::table('role_map')->insert(
            [
                'role_id'     => $role->getId(),
                'entity_type' => 'admin',
                'entity_id'   => $admin->getId(),
            ]);

        $permission = $this->fixtures->create('permission',[
            'name'   => 'mob_admin'
        ]);

        DB::table('permission_map')->insert(
            [
                'entity_id'     => $role->getId(),
                'entity_type'   => 'role',
                'permission_id' => $permission->getId(),
            ]);
    }

    public function testAdminIntentApplyApplication()
    {
        $this->addRoleAndPermissionForAdmin();

        $this->startTest();
    }

    public function testAdminFetchApplication()
    {
        $this->addRoleAndPermissionForAdmin();

        $this->startTest();
    }

    public function testAdminFetchMultipleApplications()
    {
        $this->addRoleAndPermissionForAdmin();

        $this->startTest();
    }

    public function testAdminCreateIntentForOneCa()
    {
        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $role = $this->fixtures->create('role', [
            'org_id' => Org::RZP_ORG,
        ]);

        DB::table('role_map')->insert(
            [
                'role_id'     => $role->getId(),
                'entity_type' => 'admin',
                'entity_id'   => $admin->getId(),
            ]);

        $permission = $this->fixtures->create('permission',[
            'name'   => 'submit_one_ca'
        ]);

        DB::table('permission_map')->insert(
            [
                'entity_id'     => $role->getId(),
                'entity_type'   => 'role',
                'permission_id' => $permission->getId(),
            ]);

        $this->startTest();
    }

    public function testAdminCreateIntent()
    {
        $this->addRoleAndPermissionForAdmin();

        $this->startTest();
    }

    public function testAdminFetchIntent()
    {
        $this->addRoleAndPermissionForAdmin();

        $this->startTest();
    }

    public function testAdminFetchMultipleIntents()
    {
        $this->addRoleAndPermissionForAdmin();

        $this->startTest();
    }

    public function testMobMigration()
    {
        $this->addRoleAndPermissionForAdmin();

        $this->startTest();
    }

    public function testMobToBasRoutes()
    {
        $attributes = [
            'bas_business_id'   => '10000000000000',
        ];

        $this->createMerchantDetailWithBusinessId($attributes);

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->ba->mobAppAuthForInternalRoutes();

        $this->startTest();
    }

    public function testIntentCreateViaLms()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testMobAdminRequestUserIdResolutionFailure()
    {
        $merchantId = '10000000000001';

        $userId = '10000000000002';

        $this->testData[__FUNCTION__] = $this->testData['testAdminCreateIntent'];

        $this->addRoleAndPermissionForAdmin();

        $this->fixtures->create('merchant', [
            'id' => $merchantId
        ]);

        $this->fixtures->create('user', [
            'id' => $userId
        ]);

        $this->fixtures->create('merchant_user', [
            'merchant_id'   => $merchantId,
            'user_id'       => $userId,
            'role'          => 'manager',
            'product'       => 'banking',
        ]);

        $dataToReplace = [
            'request' => [
                'content'   => [
                    'merchant_id'   => $merchantId,
                    'user_id'       => $userId,
                ]
            ],
        ];

        $this->expectException(BadRequestException::class);

        $this->startTest($dataToReplace);
    }

    public function createMerchantDetailWithBusinessId(array $attributes = [])
    {
        $default = [
            'activation_status' => 'activated',
            'merchant_id'       => '10000000000000',
            'business_type'     => '2',
        ];

        $attributes = array_merge($default, $attributes);

        return $this->fixtures->create('merchant_detail', $attributes);
    }
}

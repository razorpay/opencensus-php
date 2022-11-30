<?php

namespace RZP\Tests\Functional\UserRole;

use RZP\Http\Route;
use RZP\Models\User\BankingRole;
use RZP\Models\User\Constants;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Http\Middleware\UserAccess;
use RZP\Http\UserRolePermissionsMap;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\UserRole\Banking\Dashboard\BankingRoleTrait;

class RoleAccessTest extends TestCase
{
    use RequestResponseFlowTrait;
    use BankingRoleTrait;

    /**
     * @var array
     */
    private $routePermissions;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/RoleAccessTestData.php';

        parent::setUp();

        $this->routePermissions = Route::$bankingRoutePermissions;
    }

    public function testGrantAccessWhenExperimentOff()
    {
        $user = $this->fixtures->create('user',['id'  => '20000000000006']);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000000',
            'role'        => BankingRole::VIEW_ONLY,
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->mockRazorXTreatmentAccessDenyUnauthorised("off");

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testDenyAccessWhenExperimentOn()
    {
        $user = $this->fixtures->create('user',['id'  => '20000000000006']);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000000',
            'role'        => BankingRole::VIEW_ONLY,
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode) {
                    if ($feature === 'rx_custom_access_control_enabled')
                    {
                        return 'off';
                    }

                    if ($feature === 'rx_custom_access_control_disabled')
                    {
                        return 'on';
                    }

                    if ($feature === 'razorpay_x_acl_deny_unauthorised')
                    {
                        return 'on';
                    }

                    return 'control';
                }));

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->startTest();
    }

    public function testAllRoleAccesses()
    {
        // Validate for Owner Role
        $this->validateAccesses(BankingRole::OWNER);

        // Validate for Admin Role
        $this->validateAccesses(BankingRole::ADMIN);

        // Validate for Finance L1 Role
        $this->validateAccesses(BankingRole::FINANCE_L1);

        // Validate for Operations role
        $this->validateAccesses(BankingRole::OPERATIONS);

        // Validate for View_Only role
        $this->validateAccesses(BankingRole::VIEW_ONLY);

        //Validate for Chartered_Accountant role
        $this->validateAccesses(BankingRole::CHARTERED_ACCOUNTANT);

    }

    public function testCheckPermissionsForBankingLegacyRoles()
    {
        $legacyRoles = $this->getLegacyRoles();

        $this->disableRazorXTreatmentCAC();

        foreach ($legacyRoles as $role)
        {
            $userId = $this->createMerchantUser($role);

            $testData = & $this->testData[__FUNCTION__];

            $testData['request']['url'] = '/users/' . $userId;

            $testData['request']['server']['HTTP_X-Dashboard-User-Id'] = $userId;

            $testData['response']['content']['merchants'][1]['banking_role'] = $role;

            $this->ba->dashboardGuestAppAuth();

            $response = $this->startTest();

            $this->assertArrayHasKey(Constants::PERMISSIONS, $response['merchants'][1]);

            $permissions = $response['merchants'][1][Constants::PERMISSIONS];

            $this->validatePermissionForRole($role, $permissions);
        }
    }

    protected function validatePermissionForRole(string $role, array $actualPermissions)
    {
        $expectedPermissions = $this->getUserRolePermissiblePermissions($role);

        foreach ($expectedPermissions as $pr)
        {
            $this->assertContains($pr, $actualPermissions);
        }
    }

    protected function createMerchantUser(string $role)
    {
        $user = $this->fixtures->create('user');

        $merchant = $this->fixtures->create('merchant');

        $mappingData = [
            'user_id'     => $user->getId(),
            'merchant_id' => $merchant->getId(),
            'role'        => $role,
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        return $user->getId();
    }

    protected function validateAccesses(string $role)
    {
        // get owner route map
        $routes = $this->getUserRolePermissibleRouteMap($role);

        $this->assertNotNull($routes, "User Role $role Routes mapping cannot be null");

        foreach ($routes as $route)
        {
            $routePermission = $this->routePermissions[$route] ?? null;

            $this->assertNotNull($routePermission, "Route $route permission cannot be null");

            if ($routePermission === UserAccess::WILDCARD_PERMISSION)
            {
                continue;
            }

            $this->assertEquals(
                true,
                UserRolePermissionsMap::isValidRolePermission(BankingRole::OWNER, $routePermission),
                "Route $route permission missing for role $role");
        }
    }
}

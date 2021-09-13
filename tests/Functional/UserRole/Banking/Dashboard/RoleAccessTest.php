<?php

namespace RZP\Tests\Functional\UserRole;

use RZP\Http\Route;
use RZP\Models\User\BankingRole;
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

        $this->mockRazorXTreatmentAccessDenyUnauthorised("on");

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

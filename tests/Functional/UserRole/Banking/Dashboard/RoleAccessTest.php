<?php

namespace RZP\Tests\Functional\UserRole;

use RZP\Http\Middleware\UserAccess;
use RZP\Http\Route;
use RZP\Models\User\BankingRole;
use RZP\Tests\Functional\TestCase;
use RZP\Http\UserRolePermissionsMap;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\UserRole\Banking\Dashboard\BankingRoleTrait;

class RoleAccessTest extends TestCase
{
    use RequestResponseFlowTrait;
    use BankingRoleTrait;

    /**
     * @var UserRolePermissionsMap
     */
    private $userRolePermissionMap;
    /**
     * @var array
     */
    private $routePermissions;

    public function setUp()
    {
        parent::setUp();

        $this->userRolePermissionMap = new UserRolePermissionsMap();
        $this->routePermissions = Route::$bankingRoutePermissions;
    }

    public function testAllRoleAccesses()
    {
        // Validate for Owner Role
        $this->validateAccesses(BankingRole::OWNER);

        // Validate for Admin Role
        $this->validateAccesses(BankingRole::ADMIN);

        // Validate for Finance L1 Role
        $this->validateAccesses(BankingRole::FINANCE_L1);
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
                $this->userRolePermissionMap->isValidRolePermission(BankingRole::OWNER, $routePermission),
                "Route $route permission missing for role $role");
        }
    }
}

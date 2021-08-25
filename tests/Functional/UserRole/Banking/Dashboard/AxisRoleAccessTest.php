<?php

namespace RZP\Tests\Functional\UserRole;

use RZP\Http\Route;
use RZP\Models\User\AxisUserRole;
use RZP\Models\User\BankingRole;
use RZP\Tests\Functional\TestCase;
use RZP\Http\Middleware\UserAccess;
use RZP\Http\AxisCardsUser;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\UserRole\Banking\Dashboard\AxisBankingRoleTrait;

class AxisRoleAccessTest extends TestCase
{
    use RequestResponseFlowTrait;
    use AxisBankingRoleTrait;

    /**
     * @var array
     */
    private $routePermissions;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/AxisRoleAccessTestData.php';

        parent::setUp();

        $this->routePermissions = Route::$bankingRoutePermissions;
    }

    public function testFlowWhenAxisUserIsPresent()
    {
        $user = $this->fixtures->create('user',['id'  => '20000000000006']);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000000',
            'role'        =>  AxisUserRole::CC_ADMIN,
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->mockRazorXTreatmentAccessDenyUnauthorised("off");

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testFlowWhenBankingUserIsPresent()
    {
        $user = $this->fixtures->create('user',['id'  => '20000000000006']);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000000',
            'role'        =>  BankingRole::VIEW_ONLY,
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->mockRazorXTreatmentAccessDenyUnauthorised("off");

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }



    public function testAllRoleAccesses()
    {
        // Validate for Authorised Signatory Role
        $this->validateAccesses(AxisUserRole::AUTHORISED_SIGNATORY);

        // Validate for CC Admin Role
        $this->validateAccesses(AxisUserRole::CC_ADMIN);

        // Validate for Maker Role
        $this->validateAccesses(AxisUserRole::MAKER);

        // Validate for Maker Admin role
        $this->validateAccesses(AxisUserRole::MAKER_ADMIN);

        // Validate for Checker role
        $this->validateAccesses(AxisUserRole::CHECKER_L1);

        // Validate for View_Only role
        $this->validateAccesses(AxisUserRole::VIEWER);
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
                AxisCardsUser::isValidRolePermission(AxisUserRole::AUTHORISED_SIGNATORY, $routePermission),
                "Route $route permission missing for role $role");
        }
    }
}

<?php

namespace RZP\Tests\Functional\Admin;

use RZP\Models\Feature\Constants;
use RZP\Services\Mock\ApachePinotClient;
use RZP\Services\Mock\DruidService as MockDruidService;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\Admin;

class AdminReportsTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/AdminReportsTestData.php';

        parent::setUp();

        $this->addPermissionToBaAdmin(Admin\Permission\Name::VIEW_ADMIN_REPORTS);

        $this->ba->adminAuth();

        $this->ba->adminAuth();

        $this->mockApachePinot();
    }

    private function mockApachePinot()
    {
        $pinotService = $this->getMockBuilder(ApachePinotClient::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['getDataFromPinot'])
            ->getMock();

        $this->app->instance('apache.pinot', $pinotService);

        $pinotService->method('getDataFromPinot')
            ->willReturn(null);
    }

    public function testFiltersGetByType()
    {
        $this->startTest();
    }

    public function testGetReportsData()
    {
        $this->startTest();
    }

    public function testGetReportsByType()
    {
        $this->startTest();
    }

    public function testGetReportsForAdmin()
    {
        $this->startTest();
    }

    public function testReportsGetReportById()
    {
        $this->startTest();
    }

    protected function addPermissionToBaAdmin(string $permissionName): void
    {
        $admin = $this->ba->getAdmin();

        $roleOfAdmin = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => $permissionName]);

        $roleOfAdmin->permissions()->attach($perm->getId());
    }

}

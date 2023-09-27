<?php

namespace RZP\Tests\Functional\International;

use RZP\Services\PaymentsCrossBorderClient;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class CBPaymentConfigTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CBPaymentConfigTestData.php';
        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testCreateLRSConfigFromAdminAuth()
    {
        $this->ba->adminAuth();
        $admin = $this->ba->getAdmin();
        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $mockResponse = [
            "feature" => [
                "lrs_markup_percentage" => "2.5",
            ],
            "success" => true,
        ];
        $pxbServiceMock = $this->getMockBuilder(PaymentsCrossBorderClient::class)
            ->onlyMethods(['postDCSConfiguration'])->getMock();
        $this->app->instance('payments-cross-border', $pxbServiceMock);
        $pxbServiceMock->method("postDCSConfiguration")
            ->willReturn($mockResponse);

        $response = $this->startTest();
        $this->assertEquals($mockResponse,$response);
    }

    public function testFetchLRSConfigFromAdminAuth()
    {
        $this->ba->adminAuth();
        $admin = $this->ba->getAdmin();
        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $mockResponse = [
            "markup_percent" => 2.5,
            "success" => true,
        ];
        $pxbServiceMock = $this->getMockBuilder(PaymentsCrossBorderClient::class)
            ->onlyMethods(['getDCSConfiguration'])->getMock();
        $this->app->instance('payments-cross-border', $pxbServiceMock);
        $pxbServiceMock->method("getDCSConfiguration")
            ->willReturn($mockResponse);

        $response = $this->startTest();
        $this->assertEquals($mockResponse,$response);
    }
}

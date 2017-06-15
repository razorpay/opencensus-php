<?php

namespace RZP\Tests\Functional\VirtualAccount;

use Closure;
use Mockery;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class VirtualAccountTest extends TestCase
{
    use EntityActionTrait;
    use VirtualAccountTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/VirtualAccountTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->merchant->addFeatures(['virtual_accounts']);

        $this->ba->privateAuth();

        $this->customer = $this->getEntityById('customer', 'cust_100000customer');
    }

    public function testCreateVirtualAccount()
    {
        $response = $this->createVirtualAccount();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testFetchVirtualAccount()
    {
        $response = $this->createVirtualAccount();

        $response = $this->fetchVirtualAccount($response['id']);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testFetchVirtualAccounts()
    {
        $this->createVirtualAccount(['name' => 'First VA']);
        $this->createVirtualAccount(['name' => 'Second VA']);

        $response = $this->fetchVirtualAccounts();

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testEditVirtualAccount()
    {
        $virtualAccount = $this->createVirtualAccount();

        $response = $this->closeVirtualAccount($virtualAccount['id']);

        $this->assertEquals('closed', $response['status']);
    }

    public function testDeleteVirtualAccount()
    {
        $virtualAccount = $this->createVirtualAccount();

        $response = $this->deleteVirtualAccount($virtualAccount['id']);

        $this->assertEquals(true, $response['deleted']);

        $response = $this->fetchVirtualAccounts();

        $this->assertEquals(0, $response['count']);
    }

    public function testWebhookOnVirtualAccountPay()
    {
        $virtualAccount = $this->createVirtualAccount();

        $this->createWebhook(
            [
                'events' => [
                    'payment.captured' => '1',
                ]
            ]);

        $testData = $this->testData[__FUNCTION__];

        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertArraySelectiveEquals($testData, $data);

            return true;
        });

        $this->payVirtualAccount($virtualAccount['id']);
    }

    protected function mockInfernoFire(Closure $closure)
    {
        $class = \RZP\Models\Merchant\Webhook\Inferno::class;

        $inferno = Mockery::mock($class, [])->makePartial();

        $inferno->shouldReceive('fire')
                ->once()
                ->with(
                    Mockery::type('RZP\Jobs\WebHook'),
                    Mockery::on($closure));

        $this->app->instance('webhook.inferno', $inferno);
    }
}

<?php

namespace RZP\Tests\Functional\VirtualAccount;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class VirtualAccountTest extends TestCase
{
    use RequestResponseFlowTrait;
    use VirtualAccountTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/VirtualAccountTestData.php';

        parent::setUp();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

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

    public function testAccountCreditedWebhook()
    {
        $this->markTestSkipped();

        $this->createWebhook(
            [
                'events' => [
                    'virtual_account.credited' => '1',
                ]
            ]);

        $testData = [];

        $this->mockInfernoFire(function ($data) use ($testData)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertArraySelectiveEquals($testData, $data);

            $this->assertArrayHasKey('webhook_id', $data);
            $this->assertArrayHasKey('created_at', $data['event']);

            $payload = $data['event']['payload'];

            $bankTransfer = $payload['bank_transfer']['entity'];

            $this->assertArrayHasKey('id', $bankTransfer);
            $this->assertArrayHasKey('payment_id', $bankTransfer);
            $this->assertArrayHasKey('transaction_id', $bankTransfer);

            return true;
        });

        $this->ba->appAuth();

        $this->testBankTransferPay();
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

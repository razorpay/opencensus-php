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

    public function testCreateVirtualAccountWithDescriptor()
    {
        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() {
            $this->createVirtualAccount(['descriptor' => 'somedesc']);
        });

        $this->fixtures->merchant->setHandle('hand');

        $this->createVirtualAccount(['descriptor' => 'somedesc']);

        $vba = $this->getLastEntity('bank_account', true);
        $this->assertRegexp("/.{4}HAND.{2}SOMEDESC$/", $vba['account_number']);
    }

    public function testCreateVirtualAccountWithIdenticalDescriptor()
    {
        $this->fixtures->merchant->setHandle('hand');

        $this->createVirtualAccount(['descriptor' => 'samedesc']);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() {
            $this->createVirtualAccount(['descriptor' => 'samedesc']);
        });
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

    public function testVirtualAccountPay()
    {
        $virtualAccount = $this->createVirtualAccount();

        $this->payVirtualAccount($virtualAccount['id'], ['amount' => 5000]);
        $virtualAccount = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(5000, $virtualAccount['amount_paid']);
        $this->assertEquals('active', $virtualAccount['status']);

        $this->payVirtualAccount($virtualAccount['id'], ['amount' => 5000]);
        $virtualAccount = $this->getLastEntity('virtual_account', true);
        $this->assertEquals(10000, $virtualAccount['amount_paid']);
        $this->assertEquals('paid', $virtualAccount['status']);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);
        $this->assertEquals($virtualAccount['id'], $bankTransfer['virtual_account_id']);
    }

    public function testVirtualAccountForCustomer()
    {
        $virtualAccount = $this->createVirtualAccount(['customer_id' => 'cust_100000customer']);

        $this->assertEquals('cust_100000customer', $virtualAccount['customer_id']);

        $this->payVirtualAccount($virtualAccount['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('cust_100000customer', $payment['customer_id']);
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

            $this->assertEquals('payment.captured', $data['event']['event']);

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

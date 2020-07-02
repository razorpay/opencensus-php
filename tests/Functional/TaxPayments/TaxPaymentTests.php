<?php

namespace RZP\Tests\Functional\TaxPayments;

use App;
use Mockery;
use RZP\Models\Settings\Accessor;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payout\SourceUpdater;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class TaxPaymentTests extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    protected $config;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/TaxPaymentsData.php';

        parent::setUp();

        $this->config = App::getFacadeRoot()['config'];
    }

    public function testSettingsInternalApiAddOrUpdate()
    {
        // for vendor-payment internal auth
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->startTest();

        // you should also call the GET API and check if the value was actually updated or not?
        // no just check by calling the Accessors
        $merchant = $this->getDbEntity('merchant', ['id' => '10000000000000']);

        $keys = Accessor::for($merchant, 'tax_payments')->all();

        $this->assertEquals($keys['test_key'], 'test_value');
    }

    public function testSettingsInternalApiGet()
    {
        // calling POST api to create the settings keys
        $this->testSettingsInternalAPIAddOrUpdate();

        // for vendor-payment internal auth
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->startTest();
    }

    public function testTaxPaymentSettingGetCallsServiceMethods()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('getAllSettings')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        // assert that the Payout Update Status was called when feature was enabled
        $tpMock->shouldHaveReceived('getAllSettings');
    }

    public function testTaxPaymentSettingAddOrUpdateCallsServiceMethods()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('addOrUpdateSettings')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        // assert that the Payout Update Status was called when feature was enabled
        $tpMock->shouldHaveReceived('addOrUpdateSettings');
    }

    public function testGetTaxPaymentCallsServiceMethod()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('getTaxPayment')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        // assert that the Payout Update Status was called when feature was enabled
        $tpMock->shouldHaveReceived('getTaxPayment');
    }

    public function testListTaxPaymentCallsServiceMethod()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('listTaxPayments')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        // assert that the Payout Update Status was called when feature was enabled
        $tpMock->shouldHaveReceived('listTaxPayments');
    }
}

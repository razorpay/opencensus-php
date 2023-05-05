<?php

namespace Functional\Payout;

use RZP\Error\ErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Services\Dcs\Configurations\Service as DcsConfigService;

class PayoutsConfigurationsTest extends TestCase
{
    use HeimdallTrait;
    use RequestResponseFlowTrait;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutsConfigurationTestData.php';

        parent::setUp();
    }

    public function mockDcsCreateConfiguration() {

        $dcsConfigService = $this->getMockBuilder( DcsConfigService::class)
                                 ->setConstructorArgs([$this->app])
                                 ->getMock();

        $this->app->instance('dcs_config_service', $dcsConfigService);

        $this->app['dcs_config_service']
             ->method('createConfiguration')
             ->willReturn(null);
    }

    public function mockDcsCreateConfigurationThrowBadRequest() {

        $dcsConfigService = $this->getMockBuilder( DcsConfigService::class)
                                 ->setConstructorArgs([$this->app])
                                 ->getMock();

        $this->app->instance('dcs_config_service', $dcsConfigService);

        $this->app['dcs_config_service']
             ->method('createConfiguration')
             ->willThrowException(
                 new BadRequestException(
                     ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
                     null,
                     null,
                     "microservice error"
                 )
             );
    }

    public function mockDcsEditConfiguration() {

        $dcsConfigService = $this->getMockBuilder( DcsConfigService::class)
                                 ->setConstructorArgs([$this->app])
                                 ->getMock();

        $this->app->instance('dcs_config_service', $dcsConfigService);

        $this->app['dcs_config_service']
             ->method('editConfiguration')
             ->willReturn(null);
    }

    public function mockDcsFetchConfiguration() {

        $dcsConfigService = $this->getMockBuilder( DcsConfigService::class)
                                 ->setConstructorArgs([$this->app])
                                 ->getMock();

        $this->app->instance('dcs_config_service', $dcsConfigService);

        $this->app['dcs_config_service']
             ->method('fetchConfiguration')
             ->willReturn([
                 "allowed_upi_channels" => ["axis"]
             ]);
    }

    public function testCreatePayoutModeConfig()
    {
        $this->ba->adminAuth();

        $this->mockDcsCreateConfiguration();

        $this->startTest();
    }

    public function testCreatePayoutModeConfigFailureFromDcsLayer()
    {
        $this->ba->adminAuth();

        $this->mockDcsCreateConfigurationThrowBadRequest();

        $this->startTest();
    }

    public function testCreatePayoutModeConfigValidationFailure()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testEditPayoutModeConfig()
    {
        $this->ba->adminAuth();

        $this->mockDcsEditConfiguration();

        $this->startTest();
    }

    public function testEditPayoutModeConfigValidationFailure()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testFetchPayoutModeConfig()
    {
        $this->ba->adminAuth();

        $this->mockDcsFetchConfiguration();

        $this->startTest();
    }

    public function testFetchPayoutModeConfigValidationFailure()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

}

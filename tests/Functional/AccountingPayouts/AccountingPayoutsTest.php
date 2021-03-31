<?php

namespace RZP\Tests\Functional\AccountingPayouts;

use Mockery;

use App;

use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class AccountingPayoutsTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    protected $config;

    private $ownerRoleUser;

    private $finL1RoleUser;

    private $viewOnlyRoleUser;

    private $opsRoleUser;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/AccountingPayoutsData.php';

        parent::setUp();

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->setupUserRoles();

        $this->config = App::getFacadeRoot()['config'];

    }

    public function setupUserRoles()
    {
        $this->ownerRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'owner', 'live');

        $this->finL1RoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'finance_l1', 'live');

        $this->viewOnlyRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'view_only', 'live');

        $this->opsRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'operations', 'live');
    }

    protected function mockRazorxTreatment(string $returnValue = 'On')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn($returnValue);
    }

    public function testGetIntegrationUrlServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('getIntegrationURL')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('getIntegrationURL');
    }


    public function testInitiateIntegrationServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('integrationAppInitiate')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('integrationAppInitiate');
    }

    public function testIntegrationStatusServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('integrationStatus')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('integrationStatus');
    }

    public function testIntegrationStatusAppServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('integrationStatusApp')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('integrationStatusApp');
    }

    public function testCallbackServiceMethod()
    {
        $this->ba->directAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('callback')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('callback');
    }

    public function testAppCredentialsServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('appCredentials')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('appCredentials');
    }

    public function testDeleteServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('deleteIntegration')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('deleteIntegration');
    }

    public function testSyncStatusServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('syncStatus')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('syncStatus');
    }


    public function testSyncServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('sync')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('sync');
    }

    public function testWaitlistServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('waitlist')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('waitlist');
    }

    public function testCallbackRequestCalledDirectly()
    {
        $apMock = Mockery::mock('RZP\Services\AccountingPayouts\Service')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $apMock->shouldAllowMockingMethod('request')
            ->shouldReceive('request')
            ->andReturn([]);

        $apMock->shouldAllowMockingMethod('makeRequest')
            ->shouldReceive('makeRequest')
            ->never();

        $apMock->callback([]);
    }

    public function testIntegrationStatusForViewOnlyUsers()
    {
        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('integrationStatus')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->mockRazorxTreatment('on');

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->viewOnlyRoleUser->getId());

        $this->startTest();

        $apMock->shouldHaveReceived('integrationStatus');
    }

    public function testSyncStatusForViewOnlyUsers()
    {
        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('syncStatus')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->mockRazorxTreatment('on');

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->viewOnlyRoleUser->getId());

        $this->startTest();

        $apMock->shouldHaveReceived('syncStatus');
    }

}

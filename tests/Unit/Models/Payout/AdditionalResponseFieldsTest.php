<?php

namespace Unit\Models\Payout;

use Mockery;
use RZP\Error\ErrorCode;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\Route;
use RZP\Models\Payout\Status;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Validator;
use RZP\Models\FundAccount;
use RZP\Models\Payout\Core;
use RZP\Services\Mock\SplitzService;
use RZP\Tests\TestCase;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Diag\EventCode;

class AdditionalResponseFieldsTest extends TestCase
{
    protected $mockTrace;
    protected $mockRoute;
    protected $mockSplitz;
    protected $mockBasicAuth;
    protected $mockPayoutEntity;
    protected $mockRepo;
    protected $mockBalanceEntity;
    protected $mockBalanceRepo;
    protected $payoutCore;
    protected $payouEntity;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock all dependencies with shouldIgnoreMissing to handle framework calls
        $this->mockTrace = Mockery::mock()->shouldIgnoreMissing();
        $this->mockBasicAuth = Mockery::mock(BasicAuth::class, [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();
        $this->mockRoute = Mockery::mock(Route::class,[$this->app])->makePartial();
        $this->mockSplitz = Mockery::mock(SplitzService::class, [$this->app])->makePartial();
        $this->mockRepo = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app])->makePartial();

        // Inject mocks into app container
        $this->app->instance('basicauth', $this->mockBasicAuth);
        $this->app->instance('trace', $this->mockTrace);
        $this->app->instance('api.route', $this->mockRoute);
        $this->app->instance('splitzService', $this->mockSplitz);

        // Repo Mock
        $this->mockBalanceRepo = Mockery::mock('RZP\Models\Merchant\Balance\Repository');
        $this->mockRepo->shouldReceive('driver')->with('balance')->andReturn($this->mockBalanceRepo);
        $this->app->instance('repo', $this->mockRepo);

        // Entity Mock
        $this->mockPayoutEntity = Mockery::mock('RZP\Models\Payout\Entity')->makePartial();
        $this->mockBalanceEntity = Mockery::mock('RZP\Models\Balance\Entity')->makePartial();
        $this->payoutCore = new Core();
        $this->payouEntity = new Entity();
    }

    public function testAddAdditionalFieldsToResponseSourceUnknown()
    {
        $expectedPayload = [];
        $payload = [];
        $payload = $this->payoutCore->addAdditionalFieldsToResponse($payload,$this->mockPayoutEntity,'unknown');
        $this->assertEquals($expectedPayload,$payload);
    }


    public function testAddAdditionalFieldsToResponseAPI()
    {
        $this->mockPayoutEntity->shouldReceive('getId')->andReturn('test_payout_id');
        $this->mockPayoutEntity->shouldReceive('getMerchantId')->andReturn('test_merchant_id');
        $this->mockPayoutEntity->shouldReceive('getBalanceId')->andReturn('test_balance_id');
        $this->mockPayoutEntity->shouldReceive('getPayoutType')->andReturn('default');
        $this->mockRoute->shouldReceive('getCurrentRouteName')->once()->andReturn(Entity::PAYOUT_FETCH_BY_ID);
        $this->mockBasicAuth->shouldReceive('isPrivateAuth')->once()->andReturn(true);
        $this->mockBalanceRepo->shouldReceive('findOrFailById')->once()->andReturn($this->mockBalanceEntity);
        $this->mockBalanceEntity->shouldReceive('getAccountNumber')->twice()->andReturn('test_account_number');

        $expectedPayload = ['debit_account_number' => 'test_account_number'];
        $payload = [];
        $payload = $this->payoutCore->addAdditionalFieldsToResponse($payload,$this->mockPayoutEntity,Entity::API);
        $this->assertEquals($expectedPayload,$payload);
    }

    public function testAddAdditionalFieldsToResponseWebhook()
    {
        $this->mockPayoutEntity->shouldReceive('getId')->andReturn('test_payout_id');
        $this->mockPayoutEntity->shouldReceive('getMerchantId')->andReturn('test_merchant_id');
        $this->mockPayoutEntity->shouldReceive('getBalanceId')->andReturn('test_balance_id');
        $this->mockPayoutEntity->shouldReceive('getPayoutType')->andReturn('default');
        $this->mockBalanceRepo->shouldReceive('findOrFailById')->once()->andReturn($this->mockBalanceEntity);
        $this->mockBalanceEntity->shouldReceive('getAccountNumber')->twice()->andReturn('test_account_number');

        $expectedPayload = ['debit_account_number' => 'test_account_number'];
        $payload = [];
        $payload = $this->payoutCore->addAdditionalFieldsToResponse($payload,$this->mockPayoutEntity,Entity::WEBHOOK);
        $this->assertEquals($expectedPayload,$payload);
    }

    public function testAddAdditionalFieldsToResponseAPINotRequiredForSubAccountPayout()
    {
        $this->mockPayoutEntity->shouldReceive('getId')->andReturn('test_payout_id');
        $this->mockPayoutEntity->shouldReceive('getMerchantId')->andReturn('test_merchant_id');
        $this->mockPayoutEntity->shouldReceive('getBalanceId')->andReturn('test_balance_id');
        $this->mockPayoutEntity->shouldReceive('getPayoutType')->andReturn(Entity::SUB_ACCOUNT);

        $expectedPayload = [];
        $payload = [];
        $payload = $this->payoutCore->addAdditionalFieldsToResponse($payload,$this->mockPayoutEntity,Entity::API);
        $this->assertEquals($expectedPayload,$payload);
    }

    public function testAddAdditionalFieldsToResponseWebhookNotRequiredForSubAccountPayout()
    {
        $this->mockPayoutEntity->shouldReceive('getId')->andReturn('test_payout_id');
        $this->mockPayoutEntity->shouldReceive('getMerchantId')->andReturn('test_merchant_id');
        $this->mockPayoutEntity->shouldReceive('getBalanceId')->andReturn('test_balance_id');
        $this->mockPayoutEntity->shouldReceive('getPayoutType')->andReturn(Entity::SUB_ACCOUNT);

        $expectedPayload = [];
        $payload = [];
        $payload = $this->payoutCore->addAdditionalFieldsToResponse($payload,$this->mockPayoutEntity,Entity::WEBHOOK);
        $this->assertEquals($expectedPayload,$payload);
    }

    public function testAddAdditionalFieldsToResponseAPIRouteNotAllowed()
    {
        $this->mockPayoutEntity->shouldReceive('getId')->andReturn('test_payout_id');
        $this->mockPayoutEntity->shouldReceive('getMerchantId')->andReturn('test_merchant_id');
        $this->mockPayoutEntity->shouldReceive('getBalanceId')->andReturn('test_balance_id');
        $this->mockPayoutEntity->shouldReceive('getPayoutType')->andReturn('default');
        $this->mockRoute->shouldReceive('getCurrentRouteName')->once()->andReturn(Entity::PAYOUT_FETCH_MULTIPLE_ALL);

        $expectedPayload = [];
        $payload = [];
        $payload = $this->payoutCore->addAdditionalFieldsToResponse($payload,$this->mockPayoutEntity,Entity::API);
        $this->assertEquals($expectedPayload,$payload);
    }

    public function testAddAdditionalFieldsToResponseAPIAuthNotPrivate()
    {
        $this->mockPayoutEntity->shouldReceive('getId')->andReturn('test_payout_id');
        $this->mockPayoutEntity->shouldReceive('getMerchantId')->andReturn('test_merchant_id');
        $this->mockPayoutEntity->shouldReceive('getBalanceId')->andReturn('test_balance_id');
        $this->mockPayoutEntity->shouldReceive('getPayoutType')->andReturn('default');
        $this->mockRoute->shouldReceive('getCurrentRouteName')->once()->andReturn(Entity::PAYOUT_FETCH_BY_ID);
        $this->mockBasicAuth->shouldReceive('isPrivateAuth')->once()->andReturn(false);

        $expectedPayload = [];
        $payload = [];
        $payload = $this->payoutCore->addAdditionalFieldsToResponse($payload,$this->mockPayoutEntity,Entity::API);
        $this->assertEquals($expectedPayload,$payload);
    }

    public function testAddAdditionalFieldsToResponseAccountNumberMissing()
    {
        $this->mockPayoutEntity->shouldReceive('getId')->andReturn('test_payout_id');
        $this->mockPayoutEntity->shouldReceive('getMerchantId')->andReturn('test_merchant_id');
        $this->mockPayoutEntity->shouldReceive('getBalanceId')->andReturn('test_balance_id');
        $this->mockPayoutEntity->shouldReceive('getPayoutType')->andReturn('default');
        $this->mockBalanceRepo->shouldReceive('findOrFailById')->once()->andReturn($this->mockBalanceEntity);
        $this->mockBalanceEntity->shouldReceive('getAccountNumber')->once()->andReturn('');

        $expectedPayload = [];
        $payload = [];
        $payload = $this->payoutCore->addAdditionalFieldsToResponse($payload,$this->mockPayoutEntity,Entity::WEBHOOK);
        $this->assertEquals($expectedPayload,$payload);
    }

    public function testAddAdditionalFieldsToResponseBalanceFetchFailed()
    {
        $this->mockPayoutEntity->shouldReceive('getId')->andReturn('test_payout_id');
        $this->mockPayoutEntity->shouldReceive('getMerchantId')->andReturn('test_merchant_id');
        $this->mockPayoutEntity->shouldReceive('getBalanceId')->andReturn('test_balance_id');
        $this->mockPayoutEntity->shouldReceive('getPayoutType')->andReturn('default');
        $this->mockBalanceRepo->shouldReceive('findOrFailById')->once()->andThrow(new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,null,null, 'No db records found'));

        $expectedPayload = [];
        $payload = [];
        $payload = $this->payoutCore->addAdditionalFieldsToResponse($payload,$this->mockPayoutEntity,Entity::WEBHOOK);
        $this->assertEquals($expectedPayload,$payload);
    }

}

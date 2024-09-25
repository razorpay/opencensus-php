<?php

namespace Unit\Models\Merchant;

use Mockery;
use ReflectionClass;
use RZP\Models\Merchant\Methods\Entity;
use Tests\Unit\TestCase;
use RZP\Models\Merchant\AccountV2\Response;
use RZP\Models\Merchant\Constants as MerchantConstants;
use RZP\Models\Merchant\Methods\Entity as EntityMethods;
use RZP\Trace\TraceCode;
use Illuminate\Support\Facades\Cache;
use RZP\Models\Merchant\Methods\Metric;

class MerchantAccountV2Test extends TestCase
{

    protected $merchantEntityMock;

    protected $merchantEmailEntityMock;

    protected $merchantDetailEntityMock;

    protected $partnerEntityMock;

    protected $splitzService;

    const DEFAULT_MERCHANT_ID    = '10000000000000';


    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestDependencyMocks();
    }

    public function testAccountV2ResponseWhenActivationStatusIsUnderReview()
    {
        $accountResponse = new Response();

        $this->repoMock->shouldReceive('driver')->with('merchant_email')->andReturn($this->merchantEmailEntityMock);

        $this->merchantEmailEntityMock->shouldReceive('getEmailByMerchantId')->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getMerchantId')->andReturn('10000000000001');


        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        $this->merchantEntityMock->shouldReceive('isSuspended')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('getCreatedAt')->andReturn('1678107805');

        $this->merchantEntityMock->shouldReceive('isFundsOnHold')->andReturn(true);

        $this->merchantEntityMock->shouldReceive('getBillingLabel')->andReturn('billing_name');

        $this->merchantDetailEntityMock->shouldReceive('getActivationStatus')->andReturn('under_review');

        $this->partnerEntityMock->shouldReceive('getId')->andReturn('10000000000000');

        $result = $accountResponse->getAccountResponse($this->partnerEntityMock, $this->merchantEntityMock);

        $this->assertEquals('under_review', $result['status']);
        $this->assertEquals(true, $result['hold_funds']);
        $this->assertArrayNotHasKey('activated_at', $result);
    }

    public function testAccountV2ResponseWhenActivationStatusIsActivated()
    {
        $accountResponse = new Response();

        $this->repoMock->shouldReceive('driver')->with('merchant_email')->andReturn($this->merchantEmailEntityMock);

        $this->merchantEmailEntityMock->shouldReceive('getEmailByMerchantId')->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getMerchantId')->andReturn('10000000000001');

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        $this->merchantEntityMock->shouldReceive('isSuspended')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('getCreatedAt')->andReturn('1678107805');

        $this->merchantEntityMock->shouldReceive('isFundsOnHold')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('getBillingLabel')->andReturn('billing_name');

        $this->merchantEntityMock->shouldReceive('getActivatedAt')->andReturn('1678107805');

        $this->merchantDetailEntityMock->shouldReceive('getActivationStatus')->andReturn('activated');

        $this->partnerEntityMock->shouldReceive('getId')->andReturn('10000000000000');

        $result = $accountResponse->getAccountResponse($this->partnerEntityMock, $this->merchantEntityMock);

        $this->assertEquals('activated', $result['status']);
        $this->assertEquals(false, $result['hold_funds']);
        $this->assertArrayHasKey('activated_at', $result);
    }

    public function testAccountV2ResponseWhenActivationStatusIsInstantlyActivated()
    {
        $accountResponse = new Response();

        $this->repoMock->shouldReceive('driver')->with('merchant_email')->andReturn($this->merchantEmailEntityMock);

        $this->merchantEmailEntityMock->shouldReceive('getEmailByMerchantId')->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getMerchantId')->andReturn('10000000000001');

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        $this->merchantEntityMock->shouldReceive('isSuspended')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('getCreatedAt')->andReturn('1678107805');

        $this->merchantEntityMock->shouldReceive('isFundsOnHold')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('getBillingLabel')->andReturn('billing_name');

        $this->merchantEntityMock->shouldReceive('getActivatedAt')->andReturn('1678107805');

        $this->merchantDetailEntityMock->shouldReceive('getActivationStatus')->andReturn('instantly_activated');

        $this->partnerEntityMock->shouldReceive('getId')->andReturn('10000000000000');

        $result = $accountResponse->getAccountResponse($this->partnerEntityMock, $this->merchantEntityMock);

        $this->assertEquals('instantly_activated', $result['status']);
        $this->assertEquals(false, $result['hold_funds']);
    }

    public function testAccountV2ResponseWhenActivationStatusIsNeedsClarification()
    {
        $accountResponse = new Response();

        $this->repoMock->shouldReceive('driver')->with('merchant_email')->andReturn($this->merchantEmailEntityMock);

        $this->merchantEmailEntityMock->shouldReceive('getEmailByMerchantId')->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getMerchantId')->andReturn('10000000000001');

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        $this->merchantEntityMock->shouldReceive('isSuspended')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('getCreatedAt')->andReturn('1678107805');

        $this->merchantEntityMock->shouldReceive('isFundsOnHold')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('getBillingLabel')->andReturn('billing_name');

        $this->merchantDetailEntityMock->shouldReceive('getActivationStatus')->andReturn('needs_clarification');

        $this->partnerEntityMock->shouldReceive('getId')->andReturn('10000000000000');

        $result = $accountResponse->getAccountResponse($this->partnerEntityMock, $this->merchantEntityMock);

        $this->assertEquals('needs_clarification', $result['status']);
        $this->assertEquals(false, $result['hold_funds']);
        $this->assertArrayNotHasKey('activated_at', $result);
    }

    public function testAccountV2ResponseWhenActivationStatusIsActivatedKycPending()
    {
        $accountResponse = new Response();

        $this->repoMock->shouldReceive('driver')->with('merchant_email')->andReturn($this->merchantEmailEntityMock);

        $this->merchantEmailEntityMock->shouldReceive('getEmailByMerchantId')->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getMerchantId')->andReturn('10000000000001');

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        $this->merchantEntityMock->shouldReceive('isSuspended')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('getCreatedAt')->andReturn('1678107805');

        $this->merchantEntityMock->shouldReceive('isFundsOnHold')->andReturn(false);

        $this->merchantEntityMock->shouldReceive('getBillingLabel')->andReturn('billing_name');

        $this->merchantEntityMock->shouldReceive('getActivatedAt')->andReturn('1678107805');

        $this->merchantDetailEntityMock->shouldReceive('getActivationStatus')->andReturn('activated_kyc_pending');

        $this->partnerEntityMock->shouldReceive('getId')->andReturn('10000000000000');

        $result = $accountResponse->getAccountResponse($this->partnerEntityMock, $this->merchantEntityMock);

        $this->assertEquals('activated_kyc_pending', $result['status']);
        $this->assertEquals(false, $result['hold_funds']);
        $this->assertArrayNotHasKey('activated_at', $result);
    }

    public function testAccountV2ResponseWhenActivationStatusIsSuspended()
    {
        $accountResponse = new Response();

        $this->repoMock->shouldReceive('driver')->with('merchant_email')->andReturn($this->merchantEmailEntityMock);

        $this->merchantEmailEntityMock->shouldReceive('getEmailByMerchantId')->andReturn([]);

        $this->merchantEntityMock->shouldReceive('getMerchantId')->andReturn('10000000000001');

        $this->merchantEntityMock->shouldReceive('getAttribute')->with('merchantDetail')->andReturn($this->merchantDetailEntityMock);

        $this->merchantEntityMock->shouldReceive('isSuspended')->andReturn(true);

        $this->merchantEntityMock->shouldReceive('getCreatedAt')->andReturn('1678107805');

        $this->merchantEntityMock->shouldReceive('isFundsOnHold')->andReturn(true);

        $this->merchantEntityMock->shouldReceive('getBillingLabel')->andReturn('billing_name');

        $this->merchantEntityMock->shouldReceive('getSuspendedAt')->andReturn('1678107805');

        $this->merchantDetailEntityMock->shouldReceive('getActivationStatus')->andReturn('activated_kyc_pending');

        $this->partnerEntityMock->shouldReceive('getId')->andReturn('10000000000000');

        $result = $accountResponse->getAccountResponse($this->partnerEntityMock, $this->merchantEntityMock);

        $this->assertEquals('suspended', $result['status']);
        $this->assertEquals(true, $result['hold_funds']);
        $this->assertArrayHasKey('suspended_at', $result);
    }

    private function createTestDependencyMocks()
    {
        $this->merchantEntityMock = Mockery::mock('RZP\Models\Merchant\Entity')->makePartial()->shouldAllowMockingProtectedMethods();

        $this->merchantEmailEntityMock = Mockery::mock('RZP\Models\Merchant\Email\Entity');

        $this->merchantDetailEntityMock = Mockery::mock('RZP\Models\Merchant\Detail\Entity')->makePartial();

        $this->partnerEntityMock = Mockery::mock('RZP\Models\Merchant\Entity');

        $this->merchantCore = Mockery::mock('RZP\Models\Merchant\Core');

        $this->mockSplitzExperiment();
    }

    private function mockSplitzExperiment()
    {
        $this->splitzService = Mockery::mock('RZP\Services\SplitzService');

        $this->app->instance('splitzService', $this->splitzService);

        $output["response"]["variant"]["name"] = "enable";

        $this->splitzService
            ->shouldReceive('evaluateRequest')
            ->andReturn($output);
    }

    protected function mockTrace()
    {
        $mock = Mockery::mock('Razorpay\Trace\Logger');
        $this->app->instance('trace', $mock);
        return $mock;
    }

    protected function setProtectedProperty($object, $propertyName, $value)
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    public function testGetMethodsWithMerchantV2DataIsCachedWhenNull()
    {
        // Mock the merchant entity
        $merchantMock = Mockery::mock('RZP\Models\Merchant\Entity')->makePartial()->shouldAllowMockingProtectedMethods();
        $merchantMock->shouldReceive('getId')->andReturn(self::DEFAULT_MERCHANT_ID);

        // Mock the MethodsEntity returned by the query
        $methodsMock = Mockery::mock('RZP\Models\Merchant\Methods\Entity');
        $methodsMock->shouldReceive('toJson')->andReturn('{"id": 1, "method": "credit_card"}');
        $methodsMock->shouldReceive('merchant')->andReturnSelf();
        $methodsMock->shouldReceive('associate')->once();

        // Mock the query builder
        $queryMock = Mockery::mock('QueryBuilderClass'); // Replace with actual QueryBuilder class
        $queryMock->shouldReceive('where')
            ->with(EntityMethods::MERCHANT_ID, '=', self::DEFAULT_MERCHANT_ID)
            ->andReturnSelf();
        $queryMock->shouldReceive('orderBy')
            ->with('created_at', 'desc')
            ->andReturnSelf();
        $queryMock->shouldReceive('first')
            ->andReturn($methodsMock);

        // Mock the MethodsRepository and its methods
        $methodsRepoMock=Mockery::mock('RZP\Models\Merchant\Methods\Repository')->makePartial();
        $methodsRepoMock->shouldAllowMockingProtectedMethods();
        $methodsRepoMock->shouldReceive('newQuery')->andReturn($queryMock);

        // Mock the trace logger
        $traceMock = $this->mockTrace();
        $this->setProtectedProperty($methodsRepoMock, 'trace', $traceMock);

        // Expect the trace methods to be called
        $traceMock->shouldReceive('info')
            ->once()
            ->with(TraceCode::SET_METHODS_ON_EXPERIMENT, [
                'merchant' => 'merchantV2'
            ]);
        $traceMock->shouldReceive('count')
            ->once()
            ->with(
                Metric::PAYMENT_METHODS_READ_METRIC,
                [
                    'route' => $methodsRepoMock->fetchRouteName(),
                    'function' => 'getMethodsForMerchantV2'
                ]
            );

        // Mock the Cache facade
        Cache::shouldReceive('get')
            ->with('methods_' . self::DEFAULT_MERCHANT_ID)
            ->andReturn(null);
        Cache::shouldReceive('put')
            ->once()
            ->with('methods_' . self::DEFAULT_MERCHANT_ID, '{"id": 1, "method": "credit_card"}', Mockery::any());

        // Call the method under test
        $result = $methodsRepoMock->getMethodsForMerchantV2($merchantMock);

        // Assert the result is not null
        $this->assertNotNull($result);

        // Check that data is cached
        Cache::shouldHaveReceived('put')
            ->once()
            ->with('methods_' . self::DEFAULT_MERCHANT_ID, '{"id": 1, "method": "credit_card"}', Mockery::any());
    }

    public function testGetMethodsWithMerchantV2DataIsCachedWhenNonNull()
    {
        // Mock the merchant entity
        $merchantMock = Mockery::mock('RZP\Models\Merchant\Entity')->makePartial()->shouldAllowMockingProtectedMethods();
        $merchantMock->shouldReceive('getId')->andReturn(self::DEFAULT_MERCHANT_ID);

        // Mock the MethodsEntity returned by the query
        $methodsMock = Mockery::mock('RZP\Models\Merchant\Methods\Entity');
        $methodsMock->shouldReceive('toJson')->andReturn('{"id": 1, "method": "credit_card"}');
        $methodsMock->shouldReceive('merchant')->andReturnSelf();

        // Mock the query builder
        $queryMock = Mockery::mock('QueryBuilderClass'); // Replace with actual QueryBuilder class
        $queryMock->shouldReceive('where')
            ->with(EntityMethods::MERCHANT_ID, '=', self::DEFAULT_MERCHANT_ID)
            ->andReturnSelf();
        $queryMock->shouldReceive('orderBy')
            ->with('created_at', 'desc')
            ->andReturnSelf();
        $queryMock->shouldReceive('first')
            ->andReturn($methodsMock);

        // Mock the MethodsRepository and its methods
        $methodsRepoMock=Mockery::mock('RZP\Models\Merchant\Methods\Repository')->makePartial();
        $methodsRepoMock->shouldAllowMockingProtectedMethods();
        $methodsRepoMock->shouldReceive('newQuery')->andReturn($queryMock);

        // Mock the trace logger
        $traceMock = $this->mockTrace();
        $this->setProtectedProperty($methodsRepoMock, 'trace', $traceMock);

        // Expect the trace methods to be called
        $traceMock->shouldReceive('info')
            ->once()
            ->with(TraceCode::SET_METHODS_ON_EXPERIMENT, [
                'merchant' => 'merchantV2'
            ]);
        $traceMock->shouldReceive('count')
            ->once()
            ->with(
                Metric::PAYMENT_METHODS_READ_METRIC,
                [
                    'route' => $methodsRepoMock->fetchRouteName(),
                    'function' => 'getMethodsForMerchantV2'
                ]
            );

        // Mock the Cache facade
        Cache::shouldReceive('get')
            ->with('methods_' . self::DEFAULT_MERCHANT_ID)
            ->andReturn('{"id": 1, "method": "credit_card"}');

        // Call the method under test
        $result = $methodsRepoMock->getMethodsForMerchantV2($merchantMock);

        // Assert the result is not null
        $this->assertNotNull($result);

        // Check that data is cached
        Cache::shouldNotHaveReceived('put', [
            'methods_' . self::DEFAULT_MERCHANT_ID,
            '{"id": 1, "method": "credit_card"}',
            Mockery::any()
        ]);
    }


}

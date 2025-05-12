<?php

namespace RZP\Tests\Unit\Models\Merchant\Methods;

use Mockery;
use RZP\Tests\TestCase;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Methods\Entity as MethodsEntity;
use RZP\Models\Merchant\Methods\Repository as MethodsRepository;
use RZP\Models\Merchant\Methods\PaymentMethodsService;
use RZP\Exception\IntegrationException;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class RepositoryTest extends TestCase
{
    protected $merchant;
    protected $methodsRepoMock;
    protected $paymentMethodsServiceMock;
    protected $traceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = (new MerchantEntity)->forceFill(['id' => 'merchant_test1']);

        // Mock the dependencies
        $this->paymentMethodsServiceMock = Mockery::mock(PaymentMethodsService::class)->makePartial();
        $this->traceMock = Mockery::mock(Trace::class)->makePartial();
        $this->traceMock->shouldReceive('count')->andReturnNull();
        $this->traceMock->shouldReceive('info')->andReturnNull();
        $this->traceMock->shouldReceive('warning')->andReturnNull();
        $this->traceMock->shouldReceive('traceException')->andReturnNull();

        $this->methodsRepoMock = Mockery::mock(MethodsRepository::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app['payment_methods_service'] = $this->paymentMethodsServiceMock;
        $this->app['trace'] = $this->traceMock;
        $this->methodsRepoMock->__construct();

        $this->methodsRepoMock->shouldReceive('fetchRouteName')->andReturn('test_route');

        if (!defined('RZP\Trace\TraceCode::PAYMENT_METHODS_USING_SERVICE_DATA')) {
            define('RZP\Trace\TraceCode::PAYMENT_METHODS_USING_SERVICE_DATA', 'PAYMENT_METHODS_USING_SERVICE_DATA');
        }
        if (!defined('RZP\Trace\TraceCode::PAYMENT_METHODS_DIFF_FOUND')) {
            define('RZP\Trace\TraceCode::PAYMENT_METHODS_DIFF_FOUND', 'PAYMENT_METHODS_DIFF_FOUND');
        }
        if (!defined('RZP\Trace\TraceCode::PAYMENT_METHODS_SERVICE_CALL_FAILED')) {
            define('RZP\Trace\TraceCode::PAYMENT_METHODS_SERVICE_CALL_FAILED', 'PAYMENT_METHODS_SERVICE_CALL_FAILED');
        }
    }

    protected function createMethodsEntity(array $attributes = []): MethodsEntity
    {
        return (new MethodsEntity)->forceFill(array_merge(
            [
                'merchant_id' => $this->merchant->getId(),
                'card' => true,
                'netbanking' => true,
                'upi' => true,
            ],
            $attributes
        ));
    }


    public function testGetMethodsForMerchantNoDifference()
    {
        $merchantId = $this->merchant->getId();
        $path = sprintf('/v1/merchant/%s/api_methods', $merchantId);
        $dbMethods = $this->createMethodsEntity(['card' => true, 'upi' => true]);
        $serviceMethods = $this->createMethodsEntity(['card' => true, 'upi' => true]); // Identical

        $this->methodsRepoMock->shouldReceive('find')
            ->once()
            ->with($merchantId)
            ->andReturn($dbMethods);

        $this->paymentMethodsServiceMock->shouldReceive('isMethodServiceReadEnabled')
            ->once()
            ->andReturn(true);

        $this->paymentMethodsServiceMock->shouldReceive('fetchMethodsFromService')
            ->once()
            ->with($merchantId, $path)
            ->andReturn($serviceMethods);

        $this->paymentMethodsServiceMock->shouldReceive('areMethodsDifferent')
            ->once()
            ->with($serviceMethods, $dbMethods)
            ->andReturn(false); // No difference

        // Execute
        $result = $this->methodsRepoMock->getMethodsForMerchant($this->merchant);

        // Assert
        $this->assertInstanceOf(MethodsEntity::class, $result);
        $this->assertEquals($serviceMethods->isUpiEnabled(), $result->isUpiEnabled());
        $this->assertEquals($serviceMethods->isCardEnabled(), $result->isCardEnabled());
        $this->assertSame($result, $this->merchant->getRelation('methods')); // Check relation is set
    }

    public function testGetMethodsForMerchantDifferenceFound()
    {
        $merchantId = $this->merchant->getId();
        $path = sprintf('/v1/merchant/%s/api_methods', $merchantId);
        $dbMethods = $this->createMethodsEntity(['card' => true, 'upi' => true]);
        $serviceMethods = $this->createMethodsEntity(['card' => false, 'upi' => true]); // Different card value

        $this->methodsRepoMock->shouldReceive('find')
            ->once()
            ->with($merchantId)
            ->andReturn($dbMethods);

        $this->paymentMethodsServiceMock->shouldReceive('isMethodServiceReadEnabled')
            ->once()
            ->andReturn(true);

        $this->paymentMethodsServiceMock->shouldReceive('fetchMethodsFromService')
            ->once()
            ->with($merchantId, $path)
            ->andReturn($serviceMethods);

        $this->paymentMethodsServiceMock->shouldReceive('areMethodsDifferent')
            ->once()
            ->with($serviceMethods, $dbMethods)
            ->andReturn(true); // Difference found

        // Execute
        $result = $this->methodsRepoMock->getMethodsForMerchant($this->merchant);

        // Assert
        $this->assertInstanceOf(MethodsEntity::class, $result);
        $this->assertEquals($dbMethods->isUpiEnabled(), $result->isUpiEnabled()); // Should use DB data
        $this->assertEquals($dbMethods->isCardEnabled(), $result->isCardEnabled());   // Should use DB data
        $this->assertSame($result, $this->merchant->getRelation('methods')); // Check relation is set
    }

    public function testGetMethodsForMerchantDbNullServiceExists()
    {
        $merchantId = $this->merchant->getId();
        $path = sprintf('/v1/merchant/%s/api_methods', $merchantId);
        $serviceMethods = $this->createMethodsEntity(['card' => true, 'upi' => false]);

        $this->methodsRepoMock->shouldReceive('find')
            ->once()
            ->with($merchantId)
            ->andReturn(null); // DB returns null

        $this->paymentMethodsServiceMock->shouldReceive('isMethodServiceReadEnabled')
            ->once()
            ->andReturn(true);

        $this->paymentMethodsServiceMock->shouldReceive('fetchMethodsFromService')
            ->once()
            ->with($merchantId, $path)
            ->andReturn($serviceMethods);

        // Execute
        $result = $this->methodsRepoMock->getMethodsForMerchant($this->merchant);

        // Assert
        $this->assertInstanceOf(MethodsEntity::class, $result);
        $this->assertEquals($serviceMethods->isUpiEnabled(), $result->isUpiEnabled()); // Should use Service data
        $this->assertSame($result, $this->merchant->getRelation('methods')); // Check relation is set
    }

    public function testGetMethodsForMerchantServiceCallFails()
    {
        $merchantId = $this->merchant->getId();
        $path = sprintf('/v1/merchant/%s/api_methods', $merchantId);
        $dbMethods = $this->createMethodsEntity(['card' => true, 'upi' => true]);

        $this->methodsRepoMock->shouldReceive('find')
            ->once()
            ->with($merchantId)
            ->andReturn($dbMethods);

        $this->paymentMethodsServiceMock->shouldReceive('isMethodServiceReadEnabled')
            ->once()
            ->andReturn(true);

        $this->paymentMethodsServiceMock->shouldReceive('fetchMethodsFromService')
            ->once()
            ->with($merchantId, $path)
            ->andThrow(new IntegrationException('Service unavailable')); // Service throws error

        // areMethodsDifferent should not be called if service call fails
        $this->paymentMethodsServiceMock->shouldNotReceive('areMethodsDifferent');

        // Execute
        $result = $this->methodsRepoMock->getMethodsForMerchant($this->merchant);

        // Assert
        $this->assertInstanceOf(MethodsEntity::class, $result);
        $this->assertEquals($dbMethods->isUpiEnabled(), $result->isUpiEnabled()); // Should fallback to DB data
        $this->assertEquals($dbMethods->isCardEnabled(), $result->isCardEnabled());   // Should fallback to DB data
        $this->assertSame($result, $this->merchant->getRelation('methods')); // Check relation is set
    }

    public function testGetMethodsForMerchantDbNullServiceFails()
    {
        $merchantId = $this->merchant->getId();
        $path = sprintf('/v1/merchant/%s/api_methods', $merchantId);

        $this->methodsRepoMock->shouldReceive('find')
            ->once()
            ->with($merchantId)
            ->andReturn(null); // DB returns null

        $this->paymentMethodsServiceMock->shouldReceive('isMethodServiceReadEnabled')
            ->once()
            ->andReturn(true);

        $this->paymentMethodsServiceMock->shouldReceive('fetchMethodsFromService')
            ->once()
            ->with($merchantId, $path)
            ->andThrow(new IntegrationException('Service unavailable')); // Service throws error

        // Execute
        $result = $this->methodsRepoMock->getMethodsForMerchant($this->merchant);

        // Assert
        $this->assertNull($result); // Should return null as both failed
        $this->assertNull($this->merchant->getRelation('methods')); // Relation should not be set
    }
}

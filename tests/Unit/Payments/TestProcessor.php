<?php
use PHPUnit\Framework\TestCase;
//use RZP\Models\Payment\Processor;
use RZP\Models\Payment\Entity;
use Carbon\CarbonInterface;
use RZP\Models\Payment\Processor\Processor;
use RZP\Trace\TraceCode;
use Carbon\Carbon;
use RZP\Diag\EventCode;

class TestProcessor extends TestCase
{
    protected $processor;
    protected $payment;
    protected $traceMock;
    protected $paymentRepoMock;

    protected function setUp(): void
    {
        // Mocking the trace object
        $this->traceMock = $this->getMockBuilder(\Razorpay\Trace\Logger::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['info'])
            ->getMock();

        // Mocking the processor class and its dependencies
        $this->processor = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLateAuthPaymentConfig', 'setCaptureTimeoutRecurringPayments', 'getTimeDifferenceInAuthorizeAndCreated', 'setPaymentRefundAtForConfig'])
            ->getMock();
        // Mocking the paymentRepo
        $this->paymentRepoMock = $this->getMockBuilder(\RZP\Base\Repository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['saveOrFail']) // Add other methods if needed
            ->getMock();

        // Mocking the diag service
        $this->diagMock = $this->getMockBuilder(PaymentEventStub::class)
            ->onlyMethods(['trackPaymentEventV2'])
            ->getMock();


        // Set up the expected behavior of the diag mock
        $this->diagMock->expects($this->once())
            ->method('trackPaymentEventV2')
            ->with(
                $this->equalTo(EventCode::PAYMENT_AUTO_REFUND_DATE_OVERRIDDEN),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->arrayHasKey('auto_refund_epoch') // Add more expectations as needed
            );

        // Mocking the processor class and injecting the mocks
        $this->processor = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLateAuthPaymentConfig', 'setCaptureTimeoutRecurringPayments', 'getTimeDifferenceInAuthorizeAndCreated'])
            ->getMock();

        // Mock the app property to include diag
        $this->appMock = [
            'diag' => $this->diagMock,
        ];

        // Inject the mocked app into the processor (you might need to adjust this depending on how you access it)
        $reflection = new \ReflectionClass($this->processor);
        $appProperty = $reflection->getProperty('app'); // Assuming 'app' is a protected property
        $appProperty->setAccessible(true);
        $appProperty->setValue($this->processor, $this->appMock);


        $reflection = new \ReflectionClass($this->processor);
        $paymentRepoProperty = $reflection->getProperty('paymentRepo');
        $paymentRepoProperty->setAccessible(true);
        $paymentRepoProperty->setValue($this->processor, $this->paymentRepoMock);
        // Inject the trace mock into the protected $trace property using Reflection
        $reflection = new \ReflectionClass($this->processor);
        $traceProperty = $reflection->getProperty('trace');
        $traceProperty->setAccessible(true);
        $traceProperty->setValue($this->processor, $this->traceMock);

        $this->payment = $this->createMock(Entity::class);

        $this->payment->method('getCreatedAt')
            ->willReturn(Carbon::now());

    }

    public function testShouldAutoCapturePaymentConfig_AutomaticWithinTimeout()
    {
        // Arrange
        $lateAuthConfig = [
            'capture' => 'automatic',
            'capture_options' => [
                'automatic_expiry_period' => 15,  // minutes
                'manual_expiry_period' => 20,    // minutes
            ]
        ];

        $this->processor->expects($this->once())
            ->method('getLateAuthPaymentConfig')
            ->with($this->payment)
            ->willReturn($lateAuthConfig);

        $this->processor->expects($this->once())
            ->method('getTimeDifferenceInAuthorizeAndCreated')
            ->with($this->payment, 'seconds')
            ->willReturn(800); // 13.33 minutes

        // Act
        $reflection = new \ReflectionClass($this->processor);
        $method = $reflection->getMethod('shouldAutoCapturePaymentConfigAndSetRefundAt');
        $method->setAccessible(true);

        [$result, $config] = $method->invoke($this->processor, $this->payment);

        // Assert
        $this->assertTrue($result);
        $this->assertSame($lateAuthConfig, $config);
    }
    public function testShouldAutoCapturePaymentConfig_AutomaticExceedsManualTimeout()
    {
        // Arrange
        $lateAuthConfig = [
            'capture' => 'automatic',
            'capture_options' => [
                'automatic_expiry_period' => 15,  // minutes
                'manual_expiry_period' => 20,    // minutes
            ]
        ];

        $this->processor->expects($this->once())
            ->method('getLateAuthPaymentConfig')
            ->with($this->payment)
            ->willReturn($lateAuthConfig);

        $this->processor->expects($this->once())
            ->method('getTimeDifferenceInAuthorizeAndCreated')
            ->with($this->payment, 'seconds')
            ->willReturn(1300); // 21.67 minutes

        // Act
        $reflection = new \ReflectionClass($this->processor);
        $method = $reflection->getMethod('shouldAutoCapturePaymentConfigAndSetRefundAt');
        $method->setAccessible(true);

        [$result, $config] = $method->invoke($this->processor, $this->payment);
        // Assert
        $this->assertFalse($result);
        $this->assertSame($lateAuthConfig, $config);
    }

}

class PaymentEventStub {
    use \RZP\Diag\Traits\PaymentEvent;
}

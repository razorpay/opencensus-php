<?php

namespace Tests\Unit\Jobs;

use RZP\Tests\TestCase;
use RZP\Jobs\XBalanceDualWrite;
use RZP\Models\BankingAccountStatement\Details\Core;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use Exception;

class XBalanceDualWriteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock the trace service with minimal expectations
        $this->trace = $this->getMockBuilder('Razorpay\Trace\Logger')
            ->disableOriginalConstructor()
            ->setMethods(['info', 'error', 'traceException', 'count'])
            ->getMock();

        // Allow any number of calls to trace methods
        $this->trace->expects($this->any())
            ->method('info');
        $this->trace->expects($this->any())
            ->method('traceException');
    }

    public function testXBalanceDualWriteJobProcessing()
    {
        // Sample message that matches your format
        $payload = [
            'balance_id' => 'Q91AZnQ6FW8dgn',
            'gateway_balance' => 5941,
            'balance_last_fetched_at' => 1743700993,
            'gateway_balance_change_at' => 0
        ];

        // Create a partial mock of XBalanceDualWrite
        $job = $this->getMockBuilder(XBalanceDualWrite::class)
            ->setConstructorArgs([$payload])
            ->onlyMethods(['delete'])
            ->getMock();

        // Expect delete to be called once on success
        $job->expects($this->once())
            ->method('delete');

        // Set the trace mock
        $this->setProperty($job, 'trace', $this->trace);

        // Execute the job - should not throw any exception
        $job->handle();
    }

    public function testXBalanceDualWriteJobRetryOnFailure()
    {
        // Sample message that matches your format
        $payload = [
            'balance_id' => 'Q91AZnQ6FW8dgn',
            'gateway_balance' => 5941,
            'balance_last_fetched_at' => 1743700993,
            'gateway_balance_change_at' => 0
        ];

        // Create a mock Core that throws an exception
        $core = $this->getMockBuilder(Core::class)
            ->disableOriginalConstructor()
            ->getMock();

        $core->expects($this->once())
            ->method('handleDualWrite')
            ->willThrowException(new Exception('Test exception'));

        // Create a partial mock of XBalanceDualWrite
        $job = $this->getMockBuilder(XBalanceDualWrite::class)
            ->setConstructorArgs([$payload])
            ->onlyMethods(['attempts', 'release', 'createCore'])
            ->getMock();

        // Mock createCore to return our mocked Core
        $job->expects($this->once())
            ->method('createCore')
            ->willReturn($core);

        // Mock attempts to return 1 (first attempt)
        $job->expects($this->any())
            ->method('attempts')
            ->willReturn(1);

        // Expect release to be called once with MAX_RETRY_DELAY
        $job->expects($this->once())
            ->method('release')
            ->with(XBalanceDualWrite::MAX_RETRY_DELAY);

        // Set the trace mock
        $this->setProperty($job, 'trace', $this->trace);

        // Execute the job - should throw an exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');
        $job->handle();
    }

    /**
     * Helper method to set protected/private properties
     */
    protected function setProperty($object, $property, $value)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
} 
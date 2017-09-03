<?php

namespace RZP\Tests\Unit\Trace;

use Mockery;
use RZP\Exception;
use RZP\Models\Card;
use RZP\Tests\TestCase;
use RZP\Trace;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Error\CustomerErrorDescription;

class TraceTest extends TestCase
{
    public function testEmailOnTraceFailure()
    {
        $this->markTestSkipped();

        $trace = Mockery::mock($class, [$this->app])
                        ->makePartial()
                        ->shouldAllowMockingProtectedMethods();

        // Mocks environment returning function so that it thinks it's in prod.
        $trace->shouldReceive('isEnvironmentProd')->once()->andReturn(true);

        //
        // Mocks function which sends the mail about tracing failure.
        // This test case ensures the tracing failure mails are working as a
        // last resort.
        //
        $trace->shouldReceive('sendMailWithData')->once()->with(
            Mockery::type('string'),
            hasKey('type', 'message', 'code', 'file', 'line', 'trace', 'context', 'environment')
        );

        $trace->addRecord('info', 'RANDOM MESSAGE', []);
    }
}
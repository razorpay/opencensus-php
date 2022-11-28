<?php

namespace RZP\Tests\Traits;

use Mockery;
use \WpOrg\Requests\Response;
use PHPUnit\Framework\ExpectationFailedException;

use RZP\Services\Mock\Stork;

trait TestsStorkServiceRequests
{
    /**
     * Mock instance for \RZP\Services\Stork.
     * @var Mockery\MockInterface
     */
    protected $storkMock;

    /**
     * Creates stork service mock to set webhook event expectations.
     * @return Mockery\MockInterface
     */
    protected function createStorkMock(): Mockery\MockInterface
    {
        $this->storkMock = Mockery::mock(Stork::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->app->instance('stork_service', $this->storkMock);

        return $this->storkMock;
    }

    /**
     * @param  string        $path
     * @param  array|null    $payload
     * @param  callable|null $matcher
     * @param  int|integer   $mockedResCode
     * @param  array         $mockedResBody
     * @return void
     */
    protected function expectStorkServiceRequest(
        string $path,
        array $payload = [],
        callable $matcher = null,
        int $mockedResCode = 200,
        array $mockedResBody = [])
    {
        $this->storkMock = $this->storkMock ?: $this->createStorkMock();

        $payloadArgMatcher = function(array $actualPayload) use ($payload, $matcher)
        {
            // The matcher should return boolean and not throw exceptions.
            // But it has been easier to call assert functions in tests and hence below stuff.
            try
            {
                // Before calling optional $matcher, asserts expected $payload.
                $this->assertArraySelectiveEquals($payload, $actualPayload);

                return (($matcher === null) or ($matcher($actualPayload) ?? true));
            }
            catch (ExpectationFailedException $e)
            {
                s($e->getMessage(), $actualPayload, $payload);
                return false;
            }
        };

        $mockedRes = new \WpOrg\Requests\Response;
        $mockedRes->status_code = $mockedResCode;
        $mockedRes->success = $mockedResCode === 200;
        $mockedRes->body = json_encode($mockedResBody);

        $this->storkMock
            ->shouldReceive('request')
            ->once()
            ->with($path, Mockery::on($payloadArgMatcher))
            ->andReturn($mockedRes);
    }

    /**
     * @param  string|null   $name
     * @param  callable|null $matcher
     * @return void
     */
    protected function expectStorkServiceRequestForAction(string $name = null, callable $matcher = null)
    {
        $name = $name.'StorkExpectations';
        $testData = &$this->testData[$name];

        $path = $testData['expected_request']['path'];
        $payload = $testData['expected_request']['payload'];
        $mockedResCode = $testData['mocked_response']['code'];
        $mockedResBody = $testData['mocked_response']['body'];

        $this->expectStorkServiceRequest($path, $payload, $matcher, $mockedResCode, $mockedResBody);
    }
    /**
     * @return void
     */
    protected function expectAnyStorkServiceRequest()
    {
        $this->storkMock = $this->storkMock ?: $this->createStorkMock();

        $mockedRes = new \WpOrg\Requests\Response;
        $mockedRes->status_code = 200;
        $mockedRes->success = true;
        $mockedRes->body = json_encode([]);

        $this->storkMock
            ->shouldReceive('traceWhatsAppRequest')
            ->andReturn([]);

        $this->storkMock
            ->shouldReceive('request')
            ->times(1)
            ->with(Mockery::any(), Mockery::any())
            ->andReturn($mockedRes);
    }

    /**
     * @return void
     */
    protected function dontExpectAnyStorkServiceRequest()
    {
        $this->storkMock = $this->storkMock ?: $this->createStorkMock();

        $this->storkMock
            ->shouldNotReceive('request')
            ->with(Mockery::any(), Mockery::any());
    }

    /**
     * @param  string $path
     * @return void
     */
    protected function dontExpectStorkServiceRequest(string $path)
    {
        $this->storkMock = $this->storkMock ?: $this->createStorkMock();

        $this->storkMock
            ->shouldNotReceive('request')
            ->with($path, Mockery::any());
    }
}

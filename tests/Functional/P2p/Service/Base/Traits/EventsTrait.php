<?php

namespace RZP\Tests\P2p\Service\Base\Traits;

use Mockery;
use Http\Mock\Client;
use GuzzleHttp\Psr7\Request;

trait EventsTrait
{
    protected $mockedRavenRequest;

    protected $mockedRemindersRequest;

    protected function mockRaven()
    {
        $raven = Mockery::mock('RZP\Services\Raven')->makePartial();

        $this->app->instance('raven', $raven);

        $raven->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing(function ($route, $method, $input)
            {
                 $this->mockedRavenRequest = [$route, $method, $input];

                $response = [
                    'success' => true,
                ];

                return $response;
            })->between(1, 10);

        $this->app->instance('raven', $raven);
    }

    protected function mockReminder()
    {
        $reminders = Mockery::mock('RZP\Services\Reminders')->makePartial();

        $this->app->instance('reminders', $reminders);

        $reminders->shouldReceive('createReminder')
            ->with(Mockery::type('array'), Mockery::type('string'))
            ->andReturnUsing(function ($request, $merchantId){
                $this->mockedRemindersRequest = [$request, $merchantId];

                $response = [
                    'success'   => true
                ];

                return $response;
            });

        $this->app->instance('reminders', $reminders);
    }

    protected function assertMockReminder(callable $inputHandler)
    {
        $inputHandler($this->mockedRemindersRequest[0], $this->mockedRemindersRequest[1]);
    }

    protected function assertRavenRequest(callable $inputHandler, $method = 'post', $route = 'sms')
    {
        $this->assertSame($route, $this->mockedRavenRequest[0]);
        $this->assertSame($method, $this->mockedRavenRequest[1]);

        $inputHandler($this->mockedRavenRequest[2]);
    }

    protected function resetRavenMock()
    {
        $this->mockedRavenRequest = [];
    }

    protected function assertNoRavenRequest()
    {
        $this->assertEmpty($this->mockedRavenRequest);
    }
}

<?php


namespace RZP\Tests\Functional\Helpers;

use Mockery;

trait TerminalTrait
{
    protected function getTerminalsServiceMock()
    {
        $terminalsServiceMock = Mockery::mock('RZP\Services\TerminalsService')->makePartial();

        $terminalsServiceMock->shouldAllowMockingProtectedMethods();

        $this->app['terminals_service'] = $terminalsServiceMock;

        $this->app['config']->set('terminals_service.test.url', 'https://terminals-test.razorpay.com/');
        $this->app['config']->set('terminals_service.live.url', 'https://terminals-live.razorpay.com/');

        return $terminalsServiceMock;
    }

    protected function mockTerminalsServiceSendRequest($closure, $times = 2)
    {
        $this->terminalsServiceMock->shouldReceive('sendRequest')
            ->times($times)
            ->andReturnUsing($closure);
    }

    protected function throwTerminalsServiceIntegrationException()
    {
        throw new \Requests_Exception_Transport_cURL('curl timed out', []);
    }
}

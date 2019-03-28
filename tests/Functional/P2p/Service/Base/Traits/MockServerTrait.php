<?php

namespace RZP\Tests\P2p\Service\Base\Traits;

use Mockery;
use RZP\Gateway\P2p\Base\Factory;

trait MockServerTrait
{
    protected $requestActions = [];
    protected $contentActions = [];
    protected $mockedServer;

    protected function resetMockServer()
    {
        $this->requestActions = [];
        $this->contentActions = [];
        $this->mockedServer   = null;
    }

    protected function mockActionRequestFunction($actions, $gateway = null)
    {
        $this->requestActions = array_fill_keys(array_keys($actions), false);
        return $this->mockServerRequestFunction(
            function(&$content, $actualAction = null) use ($actions)
            {
                foreach ($actions as $forAction => $closure)
                {
                    if ($forAction === $actualAction)
                    {
                        $this->requestActions[$forAction] = true;
                        return $closure($content);
                    }
                }
            }, $gateway);
    }

    protected function mockActionContentFunction($actions, $gateway = null)
    {
        $this->contentActions = array_fill_keys(array_keys($actions), false);
        return $this->mockServerContentFunction(
            function(&$content, $actualAction = null) use ($actions)
            {
                foreach ($actions as $forAction => $closure)
                {
                    if ($forAction === $actualAction)
                    {
                        $this->contentActions[$forAction] = true;
                        return $closure($content);
                    }
                }
            }, $gateway);
    }

    protected function mockServerRequestFunction($closure, $gateway = null)
    {
        $server = $this->mockServer($gateway)
            ->shouldReceive('request')
            ->andReturnUsing($closure)
            ->mock();

        return $server;
    }

    protected function mockServerContentFunction($closure, $gateway = null)
    {
        $server = $this->mockServer($gateway)
            ->shouldReceive('content')
            ->andReturnUsing($closure)
            ->mock();

        return $server;
    }

    protected function mockServer($gateway = null)
    {
        if ($this->mockedServer === null)
        {
            $gateway = $gateway ?: $this->gateway;

            $class = $this->app['gateway']->getServerClass($gateway);

            $server = new $class;

            $class = Factory::getServerClass($gateway);

            $this->mockedServer = Mockery::mock($class, [])->makePartial();

            $server->setGatewayServer($this->mockedServer);

            $this->app['gateway']->setServer($gateway, $server);
        }

        return $this->mockedServer;
    }

    protected function checkForMockedActions()
    {
        if (isset($this->requestActions))
        {
            foreach ($this->requestActions as $action => $ran)
            {
                $this->assertTrue($ran, "Request action: $action was not called");
            }
        }
        if (isset($this->contentActions))
        {
            foreach ($this->contentActions as $action => $ran)
            {
                $this->assertTrue($ran, "Content action: $action was not called");
            }
        }
    }
}

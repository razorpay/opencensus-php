<?php

namespace RZP\Gateway;

use Config;
use RZP\Constants\Mode;
use RZP\Constants\Entity;
use RZP\Exception;
use RZP\Gateway\Base\Mock;

class GatewayManager extends \Illuminate\Support\Manager
{
    protected $gateways = array();

    protected $mocks = array();

    protected $servers = array();

    public function __construct($app)
    {
        parent::__construct($app);

        $gatewayConfig = $this->app['config']->get('gateway');

        $this->gateways = $gatewayConfig['available'];

        $this->registerMocks($gatewayConfig);
    }

    public function call($gateway, $action, $input, $mode, $terminal = null)
    {
        $gateway = $this->gateway($gateway);

        $gateway->setTerminal($terminal);

        $gateway->setMode($mode);

        // Laravel helper function converts snake case to camel case
        $action = camel_case($action);

        // Call function on actual gateway instance
        return $gateway->$action($input);
    }

    protected function registerMocks($gatewayConfig)
    {
        foreach ($this->gateways as $gateway)
        {
            if ((isset($gatewayConfig['mock_'.$gateway])) and
                ($gatewayConfig['mock_'.$gateway] === true))
            {
                $this->mocks[] = $gateway;
            }
        }
    }

    protected function createDriver($driver)
    {
        if (in_array($driver, $this->getGateways()) === false)
        {
            throw new Exception\LogicException($driver . ' is not an available gateway');
        }

        $mock = $this->isMock($driver);

        return $this->createGatewayDriver($driver, $mock);
    }

    protected function createGatewayDriver($driver, $mock)
    {
        $namespace = $this->getGatewayNamespace($driver, $mock);

        // Constructs gateway class name in the format
        $class = $namespace . '\\' . 'Gateway';

        if (class_exists($class) === false)
        {
            throw new Exception\LogicException($class . ' is not a valid class');
        }

        $gateway = new $class;

        $gateway->setMock($mock);

        return $gateway;
    }

    protected function isMock($driver)
    {
        $mode = $this->getMode();

        if (($mode === Mode::TEST) and
            (in_array($driver, $this->getMockDrivers())))
        {
            return true;
        }

        return false;
    }

    public function getDefaultDriver()
    {
        throw new Exception\LogicException('No default gateway is specified');
    }

    public function gateway($gateway)
    {
        return parent::driver($gateway);
    }

    public function driver($driver = null)
    {
        $driver = $driver ?: $this->getDefaultDriver();

        return $this->createDriver($driver);
    }

    public function server($driver)
    {
        $servers = & $this->servers;

        if (isset($servers[$driver]))
        {
            return $servers[$driver];
        }

        $server = $this->getServerClass($driver);

        $server = new $server;

        $servers[$driver] = $server;

        return $servers[$driver];
    }

    public function getServerClass($driver)
    {
        $server = $this->getGatewayNamespace($driver, true) . '\\Server';

        if ($driver === 'sharp')
        {
            $server = 'RZP\Gateway\Sharp\Server';
        }

        return $server;
    }

    /**
     * During tests, if we want to set a mock server to manipulate gateway
     * server function results, then use this function to set the mock
     * object as the corresponding server instead of the default one.
     *
     * @param   $driver
     * @param   $server Mocked server object
     * @return  $server Mocked server object
     */
    public function setServer($driver, Mock\Server $server = null)
    {
        $this->servers[$driver] = $server;

        $server->setNamespace($this->getGatewayNamespace($driver, true));

        return $server;
    }

    /**
     * Resets the mocked server for this driver to the default
     * mock server availbale.
     * @param  string $driver [description]
     */
    public function resetServer($driver)
    {
        $class = $this->getServerClass($driver);

        $this->servers[$driver] = new $class;
    }

    public function resetDriver($driver)
    {
        $mock = $this->isMock($driver);

        $this->drivers[$driver] = $this->createGatewayDriver($driver, $mock);
    }

    protected function getMockDrivers()
    {
        return $this->mocks;
    }

    protected function getMode()
    {
        return $this->app['basicauth']->getMode();
    }

    protected function getGateways()
    {
        return $this->gateways;
    }

    protected function getGatewayNamespace($driver, $mock = false)
    {
        $namespace = Entity::getEntityNamespace($driver);

        if ($mock === true)
        {
            $namespace .= '\\' . 'Mock';
        }

        return $namespace;
    }
}
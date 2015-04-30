<?php

namespace Gateway;

use Config;
use Constants\Mode;
use EE\Exception;
use Gateway\Hdfc;

class GatewayManager extends \Illuminate\Support\Manager
{
    protected $gateways = array();

    protected $mocks = array();

    public function __construct($app)
    {
        parent::__construct($app);

        $gatewayConfig = $this->app['config']->get('gateway');

        $this->gateways = $gatewayConfig['available'];

        $this->registerMocks($gatewayConfig);
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

        $mock = $this->getMock($driver);

        return $this->createGatewayDriver($driver, $mock);
    }

    protected function createGatewayDriver($driver, $mock)
    {
        $driver = ucfirst(studly_case($driver));

        // Constructs gateway class name in the format
        // 'Gateway\{Mock}{GatewayName}\Gateway'
        $class = 'Gateway\\'.$mock.$driver.'\\'.'Gateway';

        return new $class;
    }

    protected function getMock($driver)
    {
        $mock = '';

        $mode = $this->getMode();

        if (($mode === Mode::TEST) and
            (in_array($driver, $this->getMockDrivers())))
        {
            $mock = 'Mock';
        }

        return $mock;
    }

    public function getDefaultDriver()
    {
        throw new Exception\LogicException('No default gateway is specified');
    }

    public function gateway($gateway)
    {
        return parent::driver($gateway);
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
}
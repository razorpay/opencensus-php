<?php

namespace Gateway;

use Config;
use Constants\Mode;
use EE\Exception;
use Gateway\Base\Mock;

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

        return new $class;
    }

    protected function isMock($driver)
    {
        $mock = '';

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

    public function netbankingGateway($bank)
    {
        $driver = 'netbanking_'.$bank;

        return $this->netbankingGateway($bank);
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
            $server = 'Gateway\Sharp\Server';

        return $server;
    }

    public function setServer($driver, Mock\Server $server)
    {
        $this->servers[$driver] = $server;

        $server->setNamespace($this->getGatewayNamespace($driver, true));

        return $server;
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
        $driver1 = ucfirst(studly_case($driver));

        $driver2 = ucwords(str_replace('_', ' ', $driver));
        $driver2 = str_replace(' ', '\\', $driver2);

        $class1 = 'Gateway\\'.$driver1.'\Gateway';
        $class2 = 'Gateway\\'.$driver2.'\Gateway';

        $namespace = null;
        if (class_exists($class1))
            $namespace = $driver1;
        else if (class_exists($class2))
            $namespace = $driver2;

        $namespace = 'Gateway\\'.$namespace;

        if ($mock === true)
            $namespace = $namespace .= '\\' . 'Mock';

        return $namespace;
    }
}
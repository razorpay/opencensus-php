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
        if (in_array($driver, $this->gateways) === false)
        {
            throw new Exception\LogicException($driver . ' is not an available gateway');
        }

        $mock = '';

        $mode = \BasicAuth::getMode();

        if (($mode === Mode::TEST) and
            (in_array($driver, $this->mocks)))
        {
            $mock = 'Mock';
        }

        // Constructs gateway class name in the format
        // 'Gateway\{Mock}{GatewayName}\Gateway'
        $class = 'Gateway\\'.$mock.ucfirst($driver).'\\'.'Gateway';

        return new $class;
    }

    public function getDefaultDriver()
    {
        throw new Exception\LogicException('No default gateway is specified');
    }

    public function gateway($gateway)
    {
        return parent::driver($gateway);
    }
}
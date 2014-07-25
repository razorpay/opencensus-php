<?php

namespace Gateway;

use Gateway\Hdfc;
use EE\Exception;

class GatewayManager
{
    protected $gateways = array();

    public function __construct($gateway = null)
    {

    }

    protected function gateway($gateway = null)
    {
        $gateway = $gateway ?: $this->getDefaultGateway();

        if ( ! isset($this->gateways[$gateway]))
        {
            $this->gateways[$gateway] = $this->createGateway($gateway);
        }

        return $this->gateways[$gateway];
    }

    protected function createGateway($gateway)
    {
        $method = 'create'.ucfirst($gateway).'Gateway';

        if (method_exists($this, $method))
        {
            return $this->$method();
        }

        throw new Exception\InvalidArgumentException(
                                'Gateway ' . $gateway . ' not supported');
    }

    public function getDefaultGateway()
    {
        return \Config::get('gateway.default');
    }

    public function getGateways()
    {
        return $gateways;
    }

    public function createHdfcGateway()
    {
        return new Hdfc\Gateway();
    }

    public function createMockGateway()
    {
        return new Mock\Gateway();
    }

    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this->gateway(), $method), $parameters);
    }
}
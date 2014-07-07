<?php

namespace Gateway;

use Gateway\Hdfc;

class GatewayManager
{
    protected $gateways = array();

    protected function createGatewayProvider()
    {
        $this->provider = new Hdfc\Gateway();
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

        throw new \InvalidArgumentException("Gateway $gateway not supported");
    }

    public function getDefaultGateway()
    {
        return 'Hdfc';
    }

    public function getGateways()
    {
        return $gateways;
    }

    public function createHdfcGateway()
    {
        return new Hdfc\Gateway();
    }

    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this->gateway(), $method), $parameters);
    }
}
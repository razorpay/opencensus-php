<?php

namespace RZP\Gateway\P2p\Upi;

use RZP\Gateway\P2p\Base;
use RZP\Gateway\P2p\Base\Factory;
use RZP\Gateway\P2p\Upi\Contracts;
use RZP\Models\P2p\Base\Libraries\Context;

class Gateway extends Base\Gateway
{
    public function device(Context $context)
    {
        $gateway = Factory::make($context, Contracts\DeviceGateway::class);

        $this->handleGatewaySwitch($gateway, __FUNCTION__);

        return $gateway->response();
    }

    public function bankAccount(Context $context)
    {
        $gateway = Factory::make($context, Contracts\BankAccountGateway::class);

        $this->handleGatewaySwitch($gateway, __FUNCTION__);

        return $gateway->response();
    }

    public function vpa(Context $context)
    {
        $gateway = Factory::make($context, Contracts\VpaGateway::class);

        $this->handleGatewaySwitch($gateway, __FUNCTION__);

        return $gateway->response();
    }

    public function transaction(Context $context)
    {
        $gateway = Factory::make($context, Contracts\TransactionGateway::class);

        $this->handleGatewaySwitch($gateway, __FUNCTION__);

        return $gateway->response();
    }

    public function mandate(Context $context)
    {
        $gateway = Factory::make($context, Contracts\MandateGateway::class);

        $this->handleGatewaySwitch($gateway, __FUNCTION__);

        return $gateway->response();
    }

    public function upi(Context $context)
    {
        $gateway = Factory::make($context, Contracts\UpiGateway::class);

        $this->handleGatewaySwitch($gateway, __FUNCTION__);

        return $gateway->response();
    }

    public function turbo(Context $context)
    {
        $gateway = Factory::make($context, Contracts\TurboGateway::class);

        $this->handleGatewaySwitch($gateway, __FUNCTION__);

        return $gateway->response();
    }

    public function client(Context $context)
    {
        $gateway = Factory::make($context, Contracts\ClientGateway::class);

        $this->handleGatewaySwitch($gateway, __FUNCTION__);

        return $gateway->response();
    }

    protected function sendGatewayRequest($request)
    {
        if ($this->mock === true)
        {
            return $this->mockSendGatewayRequest($request);
        }

        return parent::sendGatewayRequest($request);
    }

    /*
     * This method is responsible for calling the actual mock server of the gateway
     * It first checks whether the gateway server is set. If not it will
     * get the respective gateway server and attach it to Upi/Mock/Server.
     * The respective action will be invoked on the gateway mock server,
     * which will return the actual response.
     * The gateway server will be preset when it's running on test cases
     */

    protected function mockSendGatewayRequest(array $request)
    {
        $server = $this->getMockServer();

        if ($server->hasGatewayServer() === false)
        {
            $class = Factory::getServerClass($this->gateway);

            $server->setGatewayServer(new $class);
        }

        $gatewayServer = $server->getGatewayServer();

        $content = $gatewayServer->setMockRequest($request);

        $server->setGatewayServer($gatewayServer);

        $action = camel_case($this->entity . '_' . $this->action);

        $gatewayServer->request($content, $action);

        $response = $gatewayServer->{$action}($content);

        return $response;
    }

    protected function getMockServer(): Mock\Server
    {
        return $this->app['gateway']->server($this->gateway);
    }
}

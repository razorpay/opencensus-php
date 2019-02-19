<?php

namespace RZP\Gateway\Hdfc\Mock;

use App;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Hdfc;
use ReflectionClass;

class Gateway extends Hdfc\Gateway
{
    use Base\Mock\GatewayTrait;

    protected $server;

    protected $mockHdfcServer;

    public function __construct()
    {
        parent::__construct();

        $this->request = \Request::getFacadeRoot();

        $this->mockHdfcServer = $this->config['mock_server'];

        $this->mock = true;
    }

    protected function sendGatewayRequest($request)
    {
        if ($this->mockHdfcServer)
        {
            $request['url'] = $this->getMockRequestUrl($request['url']);

            return parent::sendGatewayRequest($request);
        }
        else
        {
            $serverResponse = $this->callGatewayRequestFunctionInternally($request);

            return $this->prepareInternalResponse($serverResponse);
        }
    }

    protected function callGatewayRequestFunctionInternally($requestVar)
    {
        $server = $this->getServer();
        $server->setInput($requestVar['content']);

        $response = null;

        switch($requestVar['type'])
        {
            case 'enroll':
                $this->handleTimeoutSpecialCase();
                $response = $server->enroll();
                break;

            case 'pre_authorization':
                $response = $server->preAuthorization();
                break;

            case 'auth_enrolled':
                $response = $server->authEnrolled();
                break;

            case 'auth_second_recurring':
                $response = $server->gatewayTransaction('auth_second_recurring');
                break;

            case 'debit_pin_authentication':
                $response = $server->debitPinAuth();
                break;

            case 'auth_not_enrolled':
            case 'capture':
            case 'refund':
            case 'inquiry':
                $response = $server->gatewayTransaction();
                break;
            default:
                throw new Exception\LogicException(
                    'Unrecognized request type.',
                    null,
                    [
                        'payment_id'   => $requestVar['data']['trackid'],
                        'request_type' => $requestVar['type'],
                    ]);
        }

        return $response;
    }

    protected function getMockRequestUrl($url)
    {
        $rc = new ReflectionClass('Gateway\Hdfc\Urls');
        $urls = $rc->getConstants();

        $domain = $urls['TEST_DOMAIN'];

        foreach ($urls as $name => $hdfcUrl)
        {
            if ($url === ($domain . $hdfcUrl))
            {
                return $this->makeMockRequestUrl($name);
            }
        }
    }

    protected function makeMockRequestUrl($name)
    {
        $mockGatewaysConfig = \Config::get('applications.mock_gateways');
        $secret = $mockGatewaysConfig['secret'];

        $urlSegment = constant('Gateway\Hdfc\Mock\Urls::'.$name);

        $url = $this->route->getUrlWithAuth($urlSegment, 'rzp_test', $secret);

        return $url;
    }

    public function getRequestFields($name)
    {
        $var = $name.'Request';

        $array = $this->$var;
        $fields = $array['fields'];

        return $fields;
    }

    protected function handleTimeoutSpecialCase()
    {
        if ((isset($this->enrollRequest['data']['card'])) and
            ($this->enrollRequest['data']['card'] === '4012001036275556'))
        {
            throw new Exception\GatewayTimeoutException(
                'cURL error 28: Operation timed out after ' .
                '10 ' . static::TIMEOUT . '001 milliseconds with 0 bytes received');
        }
    }

    protected function getServer()
    {
        $app = App::getFacadeRoot();

        return $app['gateway']->server('hdfc');
    }
}

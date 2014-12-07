<?php

namespace Gateway\MockHdfc;

use Carbon\Carbon;
use EE\Exception;
use Gateway\Hdfc;
use Gateway\Hdfc\Action;
use Gateway\MockHdfc;
use Models\Card;
use ReflectionClass;
use Requests;
use Requests_Response;

class Gateway extends Hdfc\Gateway
{
    public function __construct()
    {
        parent::__construct();

        $this->request = \Request::getFacadeRoot();

        $this->mockHdfcServer = \Config::get('gateway.mockhdfc_server');

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

    protected function prepareInternalResponse($serverResponse)
    {
        $response = new Requests_Response();

        $response->headers = $serverResponse->headers->all();

        foreach ($response->headers as $key => &$value)
        {
            $value = implode(';', $value);
        }

        $response->body = $serverResponse->getContent();
        $response->status_code = $serverResponse->getStatusCode();
        $response->success = true;
        // @todo: add url to response var

        return $response;
    }

    protected function callGatewayRequestFunctionInternally($requestVar)
    {
        $server = new Server();
        $server->setInput($requestVar['content']);

        $response = null;

        switch($requestVar['type'])
        {
            case 'enroll':
                $this->handleTimeoutSpecialCase();
                $response = $server->enroll();
                break;

            case 'auth_enrolled':
                $response = $server->authEnrolled();
                break;

            case 'auth_not_enrolled':
            case 'capture':
            case 'refund':
                $response = $server->gatewayPayment();
                break;

            default:
                throw new Exception\LogicException('Unrecognized request type: ' . $requestVar['type']);
        }

        return $response;
    }

    protected function getMockRequestUrl($url)
    {
        $rc = new ReflectionClass('Gateway\Hdfc\Urls');
        $urls = $rc->getConstants();

        foreach ($urls as $name => $hdfcUrl)
        {
            if ($url === $hdfcUrl)
            {
                return $this->makeMockRequestUrl($name);
            }
        }
    }

    protected function makeMockRequestUrl($name)
    {
        $url = constant('Gateway\MockHdfc\Urls::'.$name);

        $scheme = $this->request->getScheme().'://';
        $host = $this->request->getHost();
        $key = 'rzp_test';
        $secret = 'DASHBOARD_AUTH_PASS';

        if ($host === 'localhost')
            $host = 'rzp';

        $url = $scheme . $key . ':' . $secret. '@' . $host . '/v1/' . $url;

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
            throw new \Requests_Exception(
                'cURL error 28: Operation timed out after ' .
                '10 ' . Hdfc\Config::TIMEOUT . '001 milliseconds with 0 bytes received', 'curlerror');

        }
    }
}
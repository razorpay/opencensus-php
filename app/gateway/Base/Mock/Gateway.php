<?php

namespace Gateway\Base\Mock;

use Requests_Response;

trait GatewayTrait
{
    public function authorizeMock(array $input)
    {
        $request = parent::authorize($input);

        $this->putMockPaymentGatewayUrl($request);

        return $request;
    }

    protected function putMockPaymentGatewayUrl(array & $request)
    {
        $gateway = $this->gateway;
        $route = 'mock_'.$gateway.'_payment';

        $url = \Http\Route::getUrlWithPublicAuth($route);

        if ($request['method'] === 'get')
        {
            // The key thing now is to replace the url from gateway to our mock one!
            $parts = parse_url($request['url']);

            $url = $url . '&' .$parts['query'];

            $request['url'] = $url;
        }

        $request['url'] = $url;
    }

    protected function sendGatewayRequest($request)
    {
        // Although we reset the url, it's not being used currently.
        // $request['url'] = $this->makeMockRequestUrl($request);

        // Redirect the request internally
        $serverResponse = $this->callGatewayRequestFunctionInternally($request);

        return $this->prepareInternalResponse($serverResponse);
    }

    protected function callGatewayRequestFunctionInternally($request)
    {
        $ns = $this->getNamespace();
        $class = $ns.'\Server';
        $server = new $class;

        if ($request['method'] === 'post')
        {
            $input = $request['content'];
        }
        else if ($request['method'] === 'get')
        {
            $url = $request['url'];
            $parts = parse_url($url);

            parse_str($parts['query'], $input);
        }

        $server->setInput($request['content']);

        $action = $this->action;

        $response = $server->$action($input);

        return $response;
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
}
<?php

namespace RZP\Gateway\Base\Mock;

use \WpOrg\Requests\Response;
use \WpOrg\Requests\Response\Headers;

trait GatewayTrait
{
    public function authorizeMock(array $input, $route = null)
    {
        $request = parent::authorize($input);

        if (is_array($request) and (isset($request['method']) === true))
        {
            if (($request['method'] === 'sdk') === false)
            {
                $this->putMockPaymentGatewayUrl($request, $route);
            }
        }

        return $request;
    }

    protected function putMockPaymentGatewayUrl(array & $request, $route)
    {
        $gateway = $this->gateway;

        if (is_null($route))
        {
            $route = 'mock_' . $gateway . '_payment';
        }

        $url = $this->route->getUrlWithPublicAuth($route);

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
        $this->wasGatewayHit = true;

        // Although we reset the url, it's not being used currently.
        // $request['url'] = $this->makeMockRequestUrl($request);

        // Redirect the request internally
        $serverResponse = $this->callGatewayRequestFunctionInternally($request);

        return $this->prepareInternalResponse($serverResponse);
    }

    protected function callGatewayRequestFunctionInternally($request)
    {
        $server = $this->app['gateway']->server($this->gateway);

        $input = [];

        $request['method'] = strtolower($request['method']);

        if ($request['method'] === 'post')
        {
            $input = $request['content'];
        }
        else if ($request['method'] === 'get')
        {
            $url = $request['url'];

            $parts = parse_url($url);

            if (isset($parts['query']) === true && $parts['query'] !== "")
            {
                parse_str($parts['query'], $input);
            }

            // We can either pass the query params in URL or via the request's
            // content field.
            if ((empty($request['content']) === false) and
                (is_array($request['content']) === true))
            {
                $input = array_merge($input, $request['content']);
            }
        }

        $server->setInput($request['content']);

        $server->setMockRequest($request);

        $action = camel_case($this->action);

        $response = $server->$action($input);

        return $response;
    }

    protected function prepareInternalResponse($serverResponse)
    {
        $response = new \WpOrg\Requests\Response();

        $headers = $serverResponse->headers->all();
        $response->headers = new \WpOrg\Requests\Response\Headers();

        foreach ($headers as $key => $value)
        {
            $response->headers[$key] = $value[0];
        }

        $response->body = $serverResponse->getContent();
        $response->status_code = $serverResponse->getStatusCode();
        $response->success = true;
        // @todo: add url to response var

        return $response;
    }
}

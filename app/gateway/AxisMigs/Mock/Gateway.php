<?php

namespace Gateway\AxisMigs\Mock;

use Carbon\Carbon;
use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\AxisMigs;
use Models\Card;
use Requests_Response;

class Gateway extends AxisMigs\Gateway
{
    public function authorize(array $input)
    {
        $request = parent::authorize($input);

        $url = \Http\Route::getUrlWithPublicAuth('mockaxis_payment');
        $request['url'] = $url;

        return $request;
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
        $server = new Server();

        $server->setInput($request['content']);

        $action = $request['action'];
        $response = $server->$action($request['content']);

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

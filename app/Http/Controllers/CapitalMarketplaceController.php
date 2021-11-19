<?php

namespace RZP\Http\Controllers;

use RZP\Constants\HashAlgo;
use View;
use Request;
use ApiResponse;
use RZP\Trace\Tracer;
use Illuminate\Support\Str;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use RZP\Models\Admin\Permission\Name;
use Psr\Http\Message\RequestInterface;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use OpenCensus\Trace\Propagator\ArrayHeaders;
use RZP\Mail\CapitalCards\Base;
use Illuminate\Support\Facades\Mail;

class CapitalMarketplaceController extends Controller
{
    const GET      = 'GET';
    const POST     = 'POST';
    const PUT      = 'PUT';
    const PATCH    = 'PATCH';
    const DELETE   = 'DELETE';
    const MERCHANT = 'MERCHANT';

    const OAUTH_URL = "v1/oauth/refresh_token";

    protected function handleProxyRequests($path = null)
    {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();


        $headers = [
            'x-merchant-id'    => optional($this->ba->getMerchant())->getId() ?? '',
            'X-Merchant-Email' => optional($this->ba->getMerchant())->getEmail() ?? '',
            'x-user-id'        => optional($this->ba->getUser())->getId() ?? '',
            'X-User-Role'      => $this->ba->getUserRole() ?? '',
            'X-Auth-Type'      => 'proxy',
        ];

        if (($request->method() === 'GET') and
            (empty($body) === false))
        {
            $url .= '?' . http_build_query($body);
        }


        $response = $this->sendRequestAndParseResponse($url, $body, $headers, $request->method());

        return $response;
    }

    protected function handleDirectRequests($path = null)
    {
        $request = Request::instance();

        $url     = $path;
        $body    = $request->all();
        $headers = [
            'X-Auth-Type'   => 'direct'
        ];

        $method = $request->method();

        if ($request->getQueryString() !== null)
        {
            $url .= '?' . $request->getQueryString();
        }

        if(str_contains($url, self::OAUTH_URL))
        {
            $method ='POST';
            $url = self::OAUTH_URL;
        }

        $this->trace->debug(TraceCode::CAPITAL_MARKETPLACE_DIRECT_REQUEST, [
            'request' => $url,
        ]);

        return $this->sendRequestAndParseResponse($url, $body, $headers, $method);
    }

    protected function sendRequestAndParseResponse(
        string $url,
        array $body = [],
        array $headers = [],
        string $method,
        array $options = [])
    {
        $config                  = config('applications.capital_marketplace');
        $baseUrl                 = $config['url'];
        $username                = $config['username'];
        $password                = $config['secret'];
        $timeout                 = $config['timeout'];
        $headers['Accept']       = 'application/json';
        $headers['Content-Type'] = 'application/json';
        $headers['X-Task-Id']    = $this->app['request']->getTaskId();
        $headers['Authorization'] = 'Basic '. base64_encode($username . ':' . $password);

        return $this->sendRequest($headers, $baseUrl . $url, $method, empty($body) ? '' : json_encode($body));
    }

    private function newRequest(array $headers, string $url, string $method, string $reqBody, string $contentType):
    RequestInterface
    {
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();

        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();

        $body = $streamFactory->createStream($reqBody);

        $req = $requestFactory->createRequest($method, $url);

        foreach ($headers as $key => $value) {
            $req = $req->withHeader($key, $value);
        }

        return $req
            ->withBody($body)
            ->withHeader('Accept', $contentType)
            ->withHeader('Content-Type', $contentType);
    }

    protected function sendRequest($headers, $url, $method, $body)
    {
        $this->trace->debug(TraceCode::CAPITAL_MARKETPLACE_PROXY_REQUEST, [
            'url'     => $url,
            'method'  => $method,
        ]);

        $span = Tracer::startSpan(Requests::getRequestSpanOptions($url));
        $scope = Tracer::withSpan($span);
        $span->addAttribute('http.method', $method);

        $arrHeaders = new ArrayHeaders($headers);
        Tracer::injectContext($arrHeaders);
        $headers = $arrHeaders->toArray();

        $req = $this->newRequest($headers, $url, $method, $body , 'application/json');

        $httpClient = Psr18ClientDiscovery::find();

        $resp = $httpClient->sendRequest($req);

        $span->addAttribute('http.status_code', $resp->getStatusCode());

        $traceData = [
            'status_code'   => $resp->getStatusCode(),
        ];

        if(str_contains($url, self::OAUTH_URL))
        {
            if($resp->getStatusCode() != 200)
            {
                return $this->returnViewResposne("failure");
            }
            else
            {
                return $this->returnViewResposne("success");
            }
        }
        if ($resp->getStatusCode() >= 400)
        {
            $traceData['body'] = $resp->getBody();
        }

        $this->trace->info(TraceCode::CAPITAL_MARKETPLACE_PROXY_RESPONSE, $traceData);

        $scope->close();

        return $this->parseResponse($resp->getStatusCode(), $resp->getBody());
    }

    protected function parseResponse($code, $body)
    {
        $body = json_decode($body, true);

        return ApiResponse::json($body, $code);
    }

    protected function returnViewResposne($status)
    {
        return View::Make('marketplace.marketplace')->with('status', $status)->render();
    }

    protected function handleAdminRequests($path = null)
    {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();
        $requestHeaders = Request::header();

        $this->trace->info(TraceCode::CAPITAL_MARKETPLACE_ADMIN_REQUEST, [
            'request' => $url,
        ]);


        $headers = [
            'X-Admin-Id'    => $this->ba->getAdmin()->getId() ?? '',
            'X-Admin-Email' => $this->ba->getAdmin()->getEmail() ?? '',
            'X-Auth-Type'   => 'admin'
        ];

        if (isset($requestHeaders['x-merchant-id']) === true)
        {
            $headers['x-merchant-id'] = $requestHeaders['x-merchant-id'];
        }

        if (isset($body['merchant_id']) === true)
        {
            $headers['x-merchant-id'] = $body['merchant_id'];
        }

        if (isset($body['user_id']) === true)
        {
            $headers['x-user-id'] = $body['user_id'];
        }

        if ($request->getQueryString() !== null)
        {
            $url .= '?' . $request->getQueryString();
        }
        else if (($request->method() === 'GET') and
            (empty($body) === false))
        {
            $url .= '?' . http_build_query($body);
        }

        $response = $this->sendRequestAndParseResponse($url, $body, $headers, $request->method());

        return $response;
    }

    protected function handleDevAdminRequests($path = null)
    {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();

        $this->trace->info(TraceCode::CAPITAL_MARKETPLACE_DEV_ADMIN_REQUEST, [
            'request' => $url,
        ]);

        $headers = [
            'X-Admin-Id'    => $this->ba->getAdmin()->getId() ?? '',
            'X-Admin-Email' => $this->ba->getAdmin()->getEmail() ?? '',
            'X-Auth-Type'   => 'admin'
        ];

        return $this->sendRequestAndParseResponse($url, $body, $headers, $request->method());
    }
}

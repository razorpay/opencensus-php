<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Config;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use OpenCensus\Trace\Propagator\ArrayHeaders;
use Psr\Http\Message\RequestInterface;
use Request;
use RZP\Http\Request\Requests;
use RZP\Http\RequestHeader;
use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;

class CapitalBnplController extends Controller
{
    protected function handleProxyRequests($path = null)
    {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();

        $this->trace->debug(TraceCode::CAPITAL_BNPL_PROXY_REQUEST, [
            'request' => $url,
        ]);

        $headers = [
            'X-Merchant-Id'    => optional($this->ba->getMerchant())->getId() ?? '',
            'X-Merchant-Email' => optional($this->ba->getMerchant())->getEmail() ?? '',
            'X-User-Id'        => optional($this->ba->getUser())->getId() ?? '',
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

    protected function sendRequestAndParseResponse(
        string $url,
        array $body = [],
        array $headers = [],
        string $method,
        array $options = [])
    {
        $config                  = config('applications.capital_bnpl');
        $baseUrl                 = $config['url'];
        $username                = $config['username'];
        $password                = $config['secret'];

        $headers += [
            'Accept'            => 'application/json',
            'Content-Type'      => 'application/json',
            'X-Task-Id'         => $this->app['request']->getTaskId(),
            'Authorization'     => 'Basic '. base64_encode($username . ':' . $password),
        ];


        if(!empty(Request::header(RequestHeader::DEV_SERVE_USER))){
            $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);
        }

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
        $this->trace->debug(TraceCode::CAPITAL_BNPL_PROXY_REQUEST, [
            'url'     => $url,
            'method'  => $method,
        ]);

        $span = Tracer::startSpan(Requests::getRequestSpanOptions($url));
        $scope = Tracer::withSpan($span);

        $span->addAttribute('http.method', $method);

        $arrHeaders = new ArrayHeaders($headers);
        Tracer::injectContext($arrHeaders);
        $headers = $arrHeaders->toArray();

        $req = $this->newRequest($headers, $url, $method, $body, 'application/json');

        $httpClient = Psr18ClientDiscovery::find();

        $resp = $httpClient->sendRequest($req);

        $span->addAttribute('http.status_code', $resp->getStatusCode());

        $this->trace->debug(TraceCode::CAPITAL_BNPL_PROXY_RESPONSE, [
            'status_code'   => $resp->getStatusCode(),
        ]);
        if ($resp->getStatusCode() >= 400)
        {
            $span->addAttribute('error', 'true');

            $this->trace->warning(TraceCode::CAPITAL_BNPL_PROXY_RESPONSE, [
                'status_code'   => $resp->getStatusCode(),
            ]);
        }
        else
        {
            $this->trace->debug(TraceCode::CAPITAL_BNPL_PROXY_RESPONSE, [
                'status_code'   => $resp->getStatusCode(),
            ]);
        }

        $scope->close();

        return $this->parseResponse($resp->getStatusCode(), $resp->getBody());
    }

    protected function parseResponse($code, $body)
    {
        $body = json_decode($body, true);

        return ApiResponse::json($body, $code);
    }
}

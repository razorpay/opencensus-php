<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Trace\Tracer;
use Illuminate\Support\Str;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\HashAlgo;
use RZP\Http\Request\Requests;
use RZP\Models\Admin\Permission\Name;
use Psr\Http\Message\RequestInterface;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use OpenCensus\Trace\Propagator\ArrayHeaders;

class CapitalCollectionsController extends Controller
{
    const GET      = 'GET';
    const POST     = 'POST';
    const PUT      = 'PUT';
    const PATCH    = 'PATCH';
    const DELETE   = 'DELETE';
    const MERCHANT = 'MERCHANT';

    const ROUTE_PERMISSION_MAP = [
        'v1/repayments/payment-link' => Name::CAPITAL_CREATE_PAYMENT_LINK,
    ];

    protected function handleProxyRequests($path = null)
    {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();

        $this->trace->debug(TraceCode::CAPITAL_COLLECTIONS_PROXY_REQUEST, [
            'request' => $url,
        ]);

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

        $rawContent = Request::getContent();

        $headers = Request::header();

        $receivedSignature = $headers['x-razorpay-signature'][0];

        $expectedSignature = hash_hmac(HashAlgo::SHA256,  $rawContent, config('applications.capital_collections.webhook_secret'));

        if ($receivedSignature !== $expectedSignature)
        {
            throw new Exception\BadRequestException(
                'unauthorised request : signature send by webhook does not match the expected signature');
        }

        $url     = $path;
        $body    = $request->all();
        $headers = [
            'X-Auth-Type'   => 'direct'
        ];

        if ($request->getQueryString() !== null)
        {
            $url .= '?' . $request->getQueryString();
        }

        $this->trace->debug(TraceCode::CAPITAL_COLLECTIONS_PROXY_REQUEST, [
            'request' => $url,
        ]);

        return $this->sendRequestAndParseResponse($url, $body, $headers, $request->method());
    }

    protected function handleAdminRequests($path = null)
    {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();

        $this->trace->info(TraceCode::CAPITAL_COLLECTIONS_PROXY_REQUEST, [
            'request' => $url,
        ]);

        if (isset(self::ROUTE_PERMISSION_MAP[trim($url, '/')]) === true and
            $this->ba->getAdmin()->hasPermission(self::ROUTE_PERMISSION_MAP[trim($url, '/')]) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        $headers = [
            'X-Admin-Id'    => $this->ba->getAdmin()->getId() ?? '',
            'X-Admin-Email' => $this->ba->getAdmin()->getEmail() ?? '',
            'X-Auth-Type'   => 'admin'
        ];

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

    protected function sendRequestAndParseResponse(
        string $url,
        array $body = [],
        array $headers = [],
        string $method,
        array $options = [])
    {
        $config                  = config('applications.capital_collections');
        $baseUrl                 = $config['url'];
        $username                = $config['username'];
        $password                = $config['secret'];

        $headers += [
            'Accept'            => 'application/json',
            'Content-Type'      => 'application/json',
            'X-Task-Id'         => $this->app['request']->getTaskId(),
            'Authorization'     => 'Basic '. base64_encode($username . ':' . $password),
        ];

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
        $this->trace->debug(TraceCode::CAPITAL_COLLECTIONS_PROXY_REQUEST, [
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

        if ($resp->getStatusCode() >= 400)
        {
            $span->addAttribute('error', 'true');

            $this->trace->warning(TraceCode::CAPITAL_COLLECTIONS_PROXY_RESPONSE, [
                'status_code'   => $resp->getStatusCode(),
                'body'          => $resp->getBody(),
            ]);
        }
        else
        {
            $this->trace->debug(TraceCode::CAPITAL_COLLECTIONS_PROXY_RESPONSE, [
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

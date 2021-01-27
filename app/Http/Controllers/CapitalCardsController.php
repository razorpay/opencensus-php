<?php

namespace RZP\Http\Controllers;

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

class CapitalCardsController extends Controller
{
    const GET      = 'GET';
    const POST     = 'POST';
    const PUT      = 'PUT';
    const PATCH    = 'PATCH';
    const DELETE   = 'DELETE';
    const MERCHANT = 'MERCHANT';

    const PROGRAM_ENRICH                         = 'PROGRAM_ENRICH';
    const GET_PROGRAM_BY_ID                      = 'GET_PROGRAM_BY_ID';
    const GET_PROGRAMS                           = 'GET_PROGRAMS';
    const GET_CARDS                              = 'GET_CARDS';
    const UPDATE_CARD_PREFERENCES                = 'UPDATE_CARD_PREFERENCES';
    const UPDATE_CARD                            = 'UPDATE_CARD';
    const SET_CARD_PIN                           = 'SET_CARD_PIN';
    const GET_STATEMENT                          = 'GET_STATEMENT';
    const GET_TRANSACTION                        = 'GET_TRANSACTION';
    const RAISE_DISPUTE                          = 'RAISE_DISPUTE';
    const GENERATE_OTP                           = 'GENERATE_OTP';

    const ROUTES_URL_MAP = [
        self::PROGRAM_ENRICH                         => '/v1\/program\/enrich/',
        self::GET_PROGRAM_BY_ID                      => '/v1\/program\/(.*)/',
        self::GET_PROGRAMS                           => '/v1\/program/',
        self::GET_CARDS                              => '/v1\/cards/',
        self::UPDATE_CARD_PREFERENCES                => '/v1\/card\/(.*)/',
        self::UPDATE_CARD                            => '/v1\/card\/(.*)\/(.*)/',
        self::SET_CARD_PIN                           => '/v1\/card\/(.*)\/pin/',
        self::GET_STATEMENT                          => '/v1\/statement/',
        self::GET_TRANSACTION                        => '/v1\/transaction/',
        self::RAISE_DISPUTE                          => '/v1\/transaction\/(.*)\/dispute/',
        self::GENERATE_OTP                           => '/v1\/otp/',
    ];

    const MERCHANT_ROUTES = [
        self::GET_PROGRAM_BY_ID,
        self::GET_PROGRAMS,
        self::GET_CARDS,
        self::UPDATE_CARD_PREFERENCES,
        self::UPDATE_CARD,
        self::SET_CARD_PIN,
        self::GET_STATEMENT,
        self::GET_TRANSACTION,
        self::RAISE_DISPUTE,
        self::GENERATE_OTP,
    ];

    protected function handleProxyRequests($path = null)
    {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();

        $this->trace->info(TraceCode::CAPITAL_CARDS_PROXY_REQUEST, [
            'request' => $url,
        ]);

         $isMerchantAccessible = false;
         foreach (self::MERCHANT_ROUTES as $route)
         {
             if (preg_match(self::ROUTES_URL_MAP[$route], $path, $matches) === 1)
             {
                 $isMerchantAccessible = true;
                 break;
             }
         }

         if ($isMerchantAccessible === false)
         {
             throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
         }

        $headers = [
            'x-merchant-id'    => optional($this->ba->getMerchant())->getId() ?? '',
            'X-Merchant-Email' => optional($this->ba->getMerchant())->getEmail() ?? '',
            'x-user-id'        => optional($this->ba->getUser())->getId() ?? '',
            'X-User-Role'      => $this->ba->getUserRole() ?? '',
            'X-Auth-Type'      => 'proxy',
            'x-otp'            => $body['otp'] ?? '',
        ];

        if (($request->method() === 'GET') and
            (empty($body) === false))
        {
            $url .= '?' . http_build_query($body);
        }

        $response = $this->sendRequestAndParseResponse($url, $body, $headers, $request->method());

        return $response;
    }

    protected function handleAdminRequests($path = null)
    {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();

        $this->trace->info(TraceCode::CAPITAL_CARDS_PROXY_REQUEST, [
            'request' => $url,
        ]);

         $isValidRoute = false;
         foreach (self::ROUTES_URL_MAP as $route => $regex)
         {
             if (preg_match($regex, $path, $matches) === 1)
             {
                 $isValidRoute = true;
                 break;
             }
         }

         if ($isValidRoute === false)
         {
             throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
         }

        $headers = [
            'X-Admin-Id'    => $this->ba->getAdmin()->getId() ?? '',
            'X-Admin-Email' => $this->ba->getAdmin()->getEmail() ?? '',
            'X-Auth-Type'   => 'admin'
        ];

        if ($request->getQueryString() !== null)
        {
            $url .= '?' . $request->getQueryString();
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
        $config                  = config('applications.capital_cards');
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
        $this->trace->info(TraceCode::CAPITAL_CARDS_PROXY_REQUEST, [
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

        $traceData = [
            'status_code'   => $resp->getStatusCode(),
        ];

        if ($resp->getStatusCode() >= 400)
        {
            $traceData['body'] = $resp->getBody();
        }

        $this->trace->info(TraceCode::CAPITAL_CARDS_PROXY_RESPONSE, $traceData);

        $span->addAttribute('http.status_code', $resp->getStatusCode());
        if ($resp->getStatusCode() >= 400)
        {
            $span->addAttribute('error', 'true');
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

<?php

namespace RZP\Http\Controllers;
use RZP\Models\Admin\Permission\Name;
use View;
use Config;
use Request;
use ApiResponse;
use RZP\Trace\TraceCode;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use Psr\Http\Message\RequestInterface;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use OpenCensus\Trace\Propagator\ArrayHeaders;

class OcrAdminProxyController extends BaseProxyController
{
    const GET_WEBSITE_DETAILS_FOR_OWNER = 'GetWebsiteDetailsForOwner';
    const GET_MCC_DETAILS_FOR_OWNER = 'GetMccDetailsForOwner';
    const ROUTES_URL_MAP    = [
        self::GET_WEBSITE_DETAILS_FOR_OWNER => "/website\/api\/v1\/get_website_details_for_owner/",
        self::GET_MCC_DETAILS_FOR_OWNER => "/mcc\/api\/v1\/get_mcc_details_for_owner/",
    ];

    const ADMIN_ROUTES = [
        self::GET_WEBSITE_DETAILS_FOR_OWNER,
        self::GET_MCC_DETAILS_FOR_OWNER,
    ];

    const ADMIN_ROUTES_VS_PERMISSION   = [
        self::GET_WEBSITE_DETAILS_FOR_OWNER   => Name::VIEW_ALL_ENTITY,
        self::GET_MCC_DETAILS_FOR_OWNER   => Name::VIEW_ALL_ENTITY,
    ];

    public function __construct()
    {
        parent::__construct("ocr_service");
        $this->registerRoutesMap(self::ROUTES_URL_MAP);
        $this->registerAdminRoutes(self::ADMIN_ROUTES, self::ADMIN_ROUTES_VS_PERMISSION);
        $this->setDefaultTimeout(30);
    }

    protected function getAuthorizationHeader()
    {
        return 'Basic ' . base64_encode($this->serviceConfig['user'] . ':' . $this->serviceConfig['password']);
    }
    protected function getBaseUrl(): string
    {
        return $this->serviceConfig['host'];
    }

    protected function handleAdminRequests($path = null)
    {
        $request = $this->getRequestInstance();
        $url = $path;
        $body = $request->all();

        $route = $this->getRoute($path);

        if (empty($route) === true || empty($this->adminRouteVsPermission[$route]) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        $this->ba->getAdmin()->hasPermissionOrFail($this->adminRouteVsPermission[$route]);


        $headers = $this->getHeadersForAdminRequest($body);

        if ($request->getQueryString() !== null) {
            $url .= '?' . $request->getQueryString();
        } else if (($request->method() === 'GET') and
            (empty($body) === false)) {
            $url .= '?' . http_build_query($body);
        }

        $response = $this->sendGetRequestAndParseResponse($url, $request->method(), $body, $headers);

        return $response;
    }

    protected function sendGetRequestAndParseResponse(
        string $url,
        string $method,
        array  $body = [],
        array  $headers = [])
    {
        $baseUrl = $this->getBaseUrl();
        $finalUrl = $baseUrl.'/';
        return $this->sendRequest($headers, $finalUrl . $url, $method, empty($body) ? '' : json_encode($body));
    }

    protected function sendRequest($headers, $url, $method, $body, $options = [])
    {
        $this->trace->info(TraceCode::PROXY_REQUEST, [
            'url' => $url,
            'method' => $method,
            'service' => 'ocr_service',
        ]);

        $arrHeaders = new ArrayHeaders($headers);
        $headers    = $arrHeaders->toArray();

        $headers = array_merge($headers, [
            RequestHeader::DEV_SERVE_USER =>  Request::header(RequestHeader::DEV_SERVE_USER)
        ]);


        $req = $this->newRequest($method, $url, $body, $headers);

        $httpClient = Psr18ClientDiscovery::find();

        $resp = $httpClient->sendRequest($req);

        $this->trace->info(TraceCode::PROXY_RESPONSE, [
            'status_code' => $resp->status_code,
            'url'         => $url,
            'method'      => $method,
            'service'     => $this->service
        ]);

        return $this->parseResponse($resp->getStatusCode(), $resp->getBody());
    }
    protected function newRequest(string $method, string $url, string $reqBody, array $headers): RequestInterface
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
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json');
    }
    protected function parseResponse($code, $body)
    {
        $body = json_decode($body, true);

        return ApiResponse::json($body, $code);
    }
}

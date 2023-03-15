<?php


namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use OpenCensus\Trace\Propagator\ArrayHeaders;
use Psr\Http\Message\RequestInterface;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;

class MerchantsRiskController extends Controller
{
    const RISK_DETAILS  = 'RISK_DETAILS';
    const FRAUD_DETAILS = 'FRAUD_DETAILS';

    const ROUTES_URL_MAP = [
        self::RISK_DETAILS          => '/twirp\/rzp.merchants_risk.impersonation.v1.ImpersonationService\/GetDetails/',
        self::FRAUD_DETAILS         => '/twirp\/rzp.merchants_risk.fraudlist.v1.FraudlistService\/GetFraudList/'
    ];

    const CREATE_ALERT_CONFIG_URL   = 'twirp/rzp.merchants_risk.riskAlertConfig.v1.RiskAlertConfigService/Create';
    const UPDATE_ALERT_CONFIG_URL   = 'twirp/rzp.merchants_risk.riskAlertConfig.v1.RiskAlertConfigService/Update';
    const DELETE_ALERT_CONFIG_URL   = 'twirp/rzp.merchants_risk.riskAlertConfig.v1.RiskAlertConfigService/DeleteById';

    const MERCHANT_ROUTES = [
        self::RISK_DETAILS,
        self::FRAUD_DETAILS
    ];

    protected function handleProxyRequests($path = null)
    {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();

        $this->trace->info(TraceCode::MERCHANTS_RISK_PROXY_REQUEST, [
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

        $response = $this->sendRequestAndParseResponse($url, $request->method(), $body, $headers);

        return $response;
    }

    protected function handleAdminRequests($path = null)
    {
        $request = Request::instance();
        $url     = $path;
        $body    = $request->all();

        $this->trace->info(TraceCode::MERCHANTS_RISK_PROXY_REQUEST, [
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

        $response = $this->sendRequestAndParseResponse($url, $request->method(), $body, $headers);

        return $response;
    }

    public function createAlertConfig() {
        $input = Request::all();

        $this->trace->info(TraceCode::MERCHANTS_RISK_ALERT_CONFIG, [
            'request'   => $input,
        ]);

        $url     = self::CREATE_ALERT_CONFIG_URL;

        $headers = [
            'X-Admin-Id'    => $this->ba->getAdmin()->getId() ?? '',
            'X-Admin-Email' => $this->ba->getAdmin()->getEmail() ?? '',
            'X-Auth-Type'   => 'admin'
        ];

        return $this->sendRequestAndParseResponse($url, "POST", $input, $headers);

    }

    public function updateAlertConfig($ruleId) {
        $input = Request::all();
        $input['id'] = $ruleId;

        $this->trace->info(TraceCode::MERCHANTS_RISK_ALERT_CONFIG, [
            'request'   => $input,
        ]);

        $url     = self::UPDATE_ALERT_CONFIG_URL;

        $headers = [
            'X-Admin-Id'    => $this->ba->getAdmin()->getId() ?? '',
            'X-Admin-Email' => $this->ba->getAdmin()->getEmail() ?? '',
            'X-Auth-Type'   => 'admin'
        ];

        $response = $this->sendRequestAndParseResponse($url, "POST", $input, $headers);

        return $response;
    }

    public function deleteAlertConfig($ruleId) {
        $input = Request::all();
        $input['id'] = $ruleId;

        $this->trace->info(TraceCode::MERCHANTS_RISK_ALERT_CONFIG, [
            'request'   => $input,
        ]);

        $url     = self::DELETE_ALERT_CONFIG_URL;

        $headers = [
            'X-Admin-Id'    => $this->ba->getAdmin()->getId() ?? '',
            'X-Admin-Email' => $this->ba->getAdmin()->getEmail() ?? '',
            'X-Auth-Type'   => 'admin'
        ];

        $response = $this->sendRequestAndParseResponse($url, "POST", $input, $headers);

        return $response;
    }

    protected function sendRequestAndParseResponse(
        string $url,
        string $method,
        array $body = [],
        array $headers = [],
        array $options = [])
    {
        $config                  = config('services.merchants_risk');
        $baseUrl                 = $config['url'];
        $username                = $config['auth']['key'];
        $password                = $config['auth']['secret'];
        $headers['Accept']       = 'application/json';
        $headers['Content-Type'] = 'application/json';
        $headers['X-Task-Id']    = $this->app['request']->getTaskId();
        $headers['Authorization'] = 'Basic '. base64_encode($username . ':' . $password);

        return $this->sendRequest($headers, $baseUrl."/". $url, $method, empty($body) ? '' : json_encode($body));
    }

    protected function sendRequest($headers, $url, $method, $body)
    {
        $this->trace->info(TraceCode::MERCHANTS_RISK_PROXY_REQUEST, [
            'url'     => $url,
            'method'  => $method,
        ]);

        $req = $this->newRequest($headers, $url, $method, $body , 'application/json');

        $httpClient = Psr18ClientDiscovery::find();

        $resp = $httpClient->sendRequest($req);

        $this->trace->info(TraceCode::MERCHANTS_RISK_PROXY_RESPONSE, [
            'status_code'   => $resp->getStatusCode(),
        ]);

        return $this->parseResponse($resp->getStatusCode(), $resp->getBody());
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

    protected function parseResponse($code, $body)
    {
        $body = json_decode($body, true);

        if ($code === 404)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        return ApiResponse::json($body, $code);
    }
}

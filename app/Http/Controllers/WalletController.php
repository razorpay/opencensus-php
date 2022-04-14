<?php

namespace RZP\Http\Controllers;

use Elasticsearch\Common\Exceptions\Forbidden403Exception;
use Razorpay\Api\Errors\BadRequestError;
use Request;
use ApiResponse;

use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Mail\System\Trace;
use RZP\Models\Feature\Constants;
use RZP\Trace\TraceCode;
use Psr\Http\Message\RequestInterface;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use OpenCensus\Trace\Propagator\ArrayHeaders;

class WalletController extends Controller
{
    const GET      = 'GET';
    const POST     = 'POST';
    const PUT      = 'PUT';
    const PATCH    = 'PATCH';
    const DELETE   = 'DELETE';
    const MERCHANT = 'MERCHANT';

    const USER = 'api';

    // merchant routes
    const CREATE_USER       = 'CREATE_USER';
    const SEND_OTP          = 'SEND_OTP';
    const VERIFY_OTP        = 'VERIFY_OTP';
    const CREATE_WALLET     = 'CREATE_WALLET';

    // admin route
    const TRANSFER          = 'TRANSFER';
    const TRANSFER_STATUS   = 'TRANSFER_STATUS';

    const ROUTES_URL_MAP = [
        // merchant routes
        self::CREATE_USER       => 'user',
        self::SEND_OTP          => 'user/otp',
        self::VERIFY_OTP        => 'user/(\w+)/verify',
        self::CREATE_WALLET     => 'wallet',

        // admin route
        self::TRANSFER          => 'transfer',
        self::TRANSFER_STATUS   => 'transfer/(\w+)',
    ];

    const MERCHANT_ROUTES = [
        self::CREATE_USER,
        self::SEND_OTP,
        self::VERIFY_OTP,
        self::CREATE_WALLET,
    ];

    const ADMIN_ROUTES = [
        self::TRANSFER,
        self::TRANSFER_STATUS,
    ];

    protected function handleProxyMerchantRequests($path = null)
    {
        $merchantId = $this->ba->getMerchant()->getId();

        // merchant with wallet feature can only access these routes
        $feature = $this->repo->feature->findMerchantWithFeatures($merchantId, [Constants::WALLET]);
        if (count($feature) === 0)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
        }

        return $this->handleProxyRequests(self::MERCHANT_ROUTES,  $path, $merchantId);
    }

    protected function handleProxyAdminRequests($path = null)
    {
        return $this->handleProxyRequests(self::ADMIN_ROUTES,  $path);
    }

    private function handleProxyRequests(array $routeList, $path = null, $merchantId = null)
    {
        $request = Request::instance();
        $url     = '/v1/' . $path;
        $body    = $request->all();

        $this->trace->info(TraceCode::WALLET_SERVICE_PROXY_REQUEST, [
            'request' => $url,
        ]);

        foreach ($routeList as $route)
        {
            $pattern = '@'.self::ROUTES_URL_MAP[$route].'@';
            if (preg_match($pattern, $path) === 1)
            {
                // add merchant id to the request body based on the auth
                // in case of admin request merchant ID should be part of body implicitly
                if (empty($merchantId) === false)
                {
                    $body['merchant_id'] = $this->ba->getMerchant()->getId();
                }

                // convert the request data into query params in case of get request
                if (($request->method() === 'GET') and
                    (empty($body) === false))
                {
                    $url .= '?' . http_build_query($body);
                }

                return $this->sendRequestAndParseResponse($request->method(), $url, $body);
            }
        }

        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
    }

    private function sendRequestAndParseResponse(
        string $method,
        string $url,
        array $body = [],
        array $headers = [],
        array $options = [])
    {
        $mode = app('rzp.mode') ? app('rzp.mode') : Mode::LIVE;

        $config     = config('applications.wallet');
        $baseUrl    = $config['url'][$mode];
        $username   = $config[self::USER][$mode]['username'];
        $password   = $config[self::USER][$mode]['secret'];

        // updates header values
        $headers['Accept']       = 'application/json';
        $headers['Content-Type'] = 'application/json';
        $headers['Authorization'] = 'Basic '. base64_encode($username . ':' . $password);

        return $this->sendRequest($headers, $baseUrl . $url, $method, empty($body) ? '' : json_encode($body));
    }

    private function sendRequest($headers, $url, $method, $body)
    {
        $this->trace->info(TraceCode::WALLET_SERVICE_PROXY_REQUEST, [
            'url'     => $url,
            'method'  => $method,
        ]);

        $arrHeaders = new ArrayHeaders($headers);
        $headers = $arrHeaders->toArray();

        $req = $this->newRequest($method, $url, $body , $headers);

        $httpClient = Psr18ClientDiscovery::find();

        $resp = $httpClient->sendRequest($req);

        $this->trace->info(TraceCode::WALLET_SERVICE_PROXY_RESPONSE, [
            'status_code'   => $resp->getStatusCode(),
        ]);

        return $this->parseResponse($resp->getStatusCode(), $resp->getBody());
    }

    private function newRequest( string $method, string $url, string $reqBody, array $headers): RequestInterface
    {
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();

        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();

        $body = $streamFactory->createStream($reqBody);

        $req = $requestFactory->createRequest($method, $url);

        foreach ($headers as $key => $value) {
            $req = $req->withHeader($key, $value);
        }

        return $req->withBody($body);
    }

    private function parseResponse($code, $body)
    {
        $body = json_decode($body, true);

        // throwing exception to keep the error response format consistent
        if ($code === 404)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
        else if ($code !== 200)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_REQUEST_BODY);
        }

        return ApiResponse::json($body, $code);
    }
}

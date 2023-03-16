<?php

namespace RZP\Services;

use Cache;
use ApiResponse;
use RZP\Constants\Tracing;
use RZP\Exception;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Http\RequestHeader;
use Illuminate\Http\Request;
use RZP\Http\Request\Requests;
use Razorpay\Trace\Logger as Trace;



class PaymentLinkService
{
    const CONTENT_TYPE_JSON = 'application/json';

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $key;

    /*
     * @var string
     */
    protected $secret;

    protected $ba;

    protected $timeOut;

    protected $trace;

    protected $plUrls;

    protected $app;

    protected $mock;

    public function __construct($app)
    {
        $this->app     = $app;
        $plinkConfig   = $app['config']->get('applications.payment_links');
        $this->trace   = $app['trace'];
        $this->baseUrl = $plinkConfig['url'];
        $this->key     = $plinkConfig['username'];
        $this->secret  = $plinkConfig['secret'];
        $this->timeOut = $plinkConfig['timeout'];
        $this->ba      = $app['basicauth'];
        $this->plUrls  = $plinkConfig['pl_urls'];
        $this->mock    = $plinkConfig['mock'];
    }

    public function sendRequest(\Illuminate\Http\Request $request, $param = null)
    {
        $params = $this->getRequestParams($request);

        try {
            $response = \Requests::request(
                $params['url'],
                $params['headers'],
                $params['data'],
                $params['method'],
                $params['options']);

            return $this->parseAndReturnResponse($response);
        }
        catch(\Throwable $e)
        {
            throw new Exception\ServerErrorException(
                'Error on payment link service',
                ErrorCode::SERVER_ERROR_PAYMENT_LINK_SERVICE_FAILURE,
                null,
                $e
            );
        }
    }

    public function notifyOrderPaid(Order\Entity $order, Payment\Entity $payment)
    {
        try
        {

            $data = [
                Entity::ORDER     => $order->toArray(),
                Entity::PAYMENT   => $payment->toArray(),
                Entity::DISCOUNT  =>  isset($payment->discount) ? $payment->discount->toArrayPublic() : null,
            ];

            // Return fee in payment currency
            // Slack: https://razorpay.slack.com/archives/C7WEGELHJ/p1677061101772369?thread_ts=1675832734.858449&cid=C7WEGELHJ
            $data[Entity::PAYMENT]['fee_in_mcc'] = $payment->getFeeInMcc() ?? 0;

            $this->trace->info(
                TraceCode::ORDER_NOTIFY_REQUEST_FOR_PAYMENT_V2,
                [
                    'data'    => $data,
                ]);

            $merchant = $payment->merchant;

            $this->setRequestParamsAndSendRequest($merchant, $data,  $this->plUrls['verify_order'], Request::METHOD_POST);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENT_LINK_SERVICE_REQUEST_FAILURE,
                [
                    'data' => $data
                ]);
        }
    }

    public function notifyMerchantStatusAction(Merchant\Entity $merchant)
    {
        try
        {
            $data = [
                Merchant\Entity::MERCHANT_ID => $merchant->getId(),
            ];

            $this->trace->info(
                TraceCode::MERCHANT_CACHE_EVICTION_REQUEST_FOR_PAYMENT_LINK_V2,
                [
                    'data'    => $data,
                ]);

            $this->setRequestParamsAndSendRequest($merchant, $data,  $this->plUrls['evict_merchant_cache'], Request::METHOD_POST);
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENT_LINK_SERVICE_REQUEST_FAILURE,
                [
                    'data' => $data
                ]);
        }
    }

    public function getById(string $id)
    {
        try
        {
            $response = $this->setRequestParamsAndSendRequest(
                null,
                ['action' => 'FetchById', 'id' => $id],
                'v1/payment_links_admin',
                Request::METHOD_POST);

            if (($response !== null) and ($response['status_code'] === 200))
            {
                return $response['response'];
            }

            return null;
        }
        catch (\Exception $e)
        {
            return null;
        }
    }

    protected function setRequestParamsAndSendRequest($merchant, array $data, string $url, string $method)
    {
        if ($this->mock === true)
        {
            return;
        }

        $url = $this->baseUrl.$url;

        $headers = [
            'Accept'            => self::CONTENT_TYPE_JSON,
            'Content-Type'      => self::CONTENT_TYPE_JSON,
            'X-Razorpay-TaskId' => $this->app['request']->getTaskId(),
            'X-Razorpay-Mode'   => $this->ba->getMode(),
        ];

        if ($merchant !== null)
        {
            $enabledFeatures = $merchant->getEnabledFeatures();

            $headers['X-Razorpay-Merchant-Features'] = json_encode($enabledFeatures);
        }

        $options = [
            'timeout' => $this->timeOut,
            'auth'    => [$this->key, $this->secret],
        ];

        $params = [
            'url'     => $url,
            'headers' => $headers,
            'data'    => json_encode($data),
            'options' => $options,
            'method'  => $method,
        ];

        $this->trace->info(
            TraceCode::ORDER_NOTIFY_REQUEST_PARAMS_FOR_PAYMENT_V2,
            [
                'params'    => $data,
            ]);

        $response = \Requests::request(
            $params['url'],
            $params['headers'],
            $params['data'],
            $params['method'],
            $params['options']);

        $this->trace->info(
            TraceCode::ORDER_NOTIFY_RESPONSE_FOR_PAYMENT_V2,
            [
                'response'    => $this->parseAndReturnResponse($response),
            ]);

        return $this->parseAndReturnResponse($response);
    }

    protected function parseAndReturnResponse($res)
    {
        $code = $res->status_code;

        $contentType  = $res->headers['content-type'];

        $res = json_decode($res->body, true);

        return ['status_code' => $code, 'response' => $res];
    }

    protected function getRequestParams(\Illuminate\Http\Request $request)
    {
        $path = $request->path();

        $url = $this->baseUrl . $path;

        $urlAppend = '?';

        if ($request->getQueryString() !== null)
        {
            $url .= $urlAppend . $request->getQueryString();

            $urlAppend = '&';
        }

        $method = $request->method();

        $headers = $this->getHeaders($request);

        $requestBody = [];

        $body = $request->post();

        if ((empty($body) === false) && ($request->method() !== Request::METHOD_GET))
        {
            $requestBody = json_encode($body);
        }

        //needed because dashboard backend passes the get params in request body
        if ((empty($body) === false) && ($request->method() === Request::METHOD_GET))
        {
            $extraParams = http_build_query( $body );

            $extraParams = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', $extraParams);

            $url .= $urlAppend .$extraParams;
        }

        $options = [
            'timeout' => $this->timeOut,
            'auth'    => [$this->key, $this->secret],
        ];

        $this->trace->info(TraceCode::PAYMENT_LINK_SERVICE_REQUEST, ['url' => Tracing::maskUrl($url)]);

        $response = [
            'url'     => $url,
            'headers' => $headers,
            'data'    => $requestBody,
            'options' => $options,
            'method'  => $method,
        ];

        return $response;
    }

    public function sendDirectRequestParams(string $path, string $method, $merchant, $body)
    {
        $url = $this->baseUrl . $path;

        $headers = [
            'Accept'            => self::CONTENT_TYPE_JSON,
            'Content-Type'      => self::CONTENT_TYPE_JSON
        ];

        $headers['X-Razorpay-MerchantId'] = $merchant->getId();

        $enabledFeatures = $merchant->getEnabledFeatures();

        $headers['X-Razorpay-Merchant-Features'] = json_encode($enabledFeatures);

        $headers['X-Razorpay-Mode']       = $this->ba->getMode();

        $requestBody = [];

        if ((empty($body) === false) && ($method !== Request::METHOD_GET))
        {
            $requestBody = json_encode($body);
        }

        $options = [
            'timeout' => $this->timeOut,
            'auth'    => [$this->key, $this->secret],
        ];

        $this->trace->info(TraceCode::PAYMENT_LINK_SERVICE_REQUEST, ['url' => Tracing::maskUrl($url)]);

        $params = [
            'url'     => $url,
            'headers' => $headers,
            'data'    => $requestBody,
            'options' => $options,
            'method'  => $method,
        ];

        $response = \Requests::request(
            $params['url'],
            $params['headers'],
            $params['data'],
            $params['method'],
            $params['options']);

        return $this->parseAndReturnResponse($response);
    }

    protected function getHeaders(\Illuminate\Http\Request $request): array
    {
        $headers = [
            'Accept'            => self::CONTENT_TYPE_JSON,
            'Content-Type'      => self::CONTENT_TYPE_JSON,
            'X-Razorpay-TaskId' => $request->getTaskId(),
        ];

        if ($this->ba->getMerchantId() !== null)
        {
            $headers['X-Razorpay-MerchantId'] = $this->ba->getMerchantId();

            $merchant = $this->ba->getMerchant();

            $enabledFeatures = $merchant->getEnabledFeatures();

            $headers['X-Razorpay-Merchant-Features'] = json_encode($enabledFeatures);
        }

        $user = $this->ba->getUser();

        if ($user !== null)
        {
            $headers['X-Razorpay-UserId'] = $user->getId();

            $role = $this->ba->getUserRole();

            $headers['X-Razorpay-UserRole'] = $role;
        }

        if ($this->ba->isBatchApp() === true)
        {
            $batchId = $request->header(RequestHeader::X_Batch_Id) ?? null;

            if ($batchId !== null)
            {
                $headers['X-Razorpay-BatchId'] = $batchId;
            }
        }

        $headers['X-Razorpay-Mode']       = $this->ba->getMode();

        $headers['X-Razorpay-Auth']       = $this->ba->getAuthType();

        $headers['X-Razorpay-Public-Key'] = $this->ba->getPublicKey();

        $headers['X-Razorpay-Application-Id'] = $this->ba->getOAuthApplicationId();

        return $headers;
    }
}

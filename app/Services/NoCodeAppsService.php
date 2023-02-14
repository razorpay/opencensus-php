<?php
namespace RZP\Services;


use Illuminate\Http\Request;
use RZP\Constants\Tracing;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Order\ProductType;
use RZP\Exception\ServerErrorException;

class NoCodeAppsService {
    const SOURCE_S2S = "s2s";

    const SOURCE_CHECKOUT = "checkout";

    const CONTENT_TYPE_JSON = 'application/json';

    const MERCHANT_ID = 'merchant_id';

    const PUBLIC_KEY = 'public_key';

    const REQUEST_TO_NOCODE_SERVICE_FAILED  = 'REQUEST_TO_NOCODE_SERVICE_FAILED';

    // HEADERS
    const ACCEPT                                  = 'Accept';
    const CONTENT_TYPE                            = 'Content-type';
    const X_RAZORPAY_TASK_ID                      = 'X-Razorpay-Task-Id';
    const X_RAZORPAY_MERCHANT_ID                  = 'X-Razorpay-Merchant-Id';
    const X_RAZORPAY_USER_ROLE                    = 'X-Razorpay-User-Role';

    const X_RAZORPAY_USER_EMAIL                    = 'X-Razorpay-User-Email';
    const X_RAZORPAY_USER_ID                      = 'X-Razorpay-User-Id';
    const X_RAZORPAY_MODE                         = 'X-Razorpay-Mode';
    const X_RAZORPAY_AUTH                         = 'X-Razorpay-Auth';
    const USER_AGENT                              = 'User-Agent';
    const X_USER_AGENT                            = 'X-User-Agent';
    const X_RAZORPAY_PUBLIC_KEY                   = 'X-Razorpay-Public-Key';
    const X_RAZORPAY_APPLICATION_ID               = 'X-Razorpay-Application-Id';
    const X_RAZORPAY_REQUESTER                    = 'X-Razorpay-Requester';
    const X_RAZORPAY_TRANSFORMER                  = 'X-Razorpay-NCA-Transform';



    /**
     * @var string
     */
    protected string $baseUrl;

    /**
     * @var string
     */
    protected string $key;

    protected string $secret;

    protected mixed $ba;

    protected mixed $timeOut;
    protected \Razorpay\Trace\Logger $trace;

    protected bool $mock;

    protected array $config;

    public function __construct($app)
    {
        $this->app = $app;
        $this->config  = $this->app['config']->get('applications.no_code_apps');
        $this->trace = $app['trace'];
        $this->baseUrl = $this->config['url'];
        $this->key     = $this->config['username'];
        $this->secret  = $this->config['secret'];
        $this->timeOut = $this->config['timeout'];
        $this->ba      = $this->app['basicauth'];
        $this->mock    = $this->config['mock'];
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string|null              $module
     * @param string|null              $path
     *
     * @return array
     * @throws \RZP\Exception\ServerErrorException
     */
    public function forwardNcaRequest(Request $request, string $module=null, string $path=null): array
    {
        if ($this->mock === true)
        {
            return [];
        }

        $params = $this->getNcaRequestParams($request, $module, $path);

        return $this->makeApiCall($params['url'], $params['headers'], $params['data'], $params['method'], $params['options']);
    }

    /**
     * @param \RZP\Models\Payment\Entity $payment
     *
     * @return array
     */
    public function sendS2SPaymentEvent(Payment\Entity $payment): array
    {
        if ($payment->hasOrder() !== true
            || $payment->order->getProductType() !== ProductType::PAYMENT_STORE)
        {
            return [];
        }

        $body = $payment->toArrayPublic();
        $body["order"] = $payment->order->toArrayPublic();

        return $this->sendPaymenEventToNocode($body, $payment->getMerchantId(), $this->ba->getMode(), self::SOURCE_S2S);
    }

    /**
     * @param array  $input
     * @param string $merchantId
     * @param string $mode
     * @param string $source
     *
     * @return array
     */
    public function sendPaymenEventToNocode(array $input, string $merchantId, string $mode, string $source): array
    {
        $path = sprintf($this->config["nca_urls"]["payment_process"], $source);

        $url = $this->baseUrl.$path;

        $body = json_encode($input);

        $options = [
            'timeout'          => $this->timeOut,
            'auth'             => [$this->key, $this->secret],
            'follow_redirects' => false,
        ];

        $this->trace->info(TraceCode::NOCODE_SERVICE_REQUEST, ['url' => Tracing::maskUrl($url)]);

        $headers = [
            self::ACCEPT                    => self::CONTENT_TYPE_JSON,
            self::CONTENT_TYPE              => self::CONTENT_TYPE_JSON,
            self::X_RAZORPAY_TASK_ID        => $this->app['request']->getTaskId(),
            self::X_RAZORPAY_MERCHANT_ID    => $merchantId,
            self::X_RAZORPAY_MODE           => $mode,
        ];

        $response = [];

        try
        {
            $response = $this->makeApiCall($url, $headers, $body, "POST", $options);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::NOCODE_SERVICE_REQUEST_FAILED, [
                'url' => Tracing::maskUrl($url),
                'error' => $e->getMessage(),
            ]);
        }

        return $response;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param string|null              $module
     * @param string|null              $path
     *
     * @return array
     */
    private function getNcaRequestParams(Request $request, string $module=null, string $path=null): array
    {
        $path = $this->getRequestUri($module, $path);

        $url = $this->baseUrl . $path;

        $urlAppend = '?';

        $headers = $this->getHeaders($request);

        if($request->getQueryString() !== null)
        {
            $url .= $urlAppend . $request->getQueryString();

            $urlAppend = '&';
        }

        $method = $request->method();

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

            $url .= $urlAppend .$extraParams;
        }

        $options = [
            'timeout'          => $this->timeOut,
            'auth'             => [$this->key, $this->secret],
            'follow_redirects' => true,
        ];

        $this->trace->info(TraceCode::NOCODE_SERVICE_REQUEST, ['url' => Tracing::maskUrl($url)]);

        return [
            'url'     => $url,
            'headers' => $headers,
            'data'    => $requestBody,
            'options' => $options,
            'method'  => $method,
        ];
    }

    protected function getHeaders(Request $request)
    {
        $headers = [
            self::ACCEPT           => self::CONTENT_TYPE_JSON,
            self::CONTENT_TYPE     => self::CONTENT_TYPE_JSON,
            self::X_RAZORPAY_TASK_ID   => $this->app['request']->getTaskId(),
        ];

        if($this->ba->getMerchantId() !== null)
        {
            $headers[self::X_RAZORPAY_MERCHANT_ID] = $this->ba->getMerchantId();
        }
        /**
         * @var $user \RZP\Models\User\Entity
         */
        $user = $this->ba->getUser();

        if($user !== null)
        {
            $headers[self::X_RAZORPAY_USER_ID] = $user->getId();
            $headers[self::X_RAZORPAY_USER_EMAIL] = $user->getEmail();

            $role = $this->ba->getUserRole();

            $headers[self::X_RAZORPAY_USER_ROLE] = $role;
        }

        $headers[self::X_RAZORPAY_MODE]  = $this->ba->getMode();

        $headers[self::X_RAZORPAY_AUTH] = $this->ba->getAuthType();

        $headers[self::USER_AGENT] = $request->userAgent();

        $headers[self::X_USER_AGENT] = $request->header(self::X_USER_AGENT);

        $headers[self::X_RAZORPAY_PUBLIC_KEY] = $this->ba->getPublicKey();

        $headers[self::X_RAZORPAY_APPLICATION_ID] = $this->ba->getOAuthApplicationId();

        $requester = $request->header(self::X_RAZORPAY_REQUESTER);

        if(empty($requester) === false)
        {
            $headers[self::X_RAZORPAY_REQUESTER] = $requester;
        }

        $headers[self::X_RAZORPAY_TRANSFORMER] = $request->header(self::X_RAZORPAY_TRANSFORMER, '0');

        return $headers;
    }

    protected function parseAndReturnResponse($res)
    {
        $code = $res->status_code;

        $contentType  = $res->headers['content-type'];

        $res = json_decode($res->body, true);

        return ['code' => $code, 'data' => $res];
    }

    /**
     * @param string $url
     * @param array  $headers
     * @param string $data
     * @param string $method
     * @param array  $options
     *
     * @return array
     * @throws \RZP\Exception\ServerErrorException
     */
    private function makeApiCall(string $url, array $headers, mixed $data, string $method, array $options): array
    {
        try
        {
            $response = Requests::request($url, $headers, $data, $method, $options);

            return $this->parseAndReturnResponse($response);
        }
        catch(\Throwable $e)
        {
            throw new ServerErrorException(
                'Error on nocode service',
                ErrorCode::SERVER_ERROR_NOCODE_APPS_SERVICE_FAILURE,
                null,
                $e
            );
        }
    }

    /**
     * @param string|null $module
     * @param string|null $path
     *
     * @return string
     */
    private function getRequestUri(string $module=null, string $path=null): string
    {
        if ($module === null || $module === "")
        {
            $module = "stores";
        }


        if ($path != null)
        {
            $module .= "/" . $path;
        }

        return $module;
    }
}

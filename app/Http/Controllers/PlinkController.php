<?php

namespace RZP\Http\Controllers;

use App;
use Requests;
use ApiResponse;
use Illuminate\Http\Request;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class PlinkController extends Controller
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

    public function __construct()
    {
        parent::__construct();

        $plinkConfig  = $this->config->get('applications.payment_links');
        $this->baseUrl = $plinkConfig['url'];
        $this->key     = $plinkConfig['username'];
        $this->secret  = $plinkConfig['secret'];
        $this->timeOut = $plinkConfig['timeout'];
        $this->ba      = $this->app['basicauth'];
    }

    public function sendRequest(Request $request, $param = null)
    {
        $params = $this->getRequestParams($request);

        try {
            $response = Requests::request(
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

        return ApiResponse::json($res);
    }

    public function plDemo(Request $request)
    {
        $input = $request->post();

        $this->validateCaptcha($input);

        $request->request->remove('captcha');

        $this->sendRequest($request);

    }

    protected function validateCaptcha(array $input)
    {
        $app = App::getFacadeRoot();

        if ($app->environment('production') === false)
        {
            return;
        }

        if(isset($input['captcha']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CAPTCHA_TOKEN_NOT_PRESENT
            );
        }

        $captchaResponse = $input['captcha'];

        $clientIpAddress = $_SERVER['HTTP_X_IP_ADDRESS'] ?? $app['request']->ip();

        $noCaptchaSecret = config('app.pl_demo.nocaptcha_secret');

        $input = [
            'secret'   => $noCaptchaSecret,
            'response' => $captchaResponse,
            'remoteip' => $clientIpAddress,
        ];

        $captchaQuery = http_build_query($input);

        $url = 'https://www.google.com/recaptcha/api/siteverify?'. $captchaQuery;

        $response = \Requests::get($url);

        $output = json_decode($response->body);

        if($output->success !== true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CAPTCHA_FAILED,
                null,
                [
                    'output_from_google'        => (array)$output,
                ]
            );
        }
    }

    protected function parseAndReturnResponse($res)
    {
        $code = $res->status_code;

        $contentType  = $res->headers['content-type'];

        if (str_contains($contentType, self::CONTENT_TYPE_JSON) === true)
        {
            $res = json_decode($res->body, true);

            return ApiResponse::json($res, $code);
        }
        else
        {
            $res = $res->body;

            return \Response::make($res, $code);
        }
    }

    protected function getRequestParams(Request $request)
    {
        $path = $request->path();

        $url = $this->baseUrl . $path;

        if ($request->getQueryString() !== null)
        {
            $url .= '?' . $request->getQueryString();
        }

        $method = $request->method();

        $headers = $this->getHeaders();

        $requestBody = [];

        $body = $request->post();

        if ((empty($body) === false) && ($request->method() !== Request::METHOD_GET))
        {
            $requestBody = json_encode($body);
        }

        $options = [
            'timeout' => $this->timeOut,
            'auth'    => [$this->key, $this->secret],
        ];

        $this->trace->info(TraceCode::PAYMENT_LINK_SERVICE_REQUEST, ['url' => $url]);

        $response = [
            'url'     => $url,
            'headers' => $headers,
            'data'    => $requestBody,
            'options' => $options,
            'method'  => $method,
        ];

        return $response;
    }

    protected function getHeaders(): array
    {
        $headers = [
            'Accept'            => self::CONTENT_TYPE_JSON,
            'Content-Type'      => self::CONTENT_TYPE_JSON,
            'X-Razorpay-TaskId' => $this->app['request']->getTaskId(),
        ];

        if ($this->ba->getMerchantId() !== null)
        {
            $headers['X-Razorpay-MerchantId'] = $this->ba->getMerchantId();
        }

        $user = $this->ba->getUser();

        if ($user !== null)
        {
            $headers['X-Razorpay-UserId'] = $user->getId();
        }

        $headers['X-Razorpay-Mode']          = $this->ba->getMode();
        $headers['X-Razorpay-Auth']          = $this->ba->getAuthType();

        return $headers;
    }
}


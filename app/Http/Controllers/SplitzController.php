<?php

namespace RZP\Http\Controllers;

use Request;
use RZP\Http\Request\Requests;
use ApiResponse;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Services\UfhService;
use RZP\Services\SplitzService;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Services\Mock\UfhService as MockUfhService;

class SplitzController extends Controller
{
    const CONTENT_TYPE_JSON = 'application/json';

    const EVALUATE_URL = 'twirp/rzp.splitz.evaluate.v1.EvaluateAPI/Evaluate';

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $secret;

    /**
    * @var string
    */
    protected $requestTimeout;


    public function __construct()
    {
        parent::__construct();

        $splitzConfig         = $this->config->get('applications.splitz');
        $this->baseUrl        = $splitzConfig['url'];
        $this->key            = $splitzConfig['username'];
        $this->secret         = $splitzConfig['secret'];
        $this->requestTimeout = $splitzConfig['request_timeout'];
    }

    public function uploadFileAndGetUrl()
    {
        $input = Request::all();
        $app = $this->app;
        $ufhServiceMock = $app['config']->get('applications.ufh.mock');

        $file = $input['file'];

        if ($ufhServiceMock === true)
        {
            $ufhService = new MockUfhService($app);
        }
        else
        {
            $ufhService = new UfhService($app);
        }

        //
        // Adding a prefix id for filename to avoid overwrites to the same fileName on S3.
        //
        $partial = UniqueIdEntity::generateUniqueId();

        $fileIdentifier = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $fileName = 'splitz_segment/' . $partial . '/' . $fileIdentifier;

        $extension = strtolower($file->getClientOriginalExtension());
        if (empty($extension) === false)
        {
            $fileName .= '.'. $extension;
        }

        $response = $ufhService->uploadFileAndGetUrl($input['file'], $fileName, 'splitz_segment', null);

        return ApiResponse::json($response);
    }

    public function allowCors()
    {
        $response = $this->app->splitzService->allowCors();

        return $response;
    }

    public function evaluateRequestBulk()
    {
        $parameters = Request::all();

        $response = $this->app->splitzService->bulkCallsToSplitz($parameters);

        return ApiResponse::json($response);
    }

    public function evaluateRequest()
    {
        $parameters = Request::all();
        $response = [];

        try {
            if (empty($parameters) === false)
            {

                $response = $this->app->splitzService->evaluateRequest($parameters);

                $response = ApiResponse::json($response);

            }
        } catch (\Throwable $e)
        {
            throw new Exception\ServerErrorException('Error completing the request', ErrorCode::SERVER_ERROR_SPLITZ_FAILURE, null, $e);
        }

        return $response;
    }

    public function sendRequest()
    {
        $requestParams = $this->getRequestParams();

        try
        {
            $response = Requests::request(
                $requestParams['url'],
                $requestParams['headers'],
                $requestParams['data'],
                $requestParams['method'],
                $requestParams['options']);

            $res = $this->parseAndReturnResponse($response);

            return ApiResponse::json($res);
        }
        catch (\Throwable $e)
        {
            throw new Exception\ServerErrorException('Error completing the request', ErrorCode::SERVER_ERROR_SPLITZ_FAILURE, null, $e);
        }
    }

    protected function parseAndReturnResponse($res)
    {
        $code = $res->status_code;

        $res = json_decode($res->body, true);

        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw new Exception\RuntimeException('Malformed json response');
        }

        if ($code >= 400)
        {
            $this->trace->error(TraceCode::SPLITZ_REQUEST_FAILED, [
                'status_code' => $code,
                'response' => $res
            ]);
        }

        $splitzResponse = ['status_code' => $code, 'response' => $res];

        return $splitzResponse;
    }

    protected function getRequestParams()
    {
        $path = $this->validateAndGetServicePathParam();

        $url = $this->baseUrl . $path;

        $method = Request::method();

        $headers = [];

        $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);
        
        $parameters = Request::all();

        unset($parameters['service_path']);

        $parameters = json_encode($parameters);

        $headers['Content-Type'] = self::CONTENT_TYPE_JSON;
        $headers[SplitzService::X_PASSPORT_JWT_V1] = $this->ba->getPassportJwt($this->baseUrl);
        $headers[SplitzService::X_USER_EMAIL] = $this->ba->getAdmin()->getEmail();

        $options = [
            'timeout' => $this->requestTimeout,
        ];

        $this->trace->info(TraceCode::SPLITZ_REQUEST, ['url' => $url, 'parameters' => $parameters, 'options' => $options]);

        return [
            'url'     => $url,
            'headers' => $headers,
            'data'    => $parameters,
            'options' => $options,
            'method'  => $method,
        ];
    }

    protected function validateAndGetServicePathParam(): string
    {
        $path = Request::get('service_path');

        if (empty($path) === true)
        {
            throw new Exception\BadRequestValidationFailureException('Valid path parameter required');
        }

        return $path;
    }
}




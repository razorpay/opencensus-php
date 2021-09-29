<?php

namespace RZP\Services;

use App;
use Throwable;
use ApiResponse;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Requests;

class SplitzService extends Base\Service
{
    const CONTENT_TYPE_JSON = 'application/json';

    const EVALUATE_URL              = 'twirp/rzp.splitz.evaluate.v1.EvaluateAPI/Evaluate';
    const CREATE_SEGMENT_URL        = '/twirp/rzp.splitz.segment.v1.SegmentAPI/Create';
    const UPDATE_SEGMENT_URL        = '/twirp/rzp.splitz.segment.v1.SegmentAPI/Replace';
    const GET_SEGMENT_FROM_NAME_URL = 'twirp/rzp.splitz.segment.v1.SegmentAPI/GetByName';
    const FALSE_POSITIVITY_RATE     = "0.00001";
    const EVALUATE_BULK_URL         = 'twirp/rzp.splitz.evaluate.v1.EvaluateAPI/EvaluateBulk';

    // Tells the client what the content type of the returned content actually is
    const CONTENT_TYPE = 'Content-Type';

    // Specifies the method or methods allowed when accessing the resource in response to a preflight request.
    const ACCESS_CONTROL_ALLOW_METHODS = 'Access-Control-Allow-Methods';

    // Used in response to a preflight request which includes the Access-Control-Request-Headers to indicate which HTTP headers can be used during the actual request.
    const ACCESS_CONTROL_ALLOW_HEADERS = 'Access-Control-Allow-Headers';

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

    protected $trace;

    protected $env;

    public function __construct()
    {
        $app                  = App::getFacadeRoot();
        $this->trace          = $app['trace'];
        $this->env            = $app['env'];
        $splitzConfig         = $app['config']['applications.splitz'];
        $this->baseUrl        = $splitzConfig['url'];
        $this->key            = $splitzConfig['username'];
        $this->secret         = $splitzConfig['secret'];
        $this->requestTimeout = $splitzConfig['request_timeout'];
    }

    public function createSegment($preSignedUrl, $segmentName)
    {
        $parameters = $this->getParametersForCreateSegment($preSignedUrl, $segmentName);

        return $this->sendRequest($parameters, self::CREATE_SEGMENT_URL, Requests::POST);
    }

    private function getParametersForCreateSegment($presignedUrl, $segmentName): array
    {
        return [
            'segment' => [
                'name'                => $segmentName,
                'description'         => $segmentName,
                'signedUrl'           => $presignedUrl,
                'falsePositivityRate' => static::FALSE_POSITIVITY_RATE
            ]
        ];
    }

    public function sendRequest($parameters, $path, $method)
    {
        $requestParams = $this->getRequestParams($parameters, $path, $method);

        try
        {
            $response = Requests::request(
                $requestParams['url'],
                $requestParams['headers'],
                $requestParams['data'],
                $requestParams['method'],
                $requestParams['options']);

            return $this->parseAndReturnResponse($response);
        }
        catch (Throwable $e)
        {
            throw new Exception\ServerErrorException('Error completing the request', ErrorCode::SERVER_ERROR_SPLITZ_FAILURE, null, $e);
        }
    }

    protected function getRequestParams($parameters, $path, $method)
    {
        $url = $this->baseUrl . $path;

        $headers = [];

        $parameters = json_encode($parameters);

        $headers['Content-Type'] = self::CONTENT_TYPE_JSON;

        $options = [
            'timeout' => $this->requestTimeout,
            'auth'    => [$this->key, $this->secret],
        ];

        $this->trace->info(TraceCode::SPLITZ_REQUEST, ['url' => $url, 'parameters' => $parameters, 'headers' => $headers]);

        return [
            'url'     => $url,
            'headers' => $headers,
            'data'    => $parameters,
            'options' => $options,
            'method'  => $method,
        ];
    }

    protected function parseAndReturnResponse($res)
    {
        $code = $res->status_code;

        $res = json_decode($res->body, true);

        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw new Exception\RuntimeException('Malformed json response');
        }

        $splitzResponse = ['status_code' => $code, 'response' => $res];

        return $splitzResponse;
    }

    public function updateSegment($preSignedUrl, $segmentName, $id)
    {
        $parameters = $this->getParametersForUpdateSegment($preSignedUrl, $segmentName, $id);

        return $this->sendRequest($parameters, self::UPDATE_SEGMENT_URL, Requests::POST);
    }

    private function getParametersForUpdateSegment($presignedUrl, $segmentName, $id): array
    {
        return [
            'segment' => [
                'id'                  => $id,
                'name'                => $segmentName,
                'description'         => $segmentName,
                'signedUrl'           => $presignedUrl,
                'falsePositivityRate' => static::FALSE_POSITIVITY_RATE
            ]
        ];
    }

    public function evaluateRequest($input)
    {
        return $this->sendRequest($input, self::EVALUATE_URL, Requests::POST);
    }

    public function getSegmentFromName($segmentName)
    {
        $parameters = $this->getParametersForGetSegmentByName($segmentName);

        return $this->sendRequest($parameters, self::GET_SEGMENT_FROM_NAME_URL, Requests::POST);
    }

    private function getParametersForGetSegmentByName($segmentName): array
    {
        return [
            'segmentName' => $segmentName
        ];
    }

    public function bulkCallsToSplitz($input)
    {
        $headers['Content-Type'] = self::CONTENT_TYPE_JSON;

        $options = [
            'timeout' => $this->requestTimeout,
            'auth'    => [$this->key, $this->secret],
        ];

        $url = $this->baseUrl . self::EVALUATE_BULK_URL;

        try
        {
            $response = Requests::request(
                $url,
                $headers,
                $input,
                Requests::POST,
                $options);

            return $this->parseAndReturnResponse($response);
        }
        catch (\Throwable $e)
        {
            throw new Exception\ServerErrorException('Error completing the request', ErrorCode::SERVER_ERROR_SPLITZ_BULK_FAILURE, null, $e);
        }
    }

    public function allowCors()
    {
        $response = ApiResponse::json([]);

        $response->headers->set(self::ACCESS_CONTROL_ALLOW_METHODS, 'POST, OPTIONS' );

        $response->headers->set(self::ACCESS_CONTROL_ALLOW_HEADERS, self::CONTENT_TYPE);

        return $response;
    }
}

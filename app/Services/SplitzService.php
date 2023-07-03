<?php

namespace RZP\Services;

use App;
use Request;
use Throwable;
use ApiResponse;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use RZP\Http\Request\Requests;
use RZP\Http\BasicAuth\BasicAuth;

class SplitzService extends Base\Service
{
    const CONTENT_TYPE_JSON = 'application/json';

    const X_PASSPORT_JWT_V1 = 'X-Passport-JWT-V1';
    const X_USER_EMAIL      = 'X-User-Email';

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

    const METRIC_SPLITZ_REQUEST_DURATION_MILLISECS = 'splitz_request_duration_milli_seconds.histogram';

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

    /**
     * @var BasicAuth
     */
    protected $ba;

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
        $this->evalRequestTimeout = $splitzConfig['evaluate_request_timeout'];
        $this->bulkEvalRequestTimeout = $splitzConfig['bulk_evaluate_request_timeout'];
        $this->ba             = app('basicauth');
    }

    public function createSegment($preSignedUrl, $segmentName, $s3Path)
    {
        $parameters = $this->getParametersForCreateSegment($preSignedUrl, $segmentName, $s3Path);

        return $this->sendRequest($parameters, self::CREATE_SEGMENT_URL, Requests::POST);
    }

    private function getParametersForCreateSegment($presignedUrl, $segmentName, $s3Path): array
    {
        return [
            'segment' => [
                'name'                => $segmentName,
                'description'         => $segmentName,
                'signedUrl'           => $presignedUrl,
                's3Path'              => $s3Path,
                'falsePositivityRate' => static::FALSE_POSITIVITY_RATE
            ]
        ];
    }

    public function sendRequest($parameters, $path, $method)
    {
        $requestParams = $this->getRequestParams($parameters, $path, $method);

        try
        {
            $reqStartAt = millitime();
            $response = Requests::request(
                $requestParams['url'],
                $requestParams['headers'],
                $requestParams['data'],
                $requestParams['method'],
                $requestParams['options']);

            $dimensions = [
                "path"                       => $path,
            ];
            $this->trace->histogram(self::METRIC_SPLITZ_REQUEST_DURATION_MILLISECS, millitime() - $reqStartAt, $dimensions);
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

        $timeout = $this->requestTimeout;
        if (self::EVALUATE_URL == $path) {
            $timeout = $this->evalRequestTimeout;
        } elseif (self::EVALUATE_BULK_URL == $path) {
            $timeout = $this->bulkEvalRequestTimeout;
        }

        // send passport if not evaluate route
        if (($path != self::EVALUATE_URL && $path != self::EVALUATE_BULK_URL) && empty($this->ba) === false)
        {
            $headers[self::X_PASSPORT_JWT_V1] = $this->ba->getPassportJwt($this->baseUrl);
        }

        $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);

        $options = [
            'timeout' => $timeout,
            'auth'    => [$this->key, $this->secret],
        ];

        $this->trace->info(TraceCode::SPLITZ_REQUEST, ['url' => $url, 'parameters' => $parameters]);

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

    public function updateSegment($preSignedUrl, $segmentName, $segment, $s3Path)
    {
        $parameters = $this->getParametersForUpdateSegment($preSignedUrl, $segmentName, $segment, $s3Path);

        return $this->sendRequest($parameters, self::UPDATE_SEGMENT_URL, Requests::POST);
    }

    private function getParametersForUpdateSegment($presignedUrl, $segmentName, $segment, $s3Path): array
    {

        $segmentParams = [
            'id'                  => $segment['id'],
            'name'                => $segmentName,
            'description'         => $segmentName,
            'signedUrl'           => $presignedUrl,
            's3Path'              => $s3Path,
            'falsePositivityRate' => static::FALSE_POSITIVITY_RATE
        ];

        if(isset($segment['source_type']) && $segment['source_type']==='SQL'){
            $segmentParams['source_type']      = $segment['source_type'];
            $segmentParams['cron_expression']  = $segment['cron_expression'];
            $segmentParams['sql_query']        = $segment['sql_query'];
        }

        return[
            'segment' => $segmentParams
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

    public function bulkCallsToSplitz($parameters)
    {
        $response = [];

        if (empty($parameters) === false)
        {
            $chunkExperimentArray = array_chunk($parameters, 10);

            foreach ($chunkExperimentArray as $batchExperimentArray)
            {
                $bulk_evaluate = ['bulk_evaluate' => $batchExperimentArray];

                $result = $this->sendRequest($bulk_evaluate, self::EVALUATE_BULK_URL, Requests::POST);

                if (isset($result['response']['bulk_evaluate_response']) == true)
                {
                    $response = array_merge($response, $result['response']['bulk_evaluate_response']);
                }
            }
        }

        return $response;
    }

    public function allowCors()
    {
        $response = ApiResponse::json([]);

        $response->headers->set(self::ACCESS_CONTROL_ALLOW_METHODS, 'POST, OPTIONS' );

        $response->headers->set(self::ACCESS_CONTROL_ALLOW_HEADERS, self::CONTENT_TYPE);

        return $response;
    }
}

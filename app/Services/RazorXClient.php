<?php

namespace RZP\Services;

use Cache;
use Request;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Http\RequestHeader;
use RZP\Http\Request\Requests;

class RazorXClient
{
    const EVALUATE_URI      = 'evaluate';
    const EVALUATE_URI_BULK = 'evaluateBulk';

    // Params required for evaluator API
    const ID                = 'id';
    const FEATURE_FLAG      = 'feature_flag';
    const ENVIRONMENT       = 'environment';
    const MODE              = 'mode';

    const CONTENT_TYPE_JSON = 'application/json';

    /**
     * The cookie of razorx contains the variants that are fetched. Now, if we
     * want microservices to run on the same variant, we can send a cookie with the
     * variants. And if the same RazorXClient is used in the microservice, the
     * the variant will be picked up from the cookie. Razorx cookie will be mapped
     * to a json which can contain multiple variants.
     */
    const RAZORX_COOKIE_KEY = 'razorx';

    /**
     * The default case to be returned so that the old flow is taken
     * when the featureFlag is not to be applied to merchant or the
     * response from RazorX server is not return for some reason
     */
    const DEFAULT_CASE      = 'control';

    const CACHED_TREATMENT_PREFIX = "razorx:";
    const CACHED_TREATMENT_TTL = 1; // In minutes.

    const RETRY_COUNT_KEY = 'retry_count';

    const METRIC_RAZORX_REQUEST_DURATION_SECS = 'razorx_request_duration_seconds.histogram';

    protected $baseUrl;

    /**
     * API talks to RazorX's APIs using HTTP Basic authentication.
     * Following are those user name and pass.
     */
    protected $key;

    protected $secret;

    protected $config;

    protected $trace;

    protected $env;

    protected $requestTimeout;

    protected $requestTimeoutBulk;

    public static $whiteListedCardPs = [
        'card_payments_gateway_routing_hdfc',
        'card_payments_gateway_routing_hitachi',
        'card_payments_gateway_routing_paysecure',
        'card_payments_gateway_routing_card_fss',
        'card_payments_gateway_routing_hitachi_mpi_blade',
        'card_payments_gateway_routing_axis_migs_mpi_blade',
        'card_payments_gateway_routing_axis_migs',
        'card_payments_gateway_routing_mpgs',
        'card_payments_gateway_routing_first_data',
        'card_payments_gateway_routing_first_data_mpi_blade',
        'card_payments_gateway_routing_amex',
    ];

    /**
     * @var string
     * localUniqueId will be a combination of the id, feature_flag and the mode.
     */
    protected $localUniqueId;

    /**
     * It will map the localUniqueId to a treatment. So, in the same request
     * we will store the treatment against the localUniqueId. So for further request
     * for exactly same razorx request, we return from the stored value.
     */
    protected $localUniqueIdToTreatment = [];

    public function __construct($app)
    {
        $this->trace              = $app['trace'];
        $this->config             = $app['config']->get('applications.razorx');
        $this->baseUrl            = $this->config['url'];
        $this->key                = $this->config['username'];
        $this->secret             = $this->config['secret'];
        $this->env                = $app['env'];
        $this->requestTimeout     = $this->config['request_timeout'];
        $this->requestTimeoutBulk = $this->config['request_timeout_bulk'];
    }

    /**
     * Caches response of getTreatment() for given arguments in redis.
     * @param  array  $args
     * @return string
     */
    public function getCachedTreatment(...$args): string
    {
        // Case- From within same http request scope.
        $this->localUniqueId = self::getLocalUniqueId(...$args);
        if (($storedVariant = $this->getStoredVariant()) !== null)
        {
            return $storedVariant;
        }

        // Todo: Ensure some approach to invalidate cache on feature/experiment
        // in raxorx side because with big merchants we can not live with delay.
        // Case- Between different http request scope.
        try
        {
            $treatment = Cache::remember(
                self::CACHED_TREATMENT_PREFIX.implode(':', $args),
                self::CACHED_TREATMENT_TTL,
                function () use ($args) {
                    return $this->getTreatment(...$args);
                }
            );
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);
            $treatment = $this->getTreatment(...$args);
        }

        return $treatment;
    }

    public function getTreatment(string $id, string $featureFlag, string $mode, $retryCount = 0, $requestOptions = []): string
    {
        $variant = $this->checkWhitelistedExperimentsForCardPs($featureFlag);

        if ($variant !== null)
        {
            return $variant;
        }

        $this->localUniqueId = self::getLocalUniqueId($id, $featureFlag, $mode);

        $storedVariant = $this->getStoredVariant();

        if ($storedVariant !== null)
        {
            return $storedVariant;
        }

        $this->setVariantFromCookie($id, $featureFlag, $mode);

        $storedVariant = $this->getStoredVariant();

        if ($storedVariant !== null)
        {
            return $storedVariant;
        }

        return $this->getVariantFromRazorXService($id, $featureFlag, $mode, $retryCount, $requestOptions);
    }

    protected function checkWhitelistedExperimentsForCardPs($feature)
    {
        if (($this->env !== 'func') and
            ($this->env !== 'automation') and
            ($this->env !== 'bvt') and
            ($this->env !== 'availability'))
        {
            return null;
        }

        if (in_array($feature, self::$whiteListedCardPs, false) === false)
        {
            return null;
        }

        return 'cardps';
    }

    /**
     * Razorx cookie will be mapped to a json which can contain multiple variants.
     * Within the json, the string returned from this method should be used to store
     * the variant.
    */
    public static function getLocalUniqueId(string $id, string $featureFlag, string $mode): string
    {
        $localUniqueId = 'I:' . $id . '_F:' . $featureFlag . '_M:' . $mode;
        return $localUniqueId;
    }

    /**
     * Razorx cookie will be mapped to a json which can contain multiple variants.
     * This is a helper method to construct that json string.
     * Recommended: Construct localUniqueId using the helper function.
     */
    public static function appendVariantToCurrRazorxCookieValue(string $localUniqueId, string $variant, string $currRazorxCookieValue = null): string
    {
        if ($currRazorxCookieValue === null)
        {
            $currRazorxCookieValue = '{}';
        }

        $currCookieArr = json_decode($currRazorxCookieValue, true);

        if (is_null($currCookieArr))
        {
            return '';
        }

        $currCookieArr[$localUniqueId] = $variant;

        return json_encode($currCookieArr);
    }

    protected function getVariantFromRazorXService(
                                    string $id,
                                    string $featureFlag,
                                    string $mode,
                                    int $retryCount = 0, array $requestOptions = [])
    {
        $data = [
            self::ID               => $id,
            self::FEATURE_FLAG     => $featureFlag,
            self::ENVIRONMENT      => $this->env,
            self::MODE             => $mode,
            self::RETRY_COUNT_KEY  => $retryCount,
        ];

        $variant = $this->sendRequest(self::EVALUATE_URI, Requests::GET, $data, $requestOptions);

        $this->storeVariant($variant);

        return $variant;
    }

    /**
     *  Response from Razorx Bulk is in this format.
     *   [
            {
                "id": "12345",
                "feature_flag": "random_feature",
                "environment": "beta",
                "mode": "test",
                "result": "on"
              },
            {
                "id": "123435",
                "feature_flag": "random_feature",
                "environment": "prod",
                "mode": "test",
                "result": "control"
            }
        ]
     *
     * @param string $id
     * @param array  $featureFlagBatch
     * @param string $mode
     *
     * @return array
     */
    public function getTreatmentBulk(string $id, array $featureFlagBatch, string $mode): array
    {
        $payload = $this->getPayloadForBulk($id, $featureFlagBatch, $mode);

        return $this->sendRequestBulk(self::EVALUATE_URI_BULK, Requests::POST, $payload);
    }

    protected function getPayloadForBulk(string $id, array $featureFlagBatch, string $mode): array
    {
        $payload = [];

        foreach ($featureFlagBatch as $feature)
        {
            $request = [
                self::MODE         => $mode,
                self::ID           => $id,
                self::FEATURE_FLAG => $feature,
                self::ENVIRONMENT  => $this->env,
            ];

            array_push($payload, $request);
        }

        return $payload;
    }

    protected function setVariantFromCookie(string $id, string $featureFlag, string $mode)
    {
        $variant = Request::cookie(self::RAZORX_COOKIE_KEY);

        // Check headers if not found in cookie.
        if (empty($variant) === false)
        {
            $variantArray = json_decode($variant, true);

            $variantResult = $variantArray[$this->localUniqueId] ?? null;

            if (empty($variantResult) === false)
            {
                $this->storeVariant($variantResult);
            }
        }
    }

    protected function sendRequestBulk(string $url, string $method, array $data): array
    {
        if ($this->config['mock'] === true)
        {
            return $this->defaultBulkResponse($data);
        }

        $request = $this->getRequestParams($url, $method, $data);

        $request['options']['connect_timeout'] = $this->requestTimeoutBulk;
        $request['options']['timeout']         = $this->requestTimeoutBulk;
        $request['headers']['Content-Type']    = self::CONTENT_TYPE_JSON;
        $request['content']                    = json_encode($request['content']);

        try
        {
            $response = Requests::request($request['url'], $request['headers'], $request['content'], $request['method'], $request['options']);

            $code = $response->status_code;

            if ($code === 200)
            {
                $response = json_decode($response->body, true);

                return $response;
            }
            else
            {
                unset($request['options']['auth']);

                $this->trace->error(TraceCode::RAZORX_BULK_REQUEST_FAILED, [
                    'request'  => $request,
                    'response' => json_decode($response->body, true),
                ]);

                $this->trace->count(RazorXClientMetric::RAZORX_BULK_REQUEST_FAILED_TOTAL);

                return $this->defaultBulkResponse($data);
            }
        }
        catch (\Throwable $e)
        {
            unset($request['options']['auth']);

            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::RAZORX_BULK_REQUEST_EXCEPTION,
                [
                    'request' => $request,
                ]);

            $this->trace->count(RazorXClientMetric::RAZORX_BULK_REQUEST_EXCEPTION_TOTAL);

            return $this->defaultBulkResponse($data);
        }
    }

    protected function defaultBulkResponse(array $data): array
    {
        return array_map(function($row) {

            $row ['result'] = self::DEFAULT_CASE;

            return $row;
        }, $data);
    }

    protected function sendRequest(
        string $url,
        string $method,
        array $data = [], array $requestOptions = [])
    {
        if ($this->config['mock'] === true)
        {
            return self::DEFAULT_CASE;
        }

        $request = $this->getRequestParams($url, $method, $data, $requestOptions);

        $retryCount = $data[self::RETRY_COUNT_KEY] ?? 0;

        return $this->makeRequestAndGetResponse($request, $retryCount, $retryCount);
    }

    protected function makeRequestAndGetResponse(array $request, int $retryOriginalCount, int $retryCount)
    {
        try
        {
            $reqStartAt = microtime(true);

            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['method'],
                $request['options']);

            $this->trace->histogram(self::METRIC_RAZORX_REQUEST_DURATION_SECS, microtime(true) - $reqStartAt);

            return $this->parseAndReturnResponse($response, $request);
        }
        catch (\Throwable $e)
        {
            if (($e instanceof \WpOrg\Requests\Exception) and
                ($this->checkRequestTimeout($e) === true) and
                ($retryCount > 0))
            {
                $this->trace->info(
                    TraceCode::RAZORX_SERVICE_RETRY,
                    [
                        'message'       => $e->getMessage(),
                        'data'          => $e->getData(),
                    ]);

                $retryCount--;

                return  $this->makeRequestAndGetResponse($request, $retryOriginalCount, $retryCount);
            }
            {
                unset($request['options']['auth']);

                $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::RAZORX_REQUEST_FAILED,
                    [
                        'request'       => $request,
                        'retries'       => $retryOriginalCount - $retryCount
                    ]);

                return self::DEFAULT_CASE;
            }
        }
    }

    protected function parseAndReturnResponse($res, $req = null)
    {
        $code = $res->status_code;

        if ($code === 200)
        {
            $response = json_decode($res->body, true);

            return $response['value'] ?? self::DEFAULT_CASE;
        }
        else
        {
            unset($req['options']['auth']);

            $this->trace->error(TraceCode::RAZORX_REQUEST_FAILED, [
                'request'   => $req,
                'response'  => json_decode($res->body, true),
            ]);
        }

        return self::DEFAULT_CASE;
    }

    protected function getRequestParams(
        string $url,
        string $method,
        array $data = [], array $requestOptions = []): array
    {
        $url = $this->baseUrl . $url;

        if (empty($data) === true)
        {
            $data = '';
        }

        $headers = [];
        $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);

        $options = [
            'connect_timeout' => $this->requestTimeout,
            'timeout' => $this->requestTimeout,
            'auth'    => [$this->key, $this->secret],
        ];
        $options = array_merge($options, $requestOptions);

        return [
            'url'     => $url,
            'method'  => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $data,
        ];
    }

    protected function storeVariant($variant)
    {
        $this->localUniqueIdToTreatment[$this->localUniqueId] = $variant;
    }

    protected function getStoredVariant()
    {
        if (array_key_exists($this->localUniqueId, $this->localUniqueIdToTreatment) === true)
        {
            return $this->localUniqueIdToTreatment[$this->localUniqueId];
        }

        return null;
    }

    /**
     * Checks whether the requests exception that we caught
     * is actually because of timeout in the network call.
     *
     * @param Requests_Exception $e The caught requests exception
     *
     * @return boolean              true/false
     */
    protected function checkRequestTimeout(\WpOrg\Requests\Exception $e)
    {
        if ($e->getType() === 'curlerror')
        {
            $curlErrNo = curl_errno($e->getData());

            if ($curlErrNo === 28)
            {
                return true;
            }
        }
        return false;
    }
}

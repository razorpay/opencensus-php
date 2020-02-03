<?php

namespace RZP\Services;

use Cache;
use Request;
use Requests;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class RazorXClient
{
    const REQUEST_TIMEOUT   = 1; // In seconds

    const EVALUATE_URI      = 'evaluate';

    // Params required for evaluator API
    const ID                = 'id';
    const FEATURE_FLAG      = 'feature_flag';
    const ENVIRONMENT       = 'environment';
    const MODE              = 'mode';

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
        $this->trace   = $app['trace'];
        $this->config  = $app['config']->get('applications.razorx');
        $this->baseUrl = $this->config['url'];
        $this->key     = $this->config['username'];
        $this->secret  = $this->config['secret'];
        $this->env     = $app['env'];
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

    public function getTreatment(string $id, string $featureFlag, string $mode): string
    {
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

        return $this->getVariantFromRazorXService($id, $featureFlag, $mode);
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

    protected function getVariantFromRazorXService(string $id, string $featureFlag, string $mode)
    {
        $data = [
            self::ID           => $id,
            self::FEATURE_FLAG => $featureFlag,
            self::ENVIRONMENT  => $this->env,
            self::MODE         => $mode
        ];

        $variant = $this->sendRequest(self::EVALUATE_URI, Requests::GET, $data);

        $this->storeVariant($variant);

        return $variant;
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

    protected function sendRequest(
        string $url,
        string $method,
        array $data = [])
    {
        if ($this->config['mock'] === true)
        {
            return self::DEFAULT_CASE;
        }

        $request = $this->getRequestParams($url, $method, $data);

        try
        {
            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['method'],
                $request['options']);

            return $this->parseAndReturnResponse($response, $request);
        }
        catch(\Throwable $e)
        {
            unset($request['options']['auth']);

            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::RAZORX_REQUEST_FAILED,
                [
                    'request'   => $request,
                ]);

            return self::DEFAULT_CASE;
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
        array $data = []): array
    {
        $url = $this->baseUrl . $url;

        if (empty($data) === true)
        {
            $data = '';
        }

        $headers = [];

        $options = [
            'connect_timeout' => self::REQUEST_TIMEOUT,
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [$this->key, $this->secret],
        ];

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
}

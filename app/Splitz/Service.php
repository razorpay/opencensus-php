<?php

namespace App\Splitz;

use App\Base;
use App\Http\ApiUrl;
use App\Trace\TraceCode;
use App\User\Constants;
use App\Metrics\Constants as MetricsConstants;
use App\Admin\ApiRequestAny;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Promise\PromiseInterface;
use App\Constants\Constants as AppConstants;

class Service extends Base\Service
{
    protected $trace;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Store
     */
    protected $cache;

    /**
     * @var \GuzzleHttp\Client|null
     */
    private ?Guzzle $httpClient;

    public function __construct(array $options = [])
    {
        $app = \App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->metrics = $app['metrics'];

        $this->cache = $app['cache'];

        // redis cache timeout for 1 day (1440 minutes)
        $this->cacheTimeout = 2 * 12 * 60;

        $this->httpClient = array_get($options, AppConstants::HTTP_CLIENT);
    }

    public function getSplitzVariantBulk($merchantId, $isSplitzCachingEnabled = false): array
    {
        $clientType = ['client_type' => 'merchant'];

        $url = 'splitz/bulkEvaluateProxy';

        return $this->getVariantBulk($merchantId, config('splitz.experiments'), $clientType, $url, [], $isSplitzCachingEnabled);
    }

    public function generateCacheKey($merchantId, $input)
    {
        $encrypedInput = hash('sha256', json_encode($input));

        $sanitizedMerchantId = trim($merchantId);

        $cacheKey = "splitz_cache_" . $sanitizedMerchantId . "_" . $encrypedInput;

        return $cacheKey;
    }

    public function getKeysByPattern($pattern) {
        $keys = [];

        $cursor = null;

        $patternWithPrefix = Constants::REDIS_CACHE_PREFIX . $pattern;

        do {
            [$cursor, $batch] = Redis::scan($cursor, 'MATCH', $patternWithPrefix);

            $keys = array_merge($keys, $batch);
        } while ($cursor !== '0');

        return $keys;
    }

    public function forgotCacheKeys($keysToClear)
    {
        foreach ($keysToClear as $key) {
            $cacheKey = explode(Constants::REDIS_CACHE_PREFIX, $key);
            if (count($cacheKey) > 1 && !empty($cacheKey[1]))
            {
                $this->cache->forget($cacheKey[1]);
            }
        }
    }

    public function clearSplitzCacheMerchantLevel($merchantIds)
    {
        $keysToClear = [];

        foreach ($merchantIds as $merchantId) {
            $pattern = "splitz_cache_" . trim($merchantId) . "_*";
            $cacheKeys = $this->getKeysByPattern($pattern);
            $keysToClear = array_merge($keysToClear, $cacheKeys);
        }

        $this->forgotCacheKeys($keysToClear);

        return [
            'success' => true
        ];
    }

    public function clearAllSplitzCache()
    {
        $keysToClear = $this->getKeysByPattern('splitz_cache_*');

        $this->forgotCacheKeys($keysToClear);

        return [
            'success' => true
        ];
    }

    public function handleClearCachingForMerchants($input)
    {
        if(isset($input['merchants']) && count($input['merchants']) > 0){
            return $this->clearSplitzCacheMerchantLevel($input['merchants']);
        }
        return [
            'success'=> false,
            'message'=> "No merchant id's found in payload"
        ];
    }

    public function clearSplitzCache($input)
    {
        if($input['type'] === 'merchant') {
            return $this->handleClearCachingForMerchants($input);
        }

        if($input['type'] === 'all') {
            return $this->clearAllSplitzCache();
        }

        return [
            'success'=> false,
            'message'=> 'Invalid cache input type'
        ];
    }

    public function getCacheByKey($cacheKey)
    {
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }
        return false;
    }

    public function setCacheByKey($cacheKey, $responseData)
    {
        // Store the API response in Redis for 1 day (86400 seconds) ONLY if it's not already cached
        $this->cache->put($cacheKey, $responseData, $this->cacheTimeout);
    }

    public function getCacheByIdAsyncPromise($merchantId)
    {
        $input = $this->getSplitzApiPayload($merchantId, config('splitz.experiments'), []);

        $cacheKey = $this->generateCacheKey($merchantId, $input);

        $cachedResponse = $this->getCacheByKey($cacheKey);
        // If cached response exists, return it
        if($cachedResponse){
            $this->pushMetrics(Constants::HITS, $cacheKey);

            return $cachedResponse;
        }
        $this->pushMetrics(Constants::MISS, $cacheKey);

        return false;
    }

    public function setCacheByIdAsyncPromise($merchantId, $responseData)
    {
        $input = $this->getSplitzApiPayload($merchantId, config('splitz.experiments'), []);

        $cacheKey = $this->generateCacheKey($merchantId, $input);

        $this->setCacheByKey($cacheKey, $responseData);
    }

    /**
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    public function getSplitzVariantBulkAsyncPromise($merchantId): ?PromiseInterface
    {
        $clientType = ['client_type' => 'merchant', AppConstants::HTTP_CLIENT => $this->httpClient];

        return $this->getVariantBulkAsyncPromise($merchantId, config('splitz.experiments'), $clientType);
    }

    public function getSplitzApiPayload($merchantId, $experimentIds, $optionalRequestData = [])
    {

        $requestData = ['mid' => $merchantId];

        $requestData = array_merge($requestData, $optionalRequestData);

        $input = [];

        foreach ($experimentIds as $experimentId)
        {
            $experimentInput = [
                'id'            => $merchantId,
                'experiment_id' => $experimentId,
                'request_data'  => json_encode($requestData, true)
            ];

            array_push($input, $experimentInput);
        }

        return $input;
    }

    /**
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    public function getVariantBulkAsyncPromise($merchantId, $experimentIds, $clientType = ['client_type' => 'merchant'], $url = 'splitz/bulkEvaluateProxy', $optionalRequestData = []): ?PromiseInterface
    {
        if (empty($experimentIds) === true)
        {
            return null;
        }

        $request = new ApiRequestAny($clientType);

        $input = $this->getSplitzApiPayload($merchantId, $experimentIds, $optionalRequestData);

        return $request->processInput($input)->sendAsyncPromise($url, 'POST');
    }

    public function processVariantBulkAsyncPromiseResponse($apiSplitzPromise)
    {
        list($error, $data) = $apiSplitzPromise->processAsyncPromiseResponse();

        if (empty($error) === false)
        {
            $this->trace->info(TraceCode::SPLITZ_BULK_EVALUATE_FAILED, ["error" => $error]);

            return [];
        }

        $responseData = [];

        foreach ($data as $output)
        {
            if (isset($output['experiment']['id']) === true)
            {
                $experimentFeatureFlag = $output['experiment']['id'];
                $responseData[$experimentFeatureFlag] = [];

                if (isset($output['variant']) === true)
                {
                    $responseData[$experimentFeatureFlag] = $this->transformVariablesFromVariantIfExist($output['variant']);
                }
            }
        }

        return $responseData;
    }

    public function getVariantBulk($merchantId, $experimentIds, $clientType = ['client_type' => 'merchant'], $url = 'splitz/bulkEvaluateProxy', $optionalRequestData = [], $isSplitzCachingEnabled = false)
    {
        $startTime = microtime(true) * 1000;

        $this->trace->info(TraceCode::GET_SPLITZ_EXPERIMENTS_ROUTE_INFO, [
            'action'                => 'FetchStarted',
            'start_time'            => $startTime
        ]);

        $responseData = [];

        if (empty($experimentIds) === true)
        {
            return [];
        }

        $request = new ApiRequestAny($clientType);

        $input = $this->getSplitzApiPayload($merchantId, $experimentIds, $optionalRequestData);

        // Define a cache key based on input data and merchantId
        $cacheKey = $this->generateCacheKey($merchantId, $input);

        if ($isSplitzCachingEnabled)
        {
            $cachedResponse = $this->getCacheByKey($cacheKey);
            // If cached response exists, return it
            if($cachedResponse)
            {
                $this->pushMetrics(Constants::HITS, $cacheKey, $url);

                return $cachedResponse;
            }
            $this->pushMetrics(Constants::MISS, $cacheKey, $url);
        }

        list($error, $data) = $request->processInput($input)->send($url, 'POST');

        if (empty($error) === false)
        {
            $this->trace->info(TraceCode::SPLITZ_BULK_EVALUATE_FAILED, ["error" => $error]);

            return [];
        }

        foreach ($data as $output)
        {
            if (isset($output['experiment']['id']) === true)
            {
                $experimentFeatureFlag = $output['experiment']['id'];

                if (isset($output['variant']) === true)
                {
                    $responseData[$experimentFeatureFlag] = $this->transformVariablesFromVariantIfExist($output['variant']);
                }
                else
                {
                    $responseData[$experimentFeatureFlag] = [];
                }
            }
        }

        $endTime  = microtime(true) * 1000;
        $duration = round($endTime - $startTime);

        $this->trace->info(TraceCode::GET_SPLITZ_EXPERIMENTS_ROUTE_INFO, [
            'action'              => 'FetchEnded',
            'end_time'            => $endTime,
            'duration'            => $duration,
            'controller'          => app('request')->route()->getAction()['controller']

        ]);

        if($isSplitzCachingEnabled){
            $this->setCacheByKey($cacheKey, $responseData);
        }

        return $responseData;
    }

    public function getSplitzVariant($merchantId): array
    {
        $data = [];

        foreach (config('splitz.experiments') as $key => $experimentFeatureFlag)
        {
            $variant = $this->getVariant($experimentFeatureFlag, $merchantId);

            $data[$experimentFeatureFlag] = $this->transformVariablesFromVariantIfExist($variant);
        }

        return $data;
    }

    public function getVariant($experimentId, $merchantId)
    {
        if (empty($experimentId) === true)
        {
            return [];
        }

        $request = new ApiRequestAny();

        $requestData = ['mid' => $merchantId];

        $input = [
            'id'            => $merchantId,
            'experiment_id' => $experimentId,
            'request_data'  => json_encode($requestData, true)
        ];

        list($error, $data) = $request->processInput($input)->send("splitz/evaluate", 'POST');

        if (empty($error) === false)
        {
            $this->trace->info(TraceCode::SPLITZ_EVALUATE_FAILED, ["error" => $error]);

            return [];
        }

        if (isset($data['response']['variant']) === true)
        {
            return $data['response']['variant'];
        }

        // return empty response if not found.
        return [];
    }

    public function transformVariablesFromVariantIfExist($variant)
    {
        $variableTrans = [];

        if (isset($variant['variables']) === true)
        {
            $variables = $variant['variables'];

            foreach ($variables as $variable)
            {
                $key = $variable['key'] ?? null;
                $value = $variable['value'] ?? null;

                if (empty($key) === false && empty($value) === false)
                {
                    $variableTrans[$key] = $value;
                }
            }

            $variant['variables'] = $variableTrans;
        }

        return $variant;
    }

    public function pushMetrics($cacheType, $cacheKey, $route = 'splitz/bulkEvaluateProxy'){

        $dimensions = [
            Constants::CACHE_KEY                                    =>        $cacheKey,
            Constants::ROUTE_NAME                                   =>        $route,
            MetricsConstants::LABEL_API_BASE_URL              =>        ApiUrl::getApiHost(),
        ];

        $metricsName = MetricsConstants::SPLITZ_EXPERIMENT_DASHBOARD_CACHE_MISS;

        if ($cacheType === Constants::HITS){
            $metricsName = MetricsConstants::SPLITZ_EXPERIMENT_DASHBOARD_CACHE_HIT;
        }

        try {

            $this->metrics->count($metricsName, MetricsConstants::EVENT_COUNT_ONE, $dimensions);

        }
        catch (\Throwable $t)
        {
            $this->trace->warning(TraceCode::PUSH_METRICS_FAILED, [
                'message' => $t->getMessage() ?? 'unknown_message',
            ]);
        }
    }
}

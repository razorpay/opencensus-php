<?php


namespace App\Razorx;

use App\Http\ApiUrl;
use Auth;
use App\Base;
use App\Trace\TraceCode;
use App\Admin\ApiRequestAny;
use App\Constants\Constants as AppConstant;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Illuminate\Support\Facades\Redis;
use App\User\Constants as UserConstants;
use App\Splitz\Service as SplitzService;
use App\Metrics\Constants as MetricsConstants;
use Illuminate\Support\Facades\Session;

class Service extends Base\Service
{

    protected $trace;

    protected $app;
    /**
     * @var Store
     */
    protected $cache;

    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->metrics = $app['metrics'];

        $this->cache = $app['cache'];

        // redis cache timeout for 1 day (1440 minutes)
        $this->cacheTimeout = 2 * 12 * 60;
    }

    public function getKeysByPattern($pattern) {
        $keys = [];

        $cursor = null;

        $patternWithPrefix = UserConstants::REDIS_CACHE_PREFIX . $pattern;

        do {
            [$cursor, $batch] = Redis::scan($cursor, 'MATCH', $patternWithPrefix);

            $keys = array_merge($keys, $batch);
        } while ($cursor !== '0');

        return $keys;
    }

    public function generateCacheKey($merchantId, $input)
    {
        $encrypedInput = hash('sha256', json_encode($input));

        $sanitizedMerchantId = trim($merchantId);

        $cacheKey = Constants::RAZORX_CACHE_KEY . $sanitizedMerchantId . "_" . $encrypedInput;

        return $cacheKey;
    }

    public function forgotCacheKeys($keysToClear)
    {
        foreach ($keysToClear as $key) {
            $cacheKey = explode(UserConstants::REDIS_CACHE_PREFIX, $key);
            if (count($cacheKey) > 1 && !empty($cacheKey[1]))
            {
                $this->cache->forget($cacheKey[1]);
            }
        }
    }

    public function clearAllRazorxCache()
    {
        $keysToClear = $this->getKeysByPattern(Constants::RAZORX_CACHE_KEY . '*');

        $this->forgotCacheKeys($keysToClear);

        return [
            'success' => true
        ];
    }


    public function clearRazorxCacheMerchantLevel($merchantIds)
    {
        $keysToClear = [];

        foreach ($merchantIds as $merchantId) {
            $pattern = Constants::RAZORX_CACHE_KEY . trim($merchantId) . "_*";
            $cacheKeys = $this->getKeysByPattern($pattern);
            $keysToClear = array_merge($keysToClear, $cacheKeys);
        }

        $this->forgotCacheKeys($keysToClear);

        return [
            'success' => true
        ];
    }

    public function handleClearCachingForMerchants($input)
    {
        if(isset($input['merchants']) && count($input['merchants']) > 0){
            return $this->clearRazorxCacheMerchantLevel($input['merchants']);
        }
        return [
            'success'=> false,
            'message'=> "No merchant id's found in payload"
        ];
    }

    // service to invalidate razorx cache
    public function clearRazorxCache($input)
    {
        if($input['type'] === 'merchant') {
            return $this->handleClearCachingForMerchants($input);
        }

        if($input['type'] === 'all') {
            return $this->clearAllRazorxCache();
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

    public function updateExperiments(array $data, $razorxCachingEnabled = false, $merchantId = ''): array
    {
        $experimentsResults = $this->getBulkTreatment(Constants::FEATURE_FLAGS, $razorxCachingEnabled, $merchantId);

        foreach ($experimentsResults as $result => $val)
        {
            $data['experiments'][$result] = $val;
        }

        return $data;
    }

    public function getRazorxCacheByIdAsyncPromise($currentMerchantId)
    {
        $cacheKey = $this->generateCacheKey($currentMerchantId, Constants::FEATURE_FLAGS);

        $cachedResponse = $this->getCacheByKey($cacheKey);
        // If cached response exists, return it
        if($cachedResponse){
            $this->pushMetrics(UserConstants::HITS, $cacheKey);

            return $cachedResponse;
        }

        $this->pushMetrics(UserConstants::MISS, $cacheKey);

        return false;
    }

    public function setRazorxCacheByIdAsyncPromise($currentMerchantId, $data)
    {
        $cacheKey = $this->generateCacheKey($currentMerchantId, Constants::FEATURE_FLAGS);

        $this->setCacheByKey($cacheKey, $data);
    }

    /**
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    public function getBulkTreatmentPromise(array $features, Guzzle $guzzleClient): \GuzzleHttp\Promise\PromiseInterface
    {
        $featureString = implode(', ', $features);

        $request = new ApiRequestAny(['client_type' => 'merchant', AppConstant::HTTP_CLIENT => $guzzleClient]);

        return $request->sendAsyncPromise("razorx/bulkevaluate?features=$featureString", 'GET');
    }

    public function processBulkTreatmentPromiseResponse($apiExperimentPromise): array
    {
        list($error, $data)  = $apiExperimentPromise->processAsyncPromiseResponse();

        if (empty($error) === false)
        {
            $data = [];

            $this->trace->info(TraceCode::BULK_RAZORX_CALL_FAILED, [
                "error" => $error
            ]);

            foreach (Constants::FEATURE_FLAGS as $feature)
            {
                $data[$feature] = ['result' => 'control'];
            }
        }

        return $data;
    }

    public function isRazorxApiCallDisable($merchantId): bool
    {

        // Removed RAZORX_API_CALL_DISBALED experiment as its 100% ramped up
        return true;
    }

    public function getRazorxTreatment($featureFlag, $merchantId = '')
    {
        if ($this->isRazorxApiCallDisable($merchantId) === true)
        {
            $data = [];

            $data[$featureFlag] = ['result' => 'control'];

            return  $data;
        }

        // make an api call to razorx.
        $startTime = microtime(true) * 1000;

        $this->trace->info(TraceCode::GET_RAZORX_EXPERIMENTS_ROUTE_INFO, [
          'action'                => 'FetchStarted',
          'start_time'            => $startTime
        ]);

        $request = new ApiRequestAny(['client_type' => 'merchant']);

        list($error, $data) = $request->send("razorx/evaluate/$featureFlag", 'GET');

        if (empty($error) === false)
        {
            $data = [];

            $this->trace->info(TraceCode::BULK_RAZORX_CALL_FAILED, [
              "error" => $error
            ]);

            $data[$featureFlag] = ['result' => 'control'];

//            throw new BadRequestError(
//                $error[0],
//                ErrorCode::BAD_REQUEST_ERROR,
//                400
//            );
        }

        $endTime  = microtime(true) * 1000;
        $duration = round($endTime - $startTime);

        $this->trace->info(TraceCode::GET_RAZORX_EXPERIMENTS_ROUTE_INFO, [
          'action'              => 'FetchEnded',
          'end_time'            => $endTime,
          'duration'            => $duration,
          'controller'          => app('request')->route()->getAction()['controller']
        ]);

        return $data;
    }

    public function getBulkTreatment(array $features, $razorxCachingEnabled = false, $merchantId = '')
    {
        if ($this->isRazorxApiCallDisable($merchantId) === true)
        {
            $data = [];
            foreach ($features as $feature)
            {
                $data[$feature] = ['result' => 'control'];
            }
            return  $data;
        }

        $startTime = microtime(true) * 1000;

        $this->trace->info(TraceCode::GET_RAZORX_EXPERIMENTS_BULK_ROUTE_INFO, [
            'action'                => 'FetchStarted',
            'start_time'            => $startTime
        ]);

        // Define a cache key based on features data and merchantId
        $cacheKey = $this->generateCacheKey($merchantId, $features);

        if($razorxCachingEnabled && empty($merchantId) === false){
            $cachedResponse = $this->getCacheByKey($cacheKey);
            // If cached response exists, return it
            if($cachedResponse)
            {
                $this->pushMetrics(UserConstants::HITS, $cacheKey);

                return $cachedResponse;
            }

            $this->pushMetrics(UserConstants::MISS, $cacheKey);
        }

        $request = new ApiRequestAny(['client_type' => 'merchant']);

        $featureString = implode(', ', $features);

        list($error, $data) = $request->send("razorx/bulkevaluate?features=$featureString", 'GET');

        if (empty($error) === false)
        {
            $data = [];

            $this->trace->info(TraceCode::BULK_RAZORX_CALL_FAILED, [
                "error" => $error
            ]);

            foreach ($features as $feature)
            {
                $data[$feature] = ['result' => 'control'];
            }
        }

        $endTime  = microtime(true) * 1000;
        $duration = $endTime - $startTime;

        $this->trace->info(TraceCode::GET_RAZORX_EXPERIMENTS_BULK_ROUTE_INFO, [
            'action'              => 'FetchEnded',
            'end_time'            => $endTime,
            'duration'            => $duration,
            'controller'          => app('request')->route()->getAction()['controller']

        ]);

        if($razorxCachingEnabled && empty($merchantId) === false && empty($error)){
            $this->setCacheByKey($cacheKey, $data);
        }

        return $data;
    }

    public function pushMetrics($cacheType, $cacheKey, $route = 'razorx/bulkevaluate'){

        $dimensions = [
            UserConstants::CACHE_KEY                    => $cacheKey,
            UserConstants::ROUTE_NAME                   => $route,
            MetricsConstants::LABEL_API_BASE_URL  => ApiUrl::getApiHost(),
        ];

        $metricsName = MetricsConstants::RAZORX_EXPERIMENT_DASHBOARD_CACHE_MISS;

        if ($cacheType === UserConstants::HITS){
            $metricsName = MetricsConstants::RAZORX_EXPERIMENT_DASHBOARD_CACHE_HIT;
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


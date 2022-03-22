<?php

namespace RZP\Services;

use App;

use RZP\Exception;
use RZP\Models\Address;
use RZP\Models\Address\Entity;
use RZP\Trace\TraceCode;
use Illuminate\Support\Str;
use RZP\Models\Order\OrderMeta\Order1cc;

/**
 * Used by 1cc
 * checks completeness of an address and
 * likelihood of rto for cod orders
 */
class ThirdWatchService
{
    const ADDRESS_COD_VALIDITY_TTL = 30 * 1440; // 30 days
    const ADDRESS_VALIDITY_CACHE_KEY_PREFIX = 'TW_ADDRESS_COD_VALIDITY';

    const MAX_POLLING_TIME_MILLIS = 1000; // 1sec
    const POLLING_INTERVAL_MILLIS = 50; // 50ms

    const CACHE_RESULT_TAG_KEY = 'result';
    const CACHE_RESULT_TAG_VALUE_HIT = 'hit';
    const CACHE_RESULT_TAG_VALUE_MISS = 'miss';

    private $app;

    private $cache;

    private $trace;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

        $this->cache = $this->app['cache'];
    }

    /**
     * Check address completeness and COD eligibility using Thirdwatch
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function checkAddressServiceability(array $input): array
    {
        $serviceStart = $this->getCurrentTimeInMillis();

        try
        {
            if (!isset($input['address']) || !isset($input['order_id']))
            {
                throw new Exception\BadRequestValidationFailureException();
            }

            // Rzp order id
            $orderId = $input['order_id'];

            $address = $input['address'];

            (new Address\Validator())->setStrictFalse()->validateInput('codServiceabilityCheck', $address);

            // set unique id for caching if not present
            $this->getAddressId($orderId, $address);

            $key = $this->getCacheKey($address);
            $cacheResponse = $this->cache->get($key);

            if (empty($cacheResponse) === false)
            {
                $this->trace->count(
                    TraceCode::TW_ADDRESS_COD_VALIDITY_CACHE_GET_TOTAL,
                    [self::CACHE_RESULT_TAG_KEY => self::CACHE_RESULT_TAG_VALUE_HIT]
                );

                return ['cod' => $cacheResponse['label'] === 'green'];
            }

            $this->trace->count(
                TraceCode::TW_ADDRESS_COD_VALIDITY_CACHE_GET_TOTAL,
                [self::CACHE_RESULT_TAG_KEY => self::CACHE_RESULT_TAG_VALUE_MISS]
            );

            $this->enrichAddressForTW($orderId, $address);
            $kafkaResult = (new ThirdWatchClient())->sendAddressToKafka($key, $address);

            if ($kafkaResult === false)
            {
                return ['cod' => false];
            }

            $response = $this->pollCacheForThirdWatchResponse($key);

            if (empty($response) === true)
            {
                return ['cod' => false ];
            }

            return $response;
        }
        finally
        {
            $this->trace->histogram(
                TraceCode::TW_ADDRESS_COD_VALIDITY_TOTAL_DURATION,
                $this->getCurrentTimeInMillis() - $serviceStart
            );
        }
    }

    /**
     * Check cod eligibility
     * @param array $input
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     * TODO : Once 1cc/check_cod_eligibility api is live then we can remove tw/address/check_cod_eligibility.
     */
    public function checkCodEligibility(array $input): array
    {
        $serviceStart = $this->getCurrentTimeInMillis();

        try
        {
            if (!isset($input['address']) || !isset($input['order_id']) || !isset($input['device']))
            {
                throw new Exception\BadRequestValidationFailureException();
            }

            // Rzp order id
            $orderId = $input['order_id'];

            $address = $input['address'];

            (new Address\Validator())->setStrictFalse()->validateInput('codServiceabilityCheck', $address);

            $input['device']['ip'] = $this->app['request']->ip();

            (new Order1cc\Validator())->validateDevice('customerDeviceDetails', $input['device']);

            // set unique id for caching if not present
            $this->getAddressId($orderId, $address);

            //TODO : Need to replace kafka logic by sending http request once rto prediction service is live.
            $key = $this->getCacheKey($address);
            $cacheResponse = $this->cache->get($key);

            if (empty($cacheResponse) === false)
            {
                $this->trace->count(
                    TraceCode::TW_ADDRESS_COD_VALIDITY_CACHE_GET_TOTAL,
                    [self::CACHE_RESULT_TAG_KEY => self::CACHE_RESULT_TAG_VALUE_HIT]
                );

                return ['cod' => $cacheResponse['label'] === 'green'];
            }

            $this->trace->count(
                TraceCode::TW_ADDRESS_COD_VALIDITY_CACHE_GET_TOTAL,
                [self::CACHE_RESULT_TAG_KEY => self::CACHE_RESULT_TAG_VALUE_MISS]
            );

            $this->enrichAddressForTW($orderId, $address);
            $kafkaResult = (new ThirdWatchClient())->sendAddressToKafka($key, $address);

            try
            {
                $response = $this->app['rto_prediction_provider_service']->evaluate($input);
//                if (strcmp($response['result']['action'], "allow") == 0)
//                {
//                    return ['cod' => true];
//                }
//                return ['cod' => false];
            }
            catch (Exception\BadRequestException $e)
            {
//                return ['cod' => false];
            }
            catch (\Exception $e)
            {
//                return ['cod' => true];
            }

            if ($kafkaResult === false)
            {
                return ['cod' => false];
            }

            $response = $this->pollCacheForThirdWatchResponse($key);

            if (empty($response) === true)
            {
                return ['cod' => false];
            }

            return $response;
        }
        finally
        {
            $this->trace->histogram(
                TraceCode::TW_ADDRESS_COD_VALIDITY_TOTAL_DURATION,
                $this->getCurrentTimeInMillis() - $serviceStart
            );
        }
    }

    /**
     * ThirdWatch callback. Stores the result in cache.
     * @param array $input
     * @return array
     */
    public function saveCodScoreForAddress(array $input)
    {
        (new Address\Validator())->validateInput('addressCodScoreResponse', $input);

        $this->cache->put($this->getCacheKey($input), $input, self::ADDRESS_COD_VALIDITY_TTL);

        return [ 'success' => true ];
    }

    protected function getCurrentTimeInMillis()
    {
        return round(microtime(true) * 1000);
    }

    protected function pollCacheForThirdWatchResponse($key): array
    {
        $response = ['cod' => false];

        $time = $newTime = $this->getCurrentTimeInMillis();

        $cachePopulated = false;
        while ($newTime - $time <= self::MAX_POLLING_TIME_MILLIS)
        {
            $result = $this->sleepAndCheckResponse($key);

            if (isset($result['label']))
            {
                $response['cod'] = ($result['label'] === 'green');
                $cachePopulated = true;
                break;
            }
            else
            {
                $newTime = $this->getCurrentTimeInMillis();
            }
        }

        if ($cachePopulated === false) {
            $this->trace->count(TraceCode::TW_ADDRESS_COD_VALIDITY_POLLING_TIMEOUTS);
        }

        // API call received from TW
        $this->trace->histogram(
            TraceCode::TW_ADDRESS_COD_VALIDITY_POLL_DURATION,
            $newTime - $time
        );

        return $response;
    }

    protected function getAddressId(string $orderId, array &$address)
    {
        if (isset($address[Entity::ID]) === false)
        {
            //just generate a random id for an unsaved address
            $address[Entity::ID] = $orderId . ':' . Str::uuid();
        }
    }

    protected function getCacheKey(array $address)
    {
        return self::ADDRESS_VALIDITY_CACHE_KEY_PREFIX . ':' . $address[Entity::ID];
    }

    protected function sleepAndCheckResponse(string $key)
    {
        usleep(self::POLLING_INTERVAL_MILLIS * 1000);

        return $this->cache->get($key);
    }

    private function enrichAddressForTW(string $orderId, array &$address)
    {
        $address['order_id'] = $orderId;
        if (isset($address[Entity::LINE2]) === false) {
            $address[Entity::LINE2] = "";
        }
    }
}

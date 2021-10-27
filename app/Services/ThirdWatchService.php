<?php

namespace RZP\Services;

use App;

use RZP\Exception;
use RZP\Models\Address;
use RZP\Models\Address\Entity;
use RZP\Trace\TraceCode;
use Illuminate\Support\Str;

/**
 * Used by 1cc
 * checks completeness of an address and
 * likelihood of rto for cod orders
 */
class ThirdWatchService
{
    const ADDRESS_COD_VALIDITY_TTL = 1440; // 24 hours
    const ADDRESS_VALIDITY_CACHE_KEY_PREFIX = 'TW_ADDRESS_COD_VALIDITY';

    const MAX_POLLING_TIME_MILLIS = 1000; // 1sec
    const POLLING_INTERVAL_MILLIS = 50; // 50ms

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

        $response = $this->cache->get($key);

        // push to Kafka if address is not cached
        if (empty($response))
        {
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
        }

        $this->trace->count(
            TraceCode::TW_ADDRESS_COD_VALIDITY_TOTAL_TIME_TAKEN,
            ['time_taken' => $this->getCurrentTimeInMillis() - $serviceStart]
        );

        return ['cod' => $response['label'] === 'green'];
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

    protected function pollCacheForThirdWatchResponse($key)
    {
        $response = ['cod' => false];

        $time = $newTime = $this->getCurrentTimeInMillis();

        while ($newTime - $time <= self::MAX_POLLING_TIME_MILLIS)
        {
            $result = $this->sleepAndCheckResponse($key);

            if (isset($result['label']))
            {
                $response['cod'] = ($result['label'] === 'green');
                break;
            }
            else
            {
                $newTime = $this->getCurrentTimeInMillis();
            }
        }

        // no API call received from TW
        $this->trace->count(
              TraceCode::TW_ADDRESS_COD_VALIDITY_POLL_TIME_TAKEN, [
                  'time_taken' => $newTime - $time
        ]);

        $this->trace->debug(TraceCode::TW_ADDRESS_COD_VALIDITY_RESPONSE_TIMEOUT, [
            'cod' => $response['cod']
        ]);

        return $response;
    }

    protected function getAddressId(string $orderId, array &$address)
    {
        if (isset($address[Entity::ENTITY_ID]))
        {
            $address[Entity::ID] = $address[Entity::ENTITY_ID];
        }
        else
        {
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
}

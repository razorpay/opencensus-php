<?php

namespace RZP\Services\Throttle;

use RZP\Http\Throttle\Constant as K;
use Illuminate\Support\Facades\Redis;

class Service
{
    public function create(array $input)
    {
        (new Validator)->validateInput('create', $input);

        $redis = $this->initRedisConnection();

        $key = $this->getKey($input);

        // store id level settings key in the set
        if (empty($input['id']) === false)
        {
            $redis->sadd(K::CUSTOM_SETTINGS_SET, $key);
        }

        return $redis->hmset($key, $this->getRules($input));
    }

    public function fetchMultiple(array $input): array
    {
        (new Validator)->validateInput('fetch', $input);

        $redis = $this->initRedisConnection();

        return $redis->hgetall($this->getKey($input));
    }

    public function createConfig(array $input)
    {
        (new Validator)->validateInput('create_config', $input);

        $redis = $this->initRedisConnection();

        $key = $this->getRedisKey($input);

        return $redis->hmset($key, $this->getConfigRules($input));
    }

    public function fetchConfig(array $input)
    {
       (new Validator)->validateInput('delete_config', $input);

       if (empty($input) === true)
       {
            return $this->fetchAll();
       }

        $key = $this->getRedisKey($input);

        $redis = $this->initRedisConnection();

        $rules = $redis->hgetall($key);

        if (empty($rules) === true)
        {
            return [];
        }

        $formattedRules = $this->formatRules($rules);

        if ((empty($input['merchant_id']) === false) and
            (empty($input['route']) === false) and
            (array_key_exists($input['route'], $formattedRules)))
        {
            return $formattedRules[$input['route']];
        }

        return $formattedRules;
    }

    public function fetchAll()
    {
        $redis = $this->initRedisConnection();

        $output= [
            'merchants' => [],
            'routes'    => [],
        ];

        $routes    = $redis->smembers(K::CUSTOM_ROUTE_SET);
        $merchants = $redis->smembers(K::CUSTOM_MERCHANT_SET);

        foreach ($merchants as $merchantId)
        {
            $key = $this->getRedisKey(['merchant_id' => $merchantId]);

            $rules = $redis->hgetall($key);

            if (empty($rules) === true)
            {
                $redis->srem(K::CUSTOM_MERCHANT_SET, $merchantId);
            }

            $formattedRules = $this->formatRules($rules);

            if (empty($formattedRules) == false)
            {
                $output['merchants'][$merchantId] = $formattedRules;
            }

        }

        foreach ($routes as $route)
        {
            $key = $this->getRedisKey(['route' => $route]);

            $rules = $redis->hgetall($key);

            if (empty($rules) === true)
            {
                $redis->srem(K::CUSTOM_ROUTE_SET, $route);
            }

            $formattedRules = $this->formatRules($rules);

            if (empty($formattedRules) == false)
            {
                $output['routes'][$route] = $formattedRules[$route];
            }
        }

        return $output;
    }

    public function formatRules($rules)
    {
        $formatRules = [];

        foreach ($rules as $key => $value)
        {
            $items = explode(":", $key);

            $formatRules[$items[0]][$items[1]] = $value;
        }

        return $formatRules;
    }

    public function deleteConfig(array $input)
    {
      (new Validator)->validateInput('delete_config', $input);

      $redis = $this->initRedisConnection();

      $key = $this->getRedisKey($input);

      if ((empty($input['merchant_id']) === false) and
          (empty($input['route']) === false))
      {
        $keyPrefix = $input['route'] . ':';

        $setKeys = [
            $keyPrefix . K::THROTTLE_REQUEST_COUNT,
            $keyPrefix . K::THROTTLE_REQUEST_WINDOW,
        ];

        return $redis->hdel($key, $setKeys);
      }

      // delete complete route config.
      return $redis->del($key);
    }

    protected function deleteMerchantConfig($input, $key)
    {
        $redis = $this->initRedisConnection();

        if (empty($input['route']) === true)
        {
            return $redis->del($key);
        }

        return $redis->hdel($key, $setKeys);
    }

    protected function getKeyPrefix(array $input)
    {
        $keyPrefix = $input['route'] . ':';
    }

    protected function getConfigRules(array $input): array
    {
        $formattedRules = [];

        $keyPrefix = $input['route'] . ':';

        if ($input[K::CONFIGURATION_TYPE] === K::CONFIGURATION_TYPE_ROUTE)
        {
            $formattedRules[$keyPrefix . K::CONFIGURATION_TYPE] = $input[K::THROTTLE_TYPE];
        }

        $formattedRules[$keyPrefix . K::THROTTLE_REQUEST_COUNT]  = (int)  $input[K::THROTTLE_REQUEST_COUNT];
        $formattedRules[$keyPrefix . K::THROTTLE_REQUEST_WINDOW] = (int) $input[K::THROTTLE_REQUEST_WINDOW];

        return $formattedRules;
    }

    protected function getRedisKey($input): string
    {
        $redis = $this->initRedisConnection();

        if (empty($input['merchant_id']) === true)
        {
            $redis->sadd(K::CUSTOM_ROUTE_SET, $input['route']);

            return K::THROTTLE_PREFIX . '{' . K::CONFIGURATION_TYPE_ROUTE . ':' . $input['route'] . '}';
        }

        $redis->sadd(K::CUSTOM_MERCHANT_SET, $input['merchant_id']);

        return K::THROTTLE_PREFIX . '{' . K::CONFIGURATION_TYPE_MERCHANT . ':' . $input['merchant_id'] . '}';
    }

    /**
     * Get the global or id level settings key based on whether we are setting global or id level settings
     *
     * @param array $input
     *
     * @return string
     */
    protected function getKey(array $input): string
    {
        return (empty($input['id']) === true) ? K::GLOBAL_SETTINGS_KEY : K::ID_SETTINGS_KEY_PREFIX . $input['id'];
    }

    protected function initRedisConnection()
    {
        return Redis::connection('throttle')->client();
    }

    /**
     * Fetch different rules which needs to be set in hash map based on input
     *
     * @param array $input
     *
     * @return array
     */
    protected function getRules(array $input): array
    {
        (new Validator)->validateInput('rules', $input['rules']);

        $mode  = $input['mode'] ?? '';
        $auth  = $input['auth'] ?? '';
        $proxy = $input['proxy'] ?? 0;
        $route = $input['route'] ?? '';

        $rulePrefix = $this->getRulePrefix($mode, $auth, $proxy, $route);

        // store boolean flags as integers in redis
        $booleanRules = [
            K::BLOCK,
            K::SKIP,
            K::MOCK,
        ];

        $formattedRules = [];

        foreach ($input['rules'] as $ruleName => $ruleValue)
        {
            $formattedRules[$rulePrefix . $ruleName] = $ruleValue;

            if (in_array($ruleName, $booleanRules, true) === true)
            {
                $formattedRules[$rulePrefix . $ruleName] = (int) $ruleValue;
            }
        }

        return $formattedRules;
    }

    /**
     * Get rule prefix based on level of overriding
     *
     * @param string $mode
     * @param string $auth
     * @param int    $proxy
     * @param string $route
     *
     * @return string
     */
    protected function getRulePrefix(string $mode, string $auth, int $proxy, string $route): string
    {
        $prefix = '';

        if (empty($route) === false)
        {
            $prefix = "{$mode}:{$auth}:{$proxy}:$route:";
        }
        else if (empty($auth) === false)
        {
            $prefix = "{$mode}:{$auth}:{$proxy}:";
        }
        else if (empty($mode) === false)
        {
            $prefix = "{$mode}:";
        }

        return $prefix;
    }
}

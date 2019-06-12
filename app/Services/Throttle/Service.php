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
        return Redis::connection()->client();
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

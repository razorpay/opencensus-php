<?php
/**
 * Copyright 2019 OpenCensus Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OpenCensus\Trace\Integrations;

use OpenCensus\Trace\Span;
use OpenCensus\Trace\Tracer;

/**
 * This class handles instrumenting Redis requests using the opencensus extension.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Integrations\Redis;
 *
 * Redis::load();
 * ```
 */


const VALUE_PLACEHOLDER = "?";
const VALUE_MAX_LEN = 100;
const VALUE_TOO_LONG_MARK = "...";
const CMD_MAX_LEN = 256;


// implementation mostly adapted from: https://github.com/DataDog/dd-trace-php

class Redis implements IntegrationInterface
{
    private static $hostMapping = [];

    /**
     * Static method to add instrumentation to redis requests
     */
    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load Redis integrations.', E_USER_WARNING);
        }

        opencensus_trace_method('Predis\Client', '__construct', [static::class, 'handleConstuct']);

        // covers all basic commands
        opencensus_trace_method('Predis\Client', 'executeCommand', [static::class, 'handleExecuteCommand']);
    }


    public static function handleConstuct($predis, $params)
    {
        $connection_str = sprintf("%s:%s", $params[0]['host'], $params[0]['port']);

        if ((method_exists($predis, 'getConnection')) && (is_iterable($predis->getConnection()) === true)) {
            foreach ($predis->getConnection() as $connection) {
                self::$hostMapping[$connection->getParameters()->host] = $connection_str;
            }
        }

        return [
            'attributes' => [
                'db.connection_string' => $connection_str,
                'db.system'            => 'redis',
                'db.type'              => 'redis',
                'net.peer.name'        => $params[0]['host'],
                'net.peer.port'        => $params[0]['port'],
                'span.kind'            => 'client'
            ],
            'kind'                    => 'client',
            'name'                    => 'Redis connect',
            'sameProcessAsParentSpan' => false
        ];
    }

    public static function handleExecuteCommand($predis, $command)
    {
        $params = [];

        $cmdParams = null;

        $predisConnection = $predis->getConnection();

        if (get_class($predisConnection) === 'Predis\Connection\Aggregate\RedisCluster') {
            $cmdParams = $predisConnection->getConnection($command)->getParameters();
        } else {
            $cmdParams = $predisConnection->getParameters();
        }

        if ((isset($cmdParams->host) === true) && (array_key_exists($cmdParams->host, self::$hostMapping) === true)) {
            $connection = explode(':', self::$hostMapping[$cmdParams->host]);

            $params = [
                'host' => $connection[0],
                'port' => $connection[1],
            ];
        } else {
            $params = [
                'host' => $cmdParams->host ?? '',
                'port' => $cmdParams->port ?? '',
            ];
        }

        $connection_str = sprintf("%s:%s", $params['host'], $params['port']);

        $arguments = $command->getArguments();

        array_unshift($arguments, $command->getId());
        $query = Redis::formatArguments($arguments);

        $attributes = [
            'command'              => $command->getId(),
            'db.connection_string' => $connection_str,
            'db.operation'         => $command->getId(),
            'db.system'            => 'redis',
            'db.type'              => 'redis',
            'net.peer.name'        => $params['host'],
            'net.peer.port'        => $params['port'],
            'redis.args_length'    => count($arguments),
            'service.name'         => 'redis',
            'span.kind'            => 'client',
        ];

        return [
            'attributes'              => $attributes,
            'kind'                    => 'client',
            'name'                    => 'Redis ' . $command->getId(),
            'sameProcessAsParentSpan' => false
        ];
    }

    public static function formatArguments($arguments)
    {
        $len = 0;
        $out = [];

        foreach ($arguments as $argument) {
            // crude test to skip binary
            if (strpos($argument, "\0") !== false) {
                continue;
            }

            $cmd = (string) $argument;

            if (strlen($cmd) > VALUE_MAX_LEN) {
                $cmd = substr($cmd, 0, VALUE_MAX_LEN) . VALUE_TOO_LONG_MARK;
            }

            if (($len + strlen($cmd)) > CMD_MAX_LEN) {
                $prefix = substr($cmd, 0, CMD_MAX_LEN - $len);
                $out[] = $prefix . VALUE_TOO_LONG_MARK;
                break;
            }

            $out[] = $cmd;
            $len += strlen($cmd);
        }

        return implode(' ', $out);
    }
}

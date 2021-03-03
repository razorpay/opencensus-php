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

    /**
     * Static method to add instrumentation to redis requests
     */
    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load Redis integrations.', E_USER_WARNING);
        }

        opencensus_trace_method('Predis\Client', '__construct', function ($predis, $params) {
            // checks if span limit has reached and if yes flushes the closed spans
            if (Tracer::$tracer != null) {
                Tracer::$tracer->checkSpanLimit();
            }
            $connection_str = sprintf("%s:%s", $params[0]['host'], $params[0]['port']);
            return [
                'attributes' => [
                    'peer.hostname' => $params[0]['host'],
                    'peer.port' => $params[0]['port'],
                    'net.peer.name' => $params[0]['host'],
                    'db.type' => 'redis',
                    'db.system' => 'redis',
                    'db.connection_string' =>  $connection_str,
                    'span.kind' => Span::KIND_CLIENT
                ],
                'kind' => Span::KIND_CLIENT,
                'name' => 'Predis connect',
                'sameProcessAsParentSpan' => false
            ];
        });

        // covers all basic commands
        opencensus_trace_method('Predis\Client', 'executeCommand', function ($predis, $command) {
            $arguments = $command->getArguments();
            array_unshift($arguments, $command->getId());
            $query = Redis::formatArguments($arguments);

            // checks if spanlimit has reached and if yes flushes the closed spans
            if (Tracer::$tracer != null) {
                Tracer::$tracer->checkSpanLimit();
            }

            return ['attributes' => [
                        'db.type' => 'redis',
                        'db.system' => 'redis',
                        'db.statement' => $query,
                        'db.operation' => $command->getId(),
                        'command' => $command->getId(),
                        'service.name' => 'redis',
                        'redis.raw_command' => $query,
                        'redis.args_length' => count($arguments),
                        'span.kind' => Span::KIND_CLIENT
                    ],
                    'kind' => Span::KIND_CLIENT,
                    'name' => 'Predis ' . $command->getId(),
                    'sameProcessAsParentSpan' => false
                ];
        });
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

            $cmd = (string)$argument;

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

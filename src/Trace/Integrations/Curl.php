<?php
/**
 * Copyright 2017 OpenCensus Authors
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
 * This class handles instrumenting curl requests using the opencensus extension.
 *
 * Example:
 * ```
 * use OpenCensus\Trace\Integrations\Curl;
 *
 * Curl::load();
 * ```
 */
class Curl implements IntegrationInterface
{
    /**
     * Static method to add instrumentation to curl requests
     */
    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load curl integrations.', E_USER_WARNING);
            return;
        }

        opencensus_trace_function('curl_exec', [static::class, 'handleCurlResource']);
        opencensus_trace_function('curl_multi_add_handle');
        opencensus_trace_function('curl_multi_remove_handle');
    }

    /**
     * Handle extracting the uri from a given curl resource handler
     *
     * @internal
     * @param resource $resource The curl handler
     * @return array
     */
    public static function handleCurlResource($resource)
    {
        $info = curl_getinfo($resource);
        $attrs = self::getSpanAttrsFromCurlInfo($info);

        // checks if span limit has reached and if yes exports the closed spans
        if (Tracer::$tracer != null) {
            Tracer::$tracer->checkSpanLimit();
        }

        return [
            'attributes' => $attrs,
            'kind' => Span::KIND_CLIENT
        ];
    }

    private static function getSpanAttrsFromCurlInfo($curlInfo)
    {
        $tagNameCurlInfoMap = [
            'network.client.ip'                 => 'local_ip',
            'network.client.port'               => 'local_port',
            'network.destination.ip'            => 'primary_ip',
            'network.destination.port'          => 'primary_port',
            'network.bytes_read'                => 'size_download',
            'network.bytes_written'             => 'size_upload',
            'time_total_in_secs'                => 'total_time',
            'time_to_connect_in_secs'           => 'connect_time',
            'time_to_redirect_in_secs'          => 'redirect_time',
            'time_to_namelookup_in_secs'        => 'namelookup_time',
            'time_to_pretransfer_in_secs'       => 'pretransfer_time',
            'time_to_starttransfer_in_secs'     => 'starttransfer_time',
            'primary_ip'                        => 'primary_ip',
            'uri'                               => 'url'
        ];

        $attrs = [];

        foreach ($tagNameCurlInfoMap as $tagName => $curlInfoName) {
            if (isset($curlInfo[$curlInfoName]) && !\trim($curlInfo[$curlInfoName]) !== '') {
                $attrs[$tagName] = $curlInfo[$curlInfoName];
            }
        }
        $attrs += ['kind' => 'CLIENT'];
        return $attrs;
    }
}

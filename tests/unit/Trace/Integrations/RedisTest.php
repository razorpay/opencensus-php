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

namespace OpenCensus\Tests\Unit\Trace\Integrations;

use OpenCensus\Trace\Integrations\Redis;
use OpenCensus\Trace\Span;
use PHPUnit\Framework\TestCase;
use Predis\Client;


class RedisTest extends TestCase
{
    public function testHandleConstruct()
    {
        $predis = new Client();
        $params = [
            0 => [
                'host' => '127.0.0.1',
                'port' => '6397',
            ]
        ];

        $span = Redis::handleConstuct($predis, $params);

        $expected = [
            'attributes' => [
                'db.connection_string' => '127.0.0.1:6397',
                'db.system'            => 'redis',
                'db.type'              => 'redis',
                'net.peer.name'        => $params[0]['host'],
                'net.peer.port'        => $params[0]['port'],
                'span.kind'            => 'client'
            ],
            'kind'                    => strtolower(Span::KIND_CLIENT),
            'name'                    => 'Redis connect',
            'sameProcessAsParentSpan' => false
        ];

        $this->assertEquals($expected, $span);
    }
}

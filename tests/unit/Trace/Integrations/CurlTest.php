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

use OpenCensus\Trace\Integrations\Curl;
use OpenCensus\Trace\Span;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class CurlTest extends TestCase
{
    public function testLoadUrlFromResource()
    {
        $resource = curl_init('https://www.google.com');

        $spanOptions = Curl::handleCurlResource($resource);
        $expected_uri = 'https://www.google.com';
        $expected_kind = Span::KIND_CLIENT;

        $this->assertEquals($expected_uri, $spanOptions['attributes']['uri']);
        $this->assertEquals('CLIENT', $spanOptions['attributes']['kind']);
        $this->assertEquals($expected_kind, $spanOptions['kind']);
    }
}

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

namespace OpenCensus\Tests\Unit\Trace;

use OpenCensus\Trace\SpanContext;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class SpanContextTest extends TestCase
{
    public function testGeneratesDefaultTraceId()
    {
        $context = new SpanContext();
        $this->assertMatchesRegularExpression('/[0-9a-z]{32}/', $context->traceId());
    }

    public function testSpanWithBaggageItems()
    {
        $context = new SpanContext();

        $newContext = $context->withBaggageItem('request_id', '123456');
        $this->assertEquals('123456', $newContext->getBaggageItem('request_id'));
    }
}

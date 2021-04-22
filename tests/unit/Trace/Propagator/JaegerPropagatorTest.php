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

namespace OpenCensus\Tests\Unit\Trace\Propagator;

use OpenCensus\Trace\Propagator\ArrayHeaders;
use OpenCensus\Trace\SpanContext;
use OpenCensus\Trace\Propagator\JaegerPropagator;
use PHPUnit\Framework\TestCase;

/**
 * @group trace
 */
class JaegerPropagatorTest extends TestCase
{
    /**
     * @dataProvider traceMetadata
     */
    public function testExtractBaggageItems($traceId, $spanId, $enabled, $jaegerTraceHeader, $baggageItems)
    {
        $propagator = new JaegerPropagator();
        $context = $propagator->extract(new ArrayHeaders(['HTTP_UBER_TRACE_ID' => $jaegerTraceHeader] + $baggageItems));
        $this->assertEquals($traceId, $context->traceId());
        $this->assertEquals($spanId, $context->spanId());
        $this->assertEquals($enabled, $context->enabled());
        $this->assertEquals($propagator->getBaggageItemsFromHeader($baggageItems), $context->baggage());
        $this->assertTrue($context->fromHeader());
    }

    /**
     * @dataProvider traceMetadata
     */
    public function testInjectBaggageItems($traceId, $spanId, $enabled, $jaegerTraceHeader, $baggageItems)
    {
        $propagator = new JaegerPropagator();
        $context = new SpanContext($traceId, $spanId, $enabled, true, $propagator->getBaggageItemsFromHeader($baggageItems));
        $headers = new ArrayHeaders();
        $propagator->inject($context, $headers);
        $this->assertEquals($jaegerTraceHeader, $headers->get('uber-trace-id'));
        $this->assertEquals($baggageItems['HTTP_RZP_CTX_REQUEST_ID'] ?? "", $headers->get('rzp-ctx-request_id'));
    }

    /**
     * Data provider for testing serialization and serialization. We use hex strings here to make
     * the test human readable to see that our test data adheres to the spec.
     * See https://github.com/census-instrumentation/opencensus-specs/blob/master/encodings/BinaryEncoding.md
     * for the encoding specification.
     */
    public function traceMetadata()
    {
        return [
            ['123456789012345678901234567890ab', '00000000000004d2', true,  '123456789012345678901234567890ab:00000000000004d2:0000000000000000:1', ['HTTP_RZP_CTX_REQUEST_ID' => '12345678901234567']],
            ['123456789012345678901234567890ab', '00000000000004d2', false, '123456789012345678901234567890ab:00000000000004d2:0000000000000000:0', ['HTTP_RZP_CTX_REQUEST_ID' => '12345678901234567']],
            ['123456789012345678901234567890ab', '00000000000004d2', true,  '123456789012345678901234567890ab:00000000000004d2:0000000000000000:1', []],
        ];
    }
}

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

namespace OpenCensus\Tests\Unit\Trace\Tracer;

use OpenCensus\Trace\MessageEvent;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\SpanContext;
use OpenCensus\Trace\Tracer\TracerInterface;
use PHPUnit\Framework\TestCase;
use OpenCensus\Trace\Exporter\NullExporter;

/**
 * @group trace
 */
abstract class AbstractTracerTest extends TestCase
{
    abstract protected function makeTracer(...$args): TracerInterface;

    public function testMaintainsContext()
    {
        $parentSpanId = 12345;
        $initialContext = new SpanContext('traceid', $parentSpanId, true, false, ['rzpctx-key1' => 'value1']);
        $tracer = $this->makeTracer($initialContext);
        $context = $tracer->spanContext();

        $this->assertEquals('traceid', $context->traceId());
        $this->assertEquals($parentSpanId, $context->spanId());
        $this->assertEquals(['rzpctx-key1' => 'value1'], $context->baggage());

        $tracer->inSpan(['name' => 'test'], function () use ($parentSpanId, $tracer) {
            $context = $tracer->spanContext();
            $this->assertNotEquals($parentSpanId, $context->spanId());
        });

        $spans = $tracer->spans();
        $this->assertCount(1, $spans);
        $spanData = $spans[0];
        $this->assertEquals('traceid', $spanData->traceId());
        $this->assertEquals('test', $spanData->name());
        $this->assertEquals($parentSpanId, $spanData->parentSpanId());
    }

    public function testAddsAttributesToCurrentSpan()
    {
        $tracer = $this->makeTracer();
        $tracer->inSpan(['name' => 'root'], function () use ($tracer) {
            $tracer->inSpan(['name' => 'inner'], function () use ($tracer) {
                $tracer->addAttribute('foo', 'bar');
            });
        });

        $spans = $tracer->spans();
        $this->assertCount(2, $spans);
        $spanData = $spans[1];
        $this->assertEquals('inner', $spanData->name());
        $attributes = $spanData->attributes();
        $this->assertArrayHasKey('foo', $attributes);
        $this->assertEquals('bar', $attributes['foo']);
    }

    public function testAddsAttributesToRootSpan()
    {
        $tracer = $this->makeTracer();
        $rootSpan = $tracer->startSpan(['name' => 'root']);
        $scope = $tracer->withSpan($rootSpan);
        $tracer->inSpan(['name' => 'inner'], function () use ($tracer, $rootSpan) {
            $tracer->addAttribute('foo', 'bar', ['span' => $rootSpan]);
        });
        $scope->close();

        $spans = $tracer->spans();
        $this->assertCount(2, $spans);
        $spanData = $spans[0];
        $this->assertEquals('root', $spanData->name());
        $attributes = $spanData->attributes();
        $this->assertArrayHasKey('foo', $attributes);
        $this->assertEquals('bar', $attributes['foo']);
    }

    public function testWithSpan()
    {
        $span = new Span(['name' => 'foo']);
        $tracer = $this->makeTracer();

        $this->assertNull($tracer->spanContext()->spanId());
        $scope = $tracer->withSpan($span);
        $this->assertEquals($span->spanId(), $tracer->spanContext()->spanId());
        $scope->close();
        $this->assertNull($tracer->spanContext()->spanId());
    }

    public function testSetStartTime()
    {
        $time = microtime(true) - 10;
        $span = new Span(['name' => 'foo', 'startTime' => $time]);
        $tracer = $this->makeTracer();
        $scope = $tracer->withSpan($span);
        usleep(100);
        $scope->close();

        $this->assertEquivalentTimestamps(
            $span->spanData()->startTime(),
            $tracer->spans()[0]->startTime()
        );
    }

    public function testAddsAnnotations()
    {
        $tracer = $this->makeTracer();
        $tracer->inSpan(['name' => 'root'], function () use ($tracer) {
            $tracer->addAnnotation('some root annotation', ['attributes' => ['foo' => 'bar']]);
            $tracer->inSpan(['name' => 'inner'], function () use ($tracer) {
                $tracer->addAnnotation('some inner annotation');
            });
        });

        $spans = $tracer->spans();
        $rootSpanData = $spans[0];
        $this->assertCount(1, $rootSpanData->timeEvents());
        $innerSpanData = $spans[1];
        $this->assertCount(1, $innerSpanData->timeEvents());
    }

    public function testAddsAnnotationToRootSpan()
    {
        $tracer = $this->makeTracer();
        $rootSpan = $tracer->startSpan(['name' => 'root']);
        $scope = $tracer->withSpan($rootSpan);
        $tracer->inSpan(['name' => 'inner'], function () use ($tracer, $rootSpan) {
            $tracer->addAnnotation('some root annotation', [
                'attributes' => ['foo' => 'bar'],
                'span' => $rootSpan
            ]);
        });
        $scope->close();

        $spans = $tracer->spans();
        $this->assertCount(2, $spans);

        $spanData = $spans[0];
        $this->assertEquals('root', $spanData->name());
        $this->assertCount(1, $spanData->timeEvents());
    }

    public function testAddsLinks()
    {
        $tracer = $this->makeTracer();
        $tracer->inSpan(['name' => 'root'], function () use ($tracer) {
            $tracer->addLink('traceid', 'spanid', ['attributes' => ['foo' => 'bar']]);
            $tracer->inSpan(['name' => 'inner'], function () use ($tracer) {
                $tracer->addLink('traceid', 'spanid');
            });
        });

        $spans = $tracer->spans();
        $rootSpanData = $spans[0];
        $this->assertCount(1, $rootSpanData->links());
        $innerSpanData = $spans[1];
        $this->assertCount(1, $innerSpanData->links());
    }

    public function testAddsLinkToRootSpan()
    {
        $tracer = $this->makeTracer();
        $rootSpan = $tracer->startSpan(['name' => 'root']);
        $scope = $tracer->withSpan($rootSpan);
        $tracer->inSpan(['name' => 'inner'], function () use ($tracer, $rootSpan) {
            $tracer->addLink('traceid', 'spanid', [
                'span' => $rootSpan
            ]);
        });
        $scope->close();

        $spans = $tracer->spans();
        $this->assertCount(2, $spans);
        $spanData = $spans[0];

        $this->assertEquals('root', $spanData->name());
        $this->assertCount(1, $spanData->links());
    }

    public function testAddMessageEvents()
    {
        $tracer = $this->makeTracer();
        $tracer->inSpan(['name' => 'root'], function () use ($tracer) {
            $tracer->addMessageEvent(MessageEvent::TYPE_SENT, 'id1', ['uncompressedSize' => 1234, 'compressedSize' => 1000]);
            $tracer->inSpan(['name' => 'inner'], function () use ($tracer) {
                $tracer->addMessageEvent(MessageEvent::TYPE_RECEIVED, 'id2');
            });
        });

        $spans = $tracer->spans();
        $rootSpanData = $spans[0];
        $this->assertCount(1, $rootSpanData->timeEvents());
        $innerSpanData = $spans[1];
        $this->assertCount(1, $innerSpanData->timeEvents());
    }

    public function testAddsMessageEventToRootSpan()
    {
        $tracer = $this->makeTracer();
        $rootSpan = $tracer->startSpan(['name' => 'root']);
        $scope = $tracer->withSpan($rootSpan);
        $tracer->inSpan(['name' => 'inner'], function () use ($tracer, $rootSpan) {
            $tracer->addMessageEvent(MessageEvent::TYPE_RECEIVED, 'id2', [
                'span' => $rootSpan
            ]);
        });
        $scope->close();

        $spans = $tracer->spans();
        $this->assertCount(2, $spans);
        $spanData = $spans[0];

        $this->assertEquals('root', $spanData->name());
        $this->assertCount(1, $spanData->timeEvents());
    }

    public function testInSpanSetsDefaultStartTime()
    {
        $tracer = $this->makeTracer();
        $tracer->inSpan(['name' => 'foo'], function () {
            // do nothing
        });

        $spans = $tracer->spans();
        $this->assertCount(1, $spans);
        $spanData = $spans[0];

        // #131 - Span should be initialized with current time, not the epoch.
        $this->assertNotEquals(0, $spanData->startTime()->getTimestamp());
    }

    public function testStackTraceShouldNotBeSet()
    {
        $tracer = $this->makeTracer();
        $tracer->inSpan(['name' => 'foo'], function () {
            // do nothing
        });

        $spans = $tracer->spans();
        $this->assertCount(1, $spans);
        $spanData = $spans[0];

        $this->assertIsArray( $spanData->stackTrace());
        $this->assertEmpty($spanData->stackTrace());
    }

    public function testAttributesShouldBeSet()
    {
        $tracer = $this->makeTracer();
        $tracer->inSpan(['name' => 'foo'], function () {
            // do nothing
        });

        $spans = $tracer->spans();
        $this->assertCount(1, $spans);
        $spanData = $spans[0];

        $this->assertIsArray( $spanData->attributes());
        $this->assertEmpty($spanData->attributes());
    }

    public function testLinksShouldBeSet()
    {
        $tracer = $this->makeTracer();
        $tracer->inSpan(['name' => 'foo'], function () {
            // do nothing
        });

        $spans = $tracer->spans();
        $this->assertCount(1, $spans);
        $spanData = $spans[0];

        $this->assertIsArray( $spanData->links());
        $this->assertEmpty($spanData->links());
    }

    public function testTimeEventsShouldBeSet()
    {
        $tracer = $this->makeTracer();
        $tracer->inSpan(['name' => 'foo'], function () {
            // do nothing
        });

        $spans = $tracer->spans();
        $this->assertCount(1, $spans);
        $spanData = $spans[0];

        $this->assertIsArray( $spanData->timeEvents());
        $this->assertEmpty($spanData->timeEvents());
    }

    public function testDefaultSpanKind()
    {
        $tracer = $this->makeTracer();
        $tracer->inSpan(['name' => 'foo'], function () {
            // do nothing
        });

        $spans = $tracer->spans();
        $this->assertCount(1, $spans);
        $spanData = $spans[0];

        $this->assertEquals(Span::KIND_UNSPECIFIED, $spanData->kind());
    }

    public function testSetSpanKind()
    {
        $tracer = $this->makeTracer();
        $tracer->inSpan(['name' => 'foo', 'kind' => Span::KIND_SERVER], function () {
            // do nothing
        });

        $spans = $tracer->spans();
        $this->assertCount(1, $spans);
        $spanData = $spans[0];

        $this->assertEquals(Span::KIND_SERVER, $spanData->kind());
    }

    public function testDefaultSameProcessAsParentSpan()
    {
        $tracer = $this->makeTracer();
        $tracer->inSpan(['name' => 'main'], function () use ($tracer) {
            $tracer->inSpan(['name' => 'inner'], function () {
                // do nothing
            });
        });

        $spans = $tracer->spans();
        $this->assertCount(2, $spans);
        $this->assertFalse($spans[0]->sameProcessAsParentSpan());
        $this->assertTrue($spans[1]->sameProcessAsParentSpan());
    }

    public function testSameProcessAsParentSpan()
    {
        $tracer = $this->makeTracer();
        $tracer->inSpan(['name' => 'main', 'sameProcessAsParentSpan' => true], function () use ($tracer) {
            $tracer->inSpan(['name' => 'inner', 'sameProcessAsParentSpan' => false], function () {
                // do nothing
            });
        });

        $spans = $tracer->spans();
        $this->assertCount(2, $spans);
        $this->assertTrue($spans[0]->sameProcessAsParentSpan());
        $this->assertFalse($spans[1]->sameProcessAsParentSpan());
    }

    private function assertEquivalentTimestamps($expected, $value)
    {
        $this->assertEquals((float)($expected->format('U.u')), (float)($value->format('U.u')), '', 0.000001);
    }

    public function testAttachesSpan()
    {
        $tracer = $this->makeTracer();
        $rootSpan = $tracer->startSpan(['name' => 'root']);
        $this->assertFalse($rootSpan->attached());
        $scope = $tracer->withSpan($rootSpan);
        $this->assertTrue($rootSpan->attached());
        $scope->close();
    }

    public function testSpanFlush()
    {
        $exporter = new NullExporter();
        $tracer = $this->makeTracer(null, $exporter, [
            'span_buffer_limit' => 5
        ]);

        for ($i=0; $i<=5; $i++) {
            $tracer->inSpan(['name' => 'root' . $i], function () {
            });
        }

        $count = count($tracer->spans());
        $this->assertEquals(1, $count);
    }
}

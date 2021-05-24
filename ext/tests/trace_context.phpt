--TEST--
OpenCensus Trace: Trace Context
--FILE--
<?php

require_once(__DIR__ . '/common.inc');

// 1: Sanity test a simple profile run
opencensus_trace_method("Foo", "context");
opencensus_trace_set_context("traceid", 1234, ['rzpctx-key1' => 'value1']);
$context = opencensus_trace_context();
if ($context instanceof OpenCensus\Trace\Ext\SpanContext) {
    echo "Context is a OpenCensus\\Trace\\Ext\\SpanContext.\n";
}
print_r($context->baggage());

$f = new Foo();
$context = $f->context();

if ($context instanceof OpenCensus\Trace\Ext\SpanContext) {
    echo "Nested context is a OpenCensus\\Trace\\Ext\\SpanContext.\n";
}
print_r($context->baggage());
$traces = opencensus_trace_list();

echo "Number of traces: " . count($traces) . "\n";
$span = $traces[0];

if ($span->spanId() == $context->spanId()) {
    echo "Span id matches context's span id.\n";
}

echo "Span parent id: {$span->parentSpanId()}\n";
echo "Context trace id: {$context->traceId()}\n";
?>
--EXPECT--
Context is a OpenCensus\Trace\Ext\SpanContext.
Array
(
    [rzpctx-key1] => value1
)
Nested context is a OpenCensus\Trace\Ext\SpanContext.
Array
(
    [rzpctx-key1] => value1
)
Number of traces: 1
Span id matches context's span id.
Span parent id: 1234
Context trace id: traceid

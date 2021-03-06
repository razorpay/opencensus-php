--TEST--
OpenCensus Trace: Basic Context Test
--FILE--
<?php

$res = opencensus_trace_set_context('traceid', 1234, ['rzpctx-key1' => 'value1']);
echo "Set context: ${res}\n";

$context = opencensus_trace_context();
$class = get_class($context);
echo "Context class: $class\n";
echo "Trace id: {$context->traceId()}\n";
echo "Span id: {$context->spanId()}\n";

print_r($context->baggage());
?>
--EXPECT--
Set context: 1
Context class: OpenCensus\Trace\Ext\SpanContext
Trace id: traceid
Span id: 1234
Array
(
    [rzpctx-key1] => value1
)

--TEST--
OpenCensus Trace: Test removing span by id
--FILE--
<?php

opencensus_trace_begin('root', ['spanId' => 123]);
opencensus_trace_finish();

$traces = opencensus_trace_list();
echo "Number of traces: " . count($traces) . "\n";
$span = $traces[0];
$id = $span->spanId();
opencensus_trace_remove_span($id);
$count = opencensus_trace_count();
echo "Number of traces: " . $count . "\n";
?>
--EXPECTF--
Number of traces: 1
Number of traces: 0

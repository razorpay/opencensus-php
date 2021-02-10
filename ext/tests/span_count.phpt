--TEST--
OpenCensus Trace: Test span count method
--FILE--
<?php

opencensus_trace_begin('root', ['spanId' => 123]);
opencensus_trace_finish();

$count = opencensus_trace_count();
echo "Number of traces: " . $count . "\n";
?>
--EXPECTF--
Number of traces: 1

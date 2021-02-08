--TEST--
OpenCensus Trace: Test span count method
--FILE--
<?php

opencensus_trace_begin('root', ['kind' => null]);
opencensus_trace_finish();

$count = opencensus_trace_count();
echo "Number of traces: " . $count . "\n";
?>
--EXPECTF--
Warning: opencensus_trace_begin(): Provided kind should be a string in %s on line %d
Number of traces: 1

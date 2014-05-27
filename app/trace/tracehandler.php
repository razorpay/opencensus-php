<?php

namespace Trace;

use Trace\TraceWriter;

class TraceHandler
{

    public function fire($job, $trace)
    {
        $writer = new TraceWriter();

        $writer->addRecord($trace['level'], $trace['message'], $trace['context']);

        $job->delete();
    }
}
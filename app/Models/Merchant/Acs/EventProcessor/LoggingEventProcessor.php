<?php


namespace RZP\Models\Merchant\Acs\EventProcessor;

use App;
use RZP\Trace\TraceCode;

class LoggingEventProcessor implements IEventProcessor
{
    protected $trace;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];
    }

    function ShouldProcess(array $input): bool
    {
        return true;
    }

    function Process(array $input)
    {
        $this->trace->info(TraceCode::EVENT_PROCESSED_BY_LOGGING_EVENT_PROCESSOR, $input);
    }
}

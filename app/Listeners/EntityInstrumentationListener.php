<?php

namespace RZP\Listeners;

use App;

use Razorpay\Trace\Logger as Trace;
use RZP\Events\EntityInstrumentationEvent;
use RZP\Trace\TraceCode;

class EntityInstrumentationListener
{
    protected $app;

    /**
     * @var Trace
     */
    protected $trace;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->trace = $this->app['trace'];
    }

    /**
     * Handle the event
     *
     * @param EntityInstrumentationEvent $event
     * @return void
     */
    public function handle(EntityInstrumentationEvent $event)
    {
        try
        {
            $this->trace->count($event->eventName, $event->dimensions);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::WARNING,
                TraceCode::INSTRUMENT_ENTITY_EVENT_ERROR,
                [
                    'event' => $event->eventName,
                    'dimensions' => $event->dimensions
                ]
            );
        }
    }
}

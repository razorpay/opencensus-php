<?php

namespace RZP\Diag\Traits;

use Carbon\Carbon;
use Razorpay\Trace\Logger;
use RZP\Constants\Timezone;
use RZP\Trace\TraceCode;
use RZP\Diag\Event\AsyncTokenisationEvent as ATE;

trait AsyncTokenisationEvent
{
    public function trackAsyncTokenisationEvent(array $eventData, array $properties = []): void
    {
        try
        {
            $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

            $properties = array_merge($properties, ['timestamp' => $timestamp]);

            $this->trackEvent(ATE::EVENT_TYPE, ATE::EVENT_VERSION, $eventData, $properties);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex, Logger::ERROR, TraceCode::ASYNC_TOKENISATION_EVENT_FAILURE);
        }
    }
}

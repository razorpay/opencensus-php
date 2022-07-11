<?php

namespace RZP\Diag\Traits;

use Carbon\Carbon;
use Razorpay\Trace\Logger;
use RZP\Constants\Timezone;
use RZP\Trace\TraceCode;
use RZP\Diag\Event\TokenisationEvent as TE;

trait TokenisationEvent
{
    public function trackTokenisationEvent(array $eventData, array $properties = []): void
    {
        try
        {
            $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

            $properties = array_merge($properties, ['timestamp' => $timestamp]);

            $this->trackEvent(TE::EVENT_TYPE, TE::EVENT_VERSION, $eventData, $properties);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex, Logger::ERROR, TraceCode::TOKENISATION_EVENT_FAILURE);
        }
    }
}

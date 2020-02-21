<?php

namespace RZP\Jobs;

use RZP\Jobs\Job;
use RZP\Models\Event;
use RZP\Models\Merchant;

/**
 * This is a fallback queued job and the handler just calls stork's processEvent().
 * Also see Webhook/Stork's processEventSafe().
 */
class WebhookEvent extends Job
{
    /**
     * Merchant who owns the event.
     * @var Event\Entity
     */
    public $merchant;

    /**
     * Attributes of event.
     *
     * It is not possible to serialize Event\Entity itself and send over queue
     * because that is not a real entity and during unserialize(during handle)
     * it fails in laravel worker's code. And hence need to pass the arrayed
     * event attributes and construct the event entity again on handle. And this
     * is why need to pass Merchant\Entity explicitly and associate with event
     * in handle().
     *
     * @var array
     */
    public $eventAttrs;

    public function __construct(string $mode, Merchant\Entity $merchant, array $eventAttrs)
    {
        parent::__construct($mode);

        $this->merchant   = $merchant;
        $this->eventAttrs = $eventAttrs;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $event = new Event\Entity($this->eventAttrs);
            $event->merchant()->associate($this->merchant);

            (new Merchant\Webhook\Stork)->processEvent($event, $this->mode);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            // For this job there are no max retries but there is exponential
            // backoff in terms of when to process next which can maximum be 15m.
            // 2.5s, 5s, 10s, 20s, 40s, 80s, 160s, 320s, 640s, 900s, 900s, 900s ..
            $this->release(min(pow(2, $this->attempts())*5/2, 900));
        }
    }
}

<?php

namespace RZP\Jobs;

use RZP\Jobs\Job;
use RZP\Models\Event;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Models\Base\UniqueIdEntity;

/**
 * This is a fallback queued job and the handler just calls stork's processEvent().
 * Also see WebhookV2/Stork's processEventSafe().
 */
class WebhookEvent extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 25;

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
     * it fails in laravel worker's code. Worker code does db query on model's
     * table with given id. And hence need to pass the arrayed event attributes
     * and construct the event entity again on handle. And this is why need to
     * pass Merchant\Entity explicitly and associate with event in handle().
     *
     * @var array
     */
    public $eventAttrs;

    public $product;

    public $ownerType;

    public function __construct(string $mode,
                                Merchant\Entity $merchant,
                                array $eventAttrs,
                                string $product = Product::PRIMARY,
                                string $ownerType = 'merchant')
    {
        parent::__construct($mode);

        $this->merchant   = $merchant;
        $this->eventAttrs = $eventAttrs;
        $this->product    = $product;
        $this->ownerType  = $ownerType;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $event = new Event\Entity($this->eventAttrs);
            if ((isset($this->eventAttrs['id']) === true) &&
                (UniqueIdEntity::verifyUniqueId($this->eventAttrs['id'], false) === true))
            {
                $event->setId($this->eventAttrs['id']);
            }
            $event->merchant()->associate($this->merchant);

            $this->trace->info(TraceCode::WEBHOOK_EVENT_JOB_RECEIVED, $event->toArrayPublic());

            (new Merchant\WebhookV2\Stork($this->mode, $this->product))->processEvent($event, $this->ownerType);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            if ($this->attempts() < self::MAX_ALLOWED_ATTEMPTS)
            {
                // Uses exponential backoff on when to process next which can be 15m
                // maximum, releases the job maximum 25 times over a duration of ~4h.
                // 2.5s, 5s, 10s, 20s, 40s, 80s, 160s, 320s, 640s, 900s, 900s, 900s ..
                $this->release(min(pow(2, $this->attempts())*5/2, 900));
            }
        }
    }
}

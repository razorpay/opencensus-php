<?php

namespace RZP\Listeners;

use RZP\Events\DifferEvent;
use RZP\Models\Workflow\Action\Differ;
use Illuminate\Foundation\Bus\DispatchesJobs;

class DifferEventListener
{
    use DispatchesJobs;

    /**
     * Handle the event.
     *
     * @param  DifferEvent  $event
     * @return void
     */
    public function handle(DifferEvent $event)
    {
        (new Differ\Core)->saveToES($event->event);
    }
}

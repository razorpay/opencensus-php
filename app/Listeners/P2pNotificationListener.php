<?php

namespace RZP\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use Exception;
use RZP\Events\P2p;
use RZP\Models\P2p\Transaction;

class P2pNotificationListener extends P2pListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        parent::handle($event);

        $payload = $this->event->getNotificationPayload();

        if (empty($payload) === true)
        {
            return;
        }

        $raven = $this->app->get('raven');

        try
        {
            $raven->sendSms($payload, false);
        }
        catch (Exception $e)
        {
            $this->app['trace']->traceException($e);
        }
    }
}

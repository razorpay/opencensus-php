<?php

namespace RZP\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Jobs;
use RZP\Models\Event;
use RZP\Trace\TraceCode;
use RZP\Models\P2p\Transaction;

class P2pReminderListener extends P2pListener
{
    const REMINDER_NAMESPACE = 'p2p';

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        parent::handle($event);

        $payload = $event->getReminderPayload();

        if (empty($payload))
        {
            return;
        }

        $this->processPayload($payload);
    }

    protected function processPayload($payload)
    {
        $params = $payload['params'];

        $path = 'p2p/reminders/send/%s/%s/%s/%s';

        $callbackUrl = sprintf($path, $params['handle'], $params['entity'], $params['id'], $params['action']);

        $merchantId = $this->getMerchant()->getId();

        try {
            $this->app['reminders']->createReminder([
                'namespace'         => self::REMINDER_NAMESPACE,
                'entity_id'         => $payload['entity']['id'],
                'entity_type'       => $payload['entity']['type'],
                'reminder_data'     => $payload['reminder_data'],
                'callback_url'      => $callbackUrl,
            ], $merchantId);
        }
        catch (\Exception $e)
        {
            $this->app['trace']->traceException($e);
        }
    }
}

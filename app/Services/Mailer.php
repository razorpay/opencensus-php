<?php

namespace RZP\Services;

use Illuminate\Mail\Mailer as LaravelMailer;

class Mailer extends LaravelMailer
{
    protected $serviceName = 'mailer';

    public function __construct(Factory $views, Swift_Mailer $swift,
        Dispatcher $events = null, $serviceName = 'mailer')
    {
        parent::__construct($views, $swift, $events);

        $this->serviceName = $serviceName;
    }

    public function queue($view, array $data, $callback, $queue = null)
    {
        $callback = $this->buildQueueCallable($callback);

        return $this->queue->push(
            $this->serviceName . '@handleQueuedMessage',
            compact('view', 'data', 'callback'), $queue
        );
    }
}

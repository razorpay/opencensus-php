<?php

namespace RZP\Jobs\Extended;

use Razorpay\Trace\Logger as Trace;

/**
 * Overridden: Just before destruction, sets proper connection and queue name.
 *
 * Additional api specific feature:
 * Routes a job to specific queue basis few additional arguments.
 *
 * E.g.
 * \RZP\Jobs\Webhook::dispatch($data)->using(['payment.authorized']): Will dispatch the job over queue connection &
 * queue name as defined in config/queue.php. It will look for webhook.connection to pick the queue connection &
 * webhook.payment.authorized for queue name.
 */
class PendingDispatch extends \Illuminate\Foundation\Bus\PendingDispatch
{
    /**
     * @var null|string
     */
    protected $queueConfigKey;

    /**
     * @var array
     */
    protected $queueConfigExtra = [];

    /**
     * Overrides
     * {@inheritDoc}
     */
    public function __destruct()
    {
        try
        {
            $this->setQueueAndConnectionFromConfig();

            parent::__destruct();
        }
        catch (\Throwable $e)
        {
            app('trace')->traceException($e, Trace::CRITICAL);
        }
    }

    /**
     * @param  array       $extra - This is first argument as it is almost the most number of use case
     * @param  string|null $key
     * @return PendingDispatch
     */
    public function using(array $extra = [], string $key = null): PendingDispatch
    {
        $this->queueConfigExtra = $extra;
        $this->queueConfigKey   = $key;

        return $this;
    }

    /**
     * Sets job's queue connection and queue name as per configuration
     */
    protected function setQueueAndConnectionFromConfig()
    {
        // If queue routing is mocked, push everything to default queue; Used in local environment;
        $this->queueRouteMock = config('queue.mock_route', false);
        $this->queueConfigKey = $this->queueConfigKey ?: $this->job->getQueueConfigKey();

        if (($this->queueRouteMock === true) or (empty($this->queueConfigKey) === true))
        {
            return;
        }

        $this->job->onConnection($this->getConnection());
        $this->job->onQueue($this->getQueue());
    }

    protected function getConnection(): string
    {
        $key     = "queue.{$this->queueConfigKey}.connection";
        $default = config('queue.default');

        return config($key, $default);
    }

    protected function getQueue(): string
    {
        $mode = app('rzp.mode');
        $key  = "queue.{$this->queueConfigKey}.{$mode}";

        // We flatten with the extra parameter to finally get the queue name.
        // Ref: config/queue.php
        array_unshift($this->queueConfigExtra, $key);
        $key = implode('.', $this->queueConfigExtra);

        $default = config('queue.connections.sqs.queue');

        return config($key, $default);
    }
}

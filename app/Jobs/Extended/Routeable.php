<?php

namespace RZP\Jobs\Extended;

/**
 * Routes a job to specific queue basis few additional arguments.
 *
 * E.g.
 * \RZP\Jobs\Webhook::dispatch($data)->via('payment', ['dispute.created']): Will dispatch the job over queue connection
 * & queue name as defined in config/queue_route.php. It will look for webhook[connection] to pick the queue connection
 * & webhook[payment][dispute][created] for queue name.
 */
trait Routeable
{
    /**
     * @var null|string
     */
    protected $route;

    /**
     * @var array
     */
    protected $extra = [];

    public function route(string $route = null): PendingDispatch
    {
        $this->route = $route;

        return $this;
    }

    public function extra(array $extra = []): PendingDispatch
    {
        $this->extra = $extra;

        return $this;
    }

    public function via(string $route = null, array $extra = []): PendingDispatch
    {
        $this->route = $key;
        $this->extra = $extra;

        return $this;
    }

    /**
     * Sets job's queue connection and queue name as per configuration using $route & $extra parameters.
     */
    protected function routeJobPerConfig()
    {
        // If route was not set, use job's default route.
        $this->route = $this->route ?: $this->job->getRoute();

        // If route not available, no need to use routing logic.
        if (empty($this->route) === true)
        {
            return;
        }

        $connection = $this->getConnection();
        $queue      = $this->getQueue();

        $this->job->onConnection($connection)->allOnConnection($connection);
        $this->job->onQueue($queue)->allOnQueue($queue);
    }

    protected function getConnection(): string
    {
        $key     = "queue_route.{$this->route}.connection";
        $default = config('queue.default');

        return config($key, $default);
    }

    protected function getQueue(): string
    {
        $mode = app('rzp.mode');
        $key  = "queue_route.{$this->route}.{$mode}";

        // We flatten with the extra parameter to finally get the queue name.
        // Ref: config/queue_route.php
        array_unshift($this->extra, $key);
        $key = implode('.', $this->extra);

        return config($key);
    }
}

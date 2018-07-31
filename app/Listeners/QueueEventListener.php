<?php

namespace RZP\Listeners;

use Illuminate\Queue\Events as QueueEvents;

use RZP\Constants\Metric;

class QueueEventListener
{
    // Enhancement: Figure out a way to log time taken during processing for each job & push that metric as histogram

    /**
     * @var QueueEvents\Looping|
     *      QueueEvents\JobFailed|
     *      QueueEvents\JobProcessed|
     *      QueueEvents\JobProcessing|
     *      QueueEvents\JobExceptionOccurred
     */
    protected $event;

    public function handle($event)
    {
        $this->event = $event;

        app('trace')->count($this->getMetricName(), $this->getMetricDimensions());
    }

    protected function getMetricName(): string
    {
        switch (true)
        {
            case $this->event instanceof QueueEvents\Looping:
                return Metric::ASYNC_JOBS_RECEIVING_TOTAL;

            case $this->event instanceof QueueEvents\JobFailed:
                return Metric::ASYNC_JOBS_ERRORS_TOTAL;

            case $this->event instanceof QueueEvents\JobProcessed:
                return Metric::ASYNC_JOBS_PROCESSED_TOTAL;

            case $this->event instanceof QueueEvents\JobProcessing:
                return Metric::ASYNC_JOBS_RECEIVED_TOTAL;

            case $this->event instanceof QueueEvents\JobExceptionOccurred:
                return Metric::ASYNC_JOBS_ERRORS_TOTAL;
        }
    }

    protected function getMetricDimensions(): array
    {
        return [
            Metric::LABEL_ASYNC_JOB_CONNECTION => $this->event->job->getConnectionName(),
            Metric::LABEL_ASYNC_JOB_QUEUE      => $this->event->job->getQueue(),

            //
            // We do str_replace \ with _ to ease querying, otherwise while querying
            // need to backslash which is difficult from Grafana dashboard.
            //
            Metric::LABEL_ASYNC_JOB_NAME       => str_replace('\\', '_', $this->event->job->resolveName()),
        ];
    }
}

<?php

namespace RZP\Listeners;

use Metrics;
use Illuminate\Queue\Events\Looping;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobExceptionOccurred;

use RZP\Constants\Metric;

class QueueEventListener
{
    // Enhancement: Figure out a way to log time taken during processing for each job & push that metric as histogram

    /**
     * @var Looping|JobFailed|JobProcessed|JobProcessing|JobExceptionOccurred
     */
    protected $event;

    public function handle($event)
    {
        $this->event = $event;

        Metrics::count($this->getMetricName(), 1, $this->getMetricDimensions());
    }

    protected function getMetricName(): string
    {
        switch (true)
        {
            case $this->event instanceof Looping:
                return Metric::ASYNC_JOBS_RECIEVING_TOTAL;

            case $this->event instanceof JobFailed:
                return Metric::ASYNC_JOBS_ERRORS_TOTAL;

            case $this->event instanceof JobProcessed:
                return Metric::ASYNC_JOBS_PROCESSED_TOTAL;

            case $this->event instanceof JobProcessing:
                return Metric::ASYNC_JOBS_RECIEVED_TOTAL;

            case $this->event instanceof JobExceptionOccurred:
                return Metric::ASYNC_JOBS_ERRORS_TOTAL;
        }
    }

    protected function getMetricDimensions(): array
    {
        return [
            Metric::LABEL_ASYNC_JOB_CONNECTION => $this->event->job->getConnectionName(),
            Metric::LABEL_ASYNC_JOB_QUEUE      => $this->event->job->getQueue(),
            Metric::LABEL_ASYNC_JOB_NAME       => $this->event->job->resolveName(),
        ];
    }
}

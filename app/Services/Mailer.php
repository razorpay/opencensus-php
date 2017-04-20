<?php

namespace RZP\Services;

use App;
use Illuminate\Mail\Mailer as LaravelMailer;

class Mailer extends LaravelMailer
{
    /**
     * Add Extra logger on top of laravel Mailer
     */

    const MAX_ALLOWED_ATTEMPTS = 5;

    const RELEASE_WAIT_SECS = 120;

    public function handleQueuedMessage($job, $data)
    {
        $app = App::getFacadeRoot();

        $trace = $app['trace'];

        $trace->debug(
            TraceCode::MAILER_JOB_RECEIVED,
            [
                'job_attempts'   => $job->attempts(),
            ]
        );

        try
        {
            parent::handleQueuedMessage($job, $data);
        }
        catch (\Throwable $e)
        {
            if ($this->attempts() > self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->delete();
            }
            else
            {
                $this->release(self::RELEASE_WAIT_SECS);
            }

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MAILER_JOB_ERROR,
                [
                    'job_attempts'   => $job->attempts(),
                ]
            );
        }
    }
}

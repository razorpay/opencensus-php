<?php

namespace RZP\Services;

use App;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
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
                'action'         => 'start',
                'job_attempts'   => $job->attempts(),
            ]
        );

        try
        {
            parent::handleQueuedMessage($job, $data);
        }
        catch (\Throwable $e)
        {
            if ($job->attempts() > self::MAX_ALLOWED_ATTEMPTS)
            {
                $job->delete();
            }
            else
            {
                $job->release(self::RELEASE_WAIT_SECS);
            }

            $trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::MAILER_JOB_ERROR,
                [
                    'job_attempts'   => $job->attempts(),
                ]
            );
        }

        $trace->debug(
            TraceCode::MAILER_JOB_RECEIVED,
            [
                'action'         => 'end',
                'job_attempts'   => $job->attempts(),
            ]
        );
    }
}

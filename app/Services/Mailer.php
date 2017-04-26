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

    #TODO : decrease the number of attempts after daily files are fixed
    const MAX_ALLOWED_ATTEMPTS = 50;

    const RELEASE_WAIT_SECS = 120;

    public function handleQueuedMessage($job, $data)
    {
        $app = App::getFacadeRoot();

        $trace = $app['trace'];

        $mailData = $data['data'] ?? null;

        $mailView = $data['view'] ?? null;

        $trace->debug(
            TraceCode::MAILER_JOB_RECEIVED,
            [
                'action'         => 'start',
                'job_attempts'   => $job->attempts(),
                'data'           => $mailData,
                'view'           => $mailView
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
                    'data'           => $mailData,
                    'view'           => $mailView
                ]
            );
        }

        $trace->debug(
            TraceCode::MAILER_JOB_RECEIVED,
            [
                'action'         => 'end',
                'job_attempts'   => $job->attempts(),
                'data'           => $mailData,
                'view'           => $mailView
            ]
        );
    }
}

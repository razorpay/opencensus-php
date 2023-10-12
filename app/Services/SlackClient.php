<?php

namespace RZP\Services;

use Monolog\Logger;
use Razorpay\Slack\Client;

use RZP\Trace\TraceCode;

class SlackClient extends Client
{
    public function fire($job, array $data)
    {
        app('trace')->info(TraceCode::SLACK_PUSH_MESSAGE_INIT, [
            'data'        => $data,
            'attempts'    => $job->attempts(),
            'max_retries' => $data['metadata']['num_retries']
        ]);

        if ($job->attempts() >= $data['metadata']['num_retries'])
        {
            $job->delete();
        }
        else
        {
            try
            {
                $this->sendPayload($data);

                $job->delete();
            }
            catch (\Throwable $e)
            {
                app('trace')->traceException($e, Logger::ERROR, TraceCode::SLACK_PUSH_MESSAGE_FAILURE, [
                    'data' => $data,
                ]);

                $job->release(self::RELEASE_WAIT_TIMEOUT);
            }
        }
    }
}

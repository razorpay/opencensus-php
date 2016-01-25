<?php

namespace Models\Merchant\Webhook;

use Requests;
use Trace\TraceCode;

class Inferno
{
    protected $trace;

    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->trace = $app['trace'];
    }

    public function fire($job, $data)
    {
        $repo = new Repository;

        $mode = $data['mode'];

        $webhook = $repo->connection($mode)->find($data['webhook_id']);

        if ($webhook === null)
        {
            $this->trace->info(
                TraceCode::WEBHOOK_FIRING,
                ['data' => $data]);

            $job->delete();
        }

        if ($webhook->isActive() === false)
        {
            $job->delete();

            return;
        }

        $this->trace->info(
            TraceCode::WEBHOOK_FIRING,
            [$data]);

        $request = $this->getRequestArray($data, $webhook);

        $response = $this->makeRequest($request);

        if ($response->success === false)
        {
            $this->trace->info(
                TraceCode::WEBHOOK_RESPONSE_FAILURE,
                [
                    'webhook' => $webhook->getId(),
                    'response_code' => $response->status_code
                ]);

            // It's a failure, increment failure count.
            $repo->bumpFailureCount($webhook);

            if (($webhook->isActive() === false) or
                ($job->attempts() >= 3))
            {
                $this->trace->info(
                    TraceCode::WEBHOOK_DEACTIVATE,
                    [
                        'webhook' => $webhook->getId(),
                        'response_code' => $response->status_code
                    ]);

                // Webhook is now inactive
                // So let's just delete the job
                $job->delete();
            }
            else
            {
                // Attempt again after 1 hour
                $job->release(3600);
            }
        }
        else
        {
            if ($webhook->getFailureCount() !== 0)
            {
                $repo->resetFailureCount($webhook);
            }

            $this->trace->info(
                TraceCode::WEBHOOK_FIRED,
                [
                    'webhook' => $webhook->getId(),
                    'response_code' => $response->status_code,
                ]);

            $job->delete();
        }
    }

    public function makeRequest($request)
    {
        $method = $request['method'];

        $response = Requests::$method(
                    $request['url'],
                    $request['header'],
                    $request['content'],
                    $request['options']);

        return $response;
    }

    protected function getRequestArray($event, $webhook)
    {
        $request = array(
            'url' => $webhook->getUrl(),
            'method' => 'post',
            'content' => $event);

        $request['header'] = [
            'User-Agent' => 'Razorpay-Webhook/v1',
            'Content-Type' => 'application/json',
        ];

        $request['options'] = ['timeout' => 5];

        return $request;
    }
}

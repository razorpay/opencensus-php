<?php

namespace Models\Merchant\Webhook;

use Requests;
use Trace\TraceCode;

class Inferno
{
    protected $job;

    protected $trace;

    protected $repo;

    public function __construct()
    {
        $app = \App::getFacadeRoot();

        $this->trace = $app['trace'];

        $this->repo = new Repository;
    }

    public function fire($job, $data)
    {
        $this->job = $job;
        $this->repo = new Repository;

        $repo = $this->repo;

        $webhook = $this->getWebhook($data);

        if ($webhook->isActive() === false)
        {
            $job->delete();

            return;
        }

        $request = $this->getRequestArray($data['event'], $webhook);

        $this->trace->info(
            TraceCode::WEBHOOK_FIRING,
            $request);

        $timeout = false;

        try
        {
            $response = $this->makeRequest($request);
        }
        catch (\Requests_Exception $e)
        {
            //
            // Some error occurred.
            // Check that whether the gateway response timed out.
            // Mostly it should be gateway timeout only
            //
            if (\Gateway\Utility::checkTimeout($e))
            {
                $timeout = true;

                $this->trace->info(
                    TraceCode::WEBHOOK_RESPONSE_FAILURE,
                    [
                        'webhook' => $webhook->getId(),
                        'exception' => $e->getMessage(),
                    ]);
            }
            else
            {
                throw $e;
            }
        }

        if ($timeout === true)
        {
            $this->webhookBumpFailureCount($webhook);
        }
        else if ($response->success === false)
        {
            $this->trace->info(
                TraceCode::WEBHOOK_RESPONSE_FAILURE,
                [
                    'webhook' => $webhook->getId(),
                    'response_code' => $response->status_code
                ]);

            $this->webhookBumpFailureCount($webhook);
        }
        else
        {
            $this->webhookSuccessfullyFired($webhook, $response);
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

        $request['options'] = ['timeout' => 10];

        return $request;
    }

    protected function webhookSuccessfullyFired($webhook, $response)
    {
        if ($webhook->getFailureCount() !== 0)
        {
            $this->repo->resetFailureCount($webhook);
        }

        $this->trace->info(
            TraceCode::WEBHOOK_FIRED,
            [
                'webhook' => $webhook->getId(),
                'response_code' => $response->status_code,
            ]);

        $this->job->delete();
    }

    protected function webhookBumpFailureCount($webhook)
    {
        $job = $this->job;

        // It's a failure, increment failure count.
        $this->repo->bumpFailureCount($webhook);

        if (($webhook->isActive() === false) or
            ($job->attempts() >= 3))
        {
            $this->trace->info(
                TraceCode::WEBHOOK_DEACTIVATE,
                ['webhook' => $webhook->getId()]);

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

    protected function getWebhook($data)
    {
        $mode = $data['mode'];

        $webhook = $this->repo
                        ->connection($mode)
                        ->find($data['webhook_id']);

        if ($webhook === null)
        {
            $this->trace->info(
                TraceCode::WEBHOOK_FIRING,
                ['data' => $data]);

            $this->job->delete();
        }

        return $webhook;
    }
}

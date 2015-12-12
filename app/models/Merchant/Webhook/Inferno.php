<?php

namespace Models\Merchant\Webhook;

use Requests;

class Inferno
{
    public function fire($job, $data)
    {
        $repo = new Repository;

        $webhook = $repo->find($data['webhook_id']);

        if ($webhook->isActive() === false)
        {
            $job->delete();

            return;
        }

        $request = $this->getRequestArray($data, $webhook);

        $response = $this->makeRequest($request);

        if ($response->success === false)
        {
            // It's a failure, increment failure count.
            $repo->bumpFailureCount($webhook);

            if (($webhook->isActive() === false) or
                ($job->attempts() >= 3))
            {
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

        $request['options'] = ['timeout' => 15];

        return $request;
    }
}

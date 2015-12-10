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

        if ($this->isResponseStatusCodeSuccess($response->status_code))
        {
            $repo->incrementFailureCount($webhook);

            if (($webhook->isActive() === false) or
                ($job->attempts() > 3))
            {
                $job->delete();
            }
            else
            {
                // Attempt again after 1 hour
                $job->release(3600);
            }
        }
        else if ($webhook->getFailureCount() !== 0)
        {
            $webhook->resetFailureCount();

            $job->delete();
        }
    }

    protected function makeRequest($request)
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

        $request['header'] = [];
        $request['options'] = [];

        return $request;
    }

    protected function isResponseStatusCodeSuccess($statusCode)
    {
        return (substr($statusCode, 0, 1) !== '2');
    }
}

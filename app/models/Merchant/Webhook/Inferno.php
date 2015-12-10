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

        $request = $this->getRequestArray($data);

        $response = $this->makeRequest($request);

        if (substr($response->status_code, 0, 1) !== '2')
        {
            $repo->incrementFailureCount($webhook);
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

    protected function getRequestArray($data)
    {
        $request = array(
            'url' => $data['url'],
            'method' => 'post',
            'content' => $data['event']);

        $request['header'] = [];
        $request['options'] = [];

        return $request;
    }
}

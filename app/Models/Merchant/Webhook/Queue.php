<?php

namespace RZP\Models\Merchant\Webhook;

class Queue
{
    public function fire($job, $data)
    {
        $app = \App::getFacadeRoot();

        $data = json_decode($data, true);

        $app['webhook.inferno']->fire($job, $data);
    }
}

<?php

namespace Models\Merchant\Webhook;

use Requests;

class Queue
{
    public function fire($job, $data)
    {
        $app = \App::getFacadeRoot();

        $app['webhook.inferno']->fire($job, $data);
    }
}
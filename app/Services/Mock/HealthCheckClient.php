<?php

namespace RZP\Services\Mock;

use \WpOrg\Requests\Response as Response;
use RZP\Services\HealthCheckClient as BaseHealthCheckClient;

class HealthCheckClient extends BaseHealthCheckClient
{
    protected function getResponse($request)
    {
        $response = new Response();

        $response->url = $this->url;

        $response->headers = ['Content-Type' => 'application/json'];

        switch ($this->url)
        {
            case 'http://www.validUrl.com':
                $response->status_code = 200;
                $response->success = true;
                break;

            case 'http://www.invalidUrl.com':
                throw new \WpOrg\Requests\Exception('some error due to gateway downtime', 'curlerror');
                break;

            case 'http://www.ping-giving-500.com':
                $response->status_code = 500;
                break;
        }

        return $response;
    }
}

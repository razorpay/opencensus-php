<?php

namespace RZP\Services;

use RZP\Trace\TraceCode;

class Doppler
{
    protected $app;

    protected $mock;

    protected $sns;

    protected $config;

    const SNS_CLIENT = 'doppler';

    public function __construct($app)
    {
        $this->app = $app;

        $this->config = $app['config']->get('applications.doppler');

        $this->mock = $this->config['mock'];

        $this->sns = $app['sns'];

    }

    // sends event to doppler's topic
    public function sendFeedback($eventData)
    {
        $this->sendDopplerEventRequest($eventData);
    }

    /**
     * Dispatch event data to doppler service via SNS.
     *
     * @param array $eventData
     */
    protected function sendDopplerEventRequest(array $eventData)
    {
        try
        {
            $this->sns->publish(json_encode($eventData), self::SNS_CLIENT);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::DOPPLER_SERVICE_SNS_PUBLISH_FAILED, $eventData);
        }
    }
}

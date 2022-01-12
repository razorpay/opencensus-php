<?php


namespace RZP\Services\Segment;

class XSegmentClient extends SegmentAnalyticsClient
{

    public function __construct()
    {
        parent::__construct();

        $this->config = $this->app['config']->get('services.x-segment');
    }
}

<?php

namespace RZP\lib;

use App;
use Exception;
use RZP\Http\RequestHeader;

class AwsTraceIdExtractor
{
    protected $app;

    function __construct()
    {
        $this->app = App::getFacadeRoot();
    }

    public function getAwsTraceId(): string
    {
        try {
            return $this->app['request']->headers?->get(RequestHeader::X_AMAZON_TRACE_ID) ?? 'Root=1-' . $this->app['request']->getTaskId();

        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
    }
}

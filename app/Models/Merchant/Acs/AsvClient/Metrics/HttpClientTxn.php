<?php

namespace RZP\Models\Merchant\Acs\AsvClient\Metrics;

use App;
use Razorpay\Trace\Logger;
use RZP\Constants\Metric;


class HttpClientTxn
{

    /** @var Logger */
    protected $trace;
    protected $route;
    protected $startTime;

    function __construct(string $route)
    {
        $app = App::getFacadeRoot();
        $this->trace = $app['trace'];
        $this->route = $route;
        $this->startTime = millitime();
    }

    public function start()
    {
        $this->trace->count(Metric:: ASV_HTTP_CLIENT_REQUEST_TOTAL,
            [
                Metric::LABEL_ROUTE_NAME => $this->route
            ]
        );
    }

    public function end(bool $isSuccess, string $errorCode)
    {
        $endTime = millitime();
        $isSuccessStr = $isSuccess ? 'true' : 'false';

        $this->trace->count(Metric:: ASV_HTTP_CLIENT_RESPONSE_TOTAL,
            [
                Metric::LABEL_ROUTE_NAME => $this->route,
                Metric::LABEL_IS_SUCCESS => $isSuccessStr,
                Metric::LABEL_ERROR_CODE => $errorCode
            ]
        );

        $this->trace->histogram(Metric::ASV_HTTP_CLIENT_RESPONSE_DURATION_MS, $endTime - $this->startTime,
            [
                Metric::LABEL_ROUTE_NAME => $this->route,
                Metric::LABEL_IS_SUCCESS => $isSuccessStr,
                Metric::LABEL_ERROR_CODE => $errorCode
            ]
        );
    }
}

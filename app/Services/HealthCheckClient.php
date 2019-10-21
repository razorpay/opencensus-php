<?php

namespace RZP\Services;

use App;
use Requests;
use Razorpay\Trace\Logger as Trace;
use RZP\Gateway\Hitachi;

class HealthCheckClient
{
    const URL           = 'url';

    const HEAD          = 'head';

    const HTTP_STATUS   = 'http_status';

    const HEADERS       = 'headers';

    const GATEWAY_STATUS_CODE = 'gateway_status_code';

    const ERROR_MESSAGE = 'error_message';

    const METHOD        = 'method';

    protected $url;

    /** @var Trace */
    protected $trace;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];
    }

    public function check($request)
    {
        $this->url = $request['url'];

        $output = [
            self::HTTP_STATUS               => 500,
            self::HEADERS                   => [],
            self::ERROR_MESSAGE             => null,
        ];

        try
        {
            $response = $this->getResponse($request);

            $output[self::HTTP_STATUS] = $response->status_code;
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);

            $output[self::ERROR_MESSAGE] = $e->getMessage();

            $output[self::HTTP_STATUS] = 504;
        }

        return $output;
    }

    protected function getResponse($request)
    {
        // If it is Hitachi status endpoint then check if proxy is required
        if ($request['url'] === Hitachi\Url::LIVE)
        {
            $request = (new Hitachi\Gateway)->getStatusRequest($request);
        }

        return Requests::request(
            $request['url'],
            $request['headers'] ?? [],
            $request['content'] ?? [],
            $request['method'] ?? 'HEAD',
            $request['options'] ?? ['timeout' => 60, 'verify' => false]);
    }
}

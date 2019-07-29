<?php

namespace RZP\Services;

use App;
use RZP\Exception;
use RZP\Trace\TraceCode;

class NonBlockingHttp
{
    const DEFAULT_TIMEOUT   = '50';

    protected $trace;

    protected $timeout;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->timeout = $app['config']->get('applications.non_blocking_http.timeout') ?? self::DEFAULT_TIMEOUT;
    }

    public function postRequest(string $url, $payload, array $headers = null, string $username = null, string $password = null)
    {
        try
        {
            $curl_handler = curl_init($url);

            $encodedData = json_encode($payload);

            curl_setopt($curl_handler, CURLOPT_FRESH_CONNECT, true);

            curl_setopt($curl_handler, CURLOPT_CUSTOMREQUEST, "POST");

            if (empty($username) === false)
            {
                curl_setopt($curl_handler, CURLOPT_USERPWD, $username . ':' . $password);
            }

            curl_setopt($curl_handler, CURLOPT_POSTFIELDS, $encodedData);

            curl_setopt($curl_handler, CURLOPT_TIMEOUT_MS, $this->timeout);

            if (empty($headers) === false)
            {
                curl_setopt($curl_handler, CURLOPT_HTTPHEADER, $headers);
            }

            curl_exec($curl_handler);

            if (curl_errno($curl_handler) !== 0)
            {
                $error = curl_error($curl_handler);

                curl_close($curl_handler);

                throw new Exception\RuntimeException($error);
            }

            curl_close($curl_handler);
        }
        catch (\Throwable $e)
        {
            $this->trace->info(
                TraceCode::NON_BLOCKING_HTTP_ERROR,
                [
                    'error'     => $e->getMessage(),
                ]);
        }
    }
}

<?php

namespace RZP\Services;

use App;
use RZP\Trace\TraceCode;

class NonBlockingHttp
{
    public static function postRequest(string $url, $payload, array $headers = null)
    {
        try
        {
            $ch = curl_init($url);

            $encodedData = json_encode($payload);

            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

            curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);

            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 50);

            if ($headers !== null)
            {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }

            curl_exec($ch);

            curl_close($ch);
        }
        catch (\Throwable $e)
        {
            $app = App::getFacadeRoot();

            $trace = $app['trace'];

            $trace->info(
                TraceCode::NON_BLOCKING_HTTP_ERROR,
                [
                    'error'     => $e->getMessage(),
                ]);
        }
    }
}

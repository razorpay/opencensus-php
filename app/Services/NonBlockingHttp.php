<?php

namespace RZP\Services;

use App;
use RZP\Trace\TraceCode;

class NonBlockingHttp
{
    public static function postRequest(string $url, string $payload, array $header = null)
    {
        try
        {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);

            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 50);

            if ($header !== null)
            {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
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

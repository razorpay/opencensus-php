<?php

namespace RZP\Services;

use App;
use RZP\Trace\TraceCode;

class NonBlockingHttp
{
    public static function postRequest(string $url, string $payload, string $header = null)
    {
        try
        {
            $cmd = 'curl -X POST ' . $url;

            $cmd .= ' -d "' . $payload . '" ';

            if (isset($header) === true)
            {
                $cmd .= '-H "' . $header . '" ';
            }

            $cmd .= " -m 1 > /dev/null 2>&1 &";

            exec($cmd, $output, $exit);

            return $exit == 0;
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

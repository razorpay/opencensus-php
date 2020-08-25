<?php

namespace RZP\Services\Elfin\Impl;

use RZP\Http\Request\Requests;

use RZP\Exception;

abstract class Base
{
    /**
     * Shorten given url
     * @param  string       $url
     * @param  array        $input
     * @param  bool|boolean $fail - If fail is passed as true it'll bubble up ex.
     * @return string
     * @throws \Throwable
     */
    abstract public function shorten(string $url, array $input = [], bool $fail = false);

    protected function makeRequestAndValidateHeader(string $api, array $headers, $params = [])
    {
        $res = Requests::post($api, $headers, $params);

        $this->validateResponseHeader($res);

        return json_decode($res->body, true);
    }

    protected function validateResponseHeader($res)
    {
        //
        // If response code from any service is not 2xx, it throws exception
        // which gets handled by caller in ways.
        //

        $code = $res->status_code;

        if (in_array($code, [200, 201], true))
        {
            return;
        }

        throw new Exception\RuntimeException(
            'Unexpected response code received from ' . get_called_class() . ' service.',
            [
                'status_code' => $code,
                'res_body'    => $res->body,
            ]);
    }
}

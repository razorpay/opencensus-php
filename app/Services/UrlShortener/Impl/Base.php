<?php

namespace RZP\Services\UrlShortener\Impl;

use Requests;

use RZP\Exception;

abstract class Base
{

    abstract public function shorten(string $url);


    //
    // Calling instance() on any class which is extending this will return
    // singleton of the same.
    // Following block implements that.
    //

    static $instances = [];

    public static function instance(array $config = [])
    {
        $calledClass = get_called_class();

        if (isset($instances[$calledClass]) === false)
        {
            self::$instances[$calledClass] = new static($config);
        }

        return self::$instances[$calledClass];
    }

    protected function makeRequestAndValidateHeader(string $api, array $headers, $params)
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

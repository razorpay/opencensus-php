<?php

namespace RZP\Http\Request;

use Requests;

class Request
{
    const TRACE_REQUEST_FEATURE = 'request_trace';

    public static function request($url, $headers = array(), $data = array(), $type = Requests::GET, $options = array())
    {
        $hooks = new Hooks();

        $hooks->addCurlProperties($url, $options);

        return Requests::request($url, $headers, $data, $type, $options);
    }

    public static function get($url, $headers = array(), $options = array())
    {
        $hooks = new Hooks();

        $hooks->addCurlProperties($url, $options);

        return Requests::get($url, $headers, $options);
    }
}
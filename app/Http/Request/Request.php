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

    public static function head($url, $headers = array(), $options = array()) {
        $hooks = new Hooks();

        $hooks->addCurlProperties($url, $options);

        return Requests::head($url, $headers, $options);
    }

    public static function delete($url, $headers = array(), $options = array()) {
        $hooks = new Hooks();

        $hooks->addCurlProperties($url, $options);

        return Requests::delete($url, $headers, $options);
    }

    public static function trace($url, $headers = array(), $options = array()) {
        $hooks = new Hooks();

        $hooks->addCurlProperties($url, $options);

        return Requests::trace($url, $headers, $options);
    }

    public static function post($url, $headers = array(), $data = array(), $options = array()) {
        $hooks = new Hooks();

        $hooks->addCurlProperties($url, $options);

        return Requests::post($url, $headers, $data, $options);
    }

    public static function put($url, $headers = array(), $data = array(), $options = array()) {
        $hooks = new Hooks();

        $hooks->addCurlProperties($url, $options);

        return Requests::put($url, $headers, $data, $options);
    }

    public static function options($url, $headers = array(), $data = array(), $options = array()) {
        $hooks = new Hooks();

        $hooks->addCurlProperties($url, $options);

        return Requests::options($url, $headers, $data, $options);
    }

    public static function patch($url, $headers, $data = array(), $options = array()) {
        $hooks = new Hooks();

        $hooks->addCurlProperties($url, $options);

        return Requests::patch($url, $headers, $data, $options);
    }
}
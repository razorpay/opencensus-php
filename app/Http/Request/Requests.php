<?php

namespace RZP\Http\Request;

use Requests as Req;

class Requests
{
    /**
     * POST method
     *
     * @var string
     */
    const POST = 'POST';

    /**
     * PUT method
     *
     * @var string
     */
    const PUT = 'PUT';

    /**
     * GET method
     *
     * @var string
     */
    const GET = 'GET';

    /**
     * HEAD method
     *
     * @var string
     */
    const HEAD = 'HEAD';

    /**
     * DELETE method
     *
     * @var string
     */
    const DELETE = 'DELETE';

    /**
     * OPTIONS method
     *
     * @var string
     */
    const OPTIONS = 'OPTIONS';

    /**
     * TRACE method
     *
     * @var string
     */
    const TRACE = 'TRACE';

    /**
     * PATCH method
     *
     * @link https://tools.ietf.org/html/rfc5789
     * @var string
     */
    const PATCH = 'PATCH';

    const TRACE_REQUEST_FEATURE = 'request_trace';

    public static function request($url, $headers = array(), $data = array(), $type = Request::GET, $options = array())
    {
        $hooks = new Hooks();

        $hooks->addCurlProperties($url, $options);

        return Req::request($url, $headers, $data, $type, $options);
    }

    public static function get($url, $headers = array(), $options = array())
    {
        $hooks = new Hooks();

        $hooks->addCurlProperties($url, $options);

        return Req::get($url, $headers, $options);
    }

    public static function head($url, $headers = array(), $options = array()) {
        $hooks = new Hooks();

        $hooks->addCurlProperties($url, $options);

        return Req::head($url, $headers, $options);
    }

    public static function delete($url, $headers = array(), $options = array()) {
        $hooks = new Hooks();

        $hooks->addCurlProperties($url, $options);

        return Req::delete($url, $headers, $options);
    }

    public static function trace($url, $headers = array(), $options = array()) {
        $hooks = new Hooks();

        $hooks->addCurlProperties($url, $options);

        return Req::trace($url, $headers, $options);
    }

    public static function post($url, $headers = array(), $data = array(), $options = array()) {
        $hooks = new Hooks();

        $hooks->addCurlProperties($url, $options);

        return Req::post($url, $headers, $data, $options);
    }

    public static function put($url, $headers = array(), $data = array(), $options = array()) {
        $hooks = new Hooks();

        $hooks->addCurlProperties($url, $options);

        return Req::put($url, $headers, $data, $options);
    }

    public static function options($url, $headers = array(), $data = array(), $options = array()) {
        $hooks = new Hooks();

        $hooks->addCurlProperties($url, $options);

        return Req::options($url, $headers, $data, $options);
    }

    public static function patch($url, $headers, $data = array(), $options = array()) {
        $hooks = new Hooks();

        $hooks->addCurlProperties($url, $options);

        return Req::patch($url, $headers, $data, $options);
    }
}
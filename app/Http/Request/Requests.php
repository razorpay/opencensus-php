<?php

namespace RZP\Http\Request;


use Requests as Req;
use RZP\Trace\Tracer;
use RZP\Constants\Metric;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\SpanContext;
use OpenCensus\Trace\Propagator\ArrayHeaders;

class Requests
{
    /**
     * POST method
     *
     * @var string
     */
    const POST = Req::POST;

    /**
     * PUT method
     *
     * @var string
     */
    const PUT = Req::PUT;

    /**
     * GET method
     *
     * @var string
     */
    const GET = Req::GET;

    /**
     * HEAD method
     *
     * @var string
     */
    const HEAD = Req::HEAD;

    /**
     * DELETE method
     *
     * @var string
     */
    const DELETE = Req::DELETE;

    /**
     * OPTIONS method
     *
     * @var string
     */
    const OPTIONS = Req::OPTIONS;

    /**
     * TRACE method
     *
     * @var string
     */
    const TRACE = Req::TRACE;

    /**
     * PATCH method
     *
     * @link https://tools.ietf.org/html/rfc5789
     * @var string
     */
    const PATCH = 'PATCH';

    const TRACE_REQUEST_FEATURE = 'request_trace';

    public static function getRequestSpanOptions(string $url)
    {
        $urlInfo = parse_url($url);

        $name = $urlInfo['host'];

        if (array_key_exists('path', $urlInfo)){
            $name = $name . $urlInfo['path'];
        }

        $spanOptions = ['name' => $name,
                        'kind'=> 'client',
                        'sameProcessAsParentSpan' => false
                    ];

        $attrs = ['span.kind' => 'client'];
        if (array_key_exists('query', $urlInfo)){
            parse_str($urlInfo['query'], $queryParams);
            $attrs += $queryParams;
        }

        $spanOptions['attributes'] = $attrs;
        return $spanOptions;
    }

    private static function wrapRequestInSpan($methodName, $methodArgs, $defaultSpanOptions = array())
    {
        $response = null;
        $span = Tracer::startSpan($defaultSpanOptions);
        $scope = Tracer::withSpan($span);

        // inject spanContext into trace propagation headers
        $headers = [];
        if(count($methodArgs) > 1)
        {
            $headers = $methodArgs[1];
        }

        $arrHeaders = new ArrayHeaders($headers);
        Tracer::injectContext($arrHeaders);
        $headers = $arrHeaders->toArray();
        $methodArgs[1] = $headers;

        $methodTag = ($methodName == 'request') ? $methodArgs[3] : $methodName;
        $span->addAttribute('http.method', $methodTag);

        // handle actual request
        try
        {
            $response = Req::$methodName(...$methodArgs);
        }
        catch(\Throwable $e)
        {
            $span->addAttribute('error', 'true');
            throw $e;
        }
        finally{
            $scope->close();
        }

        if (!is_null($response))
        {
            // add response status as a span tags
            $statusCode = $response->status_code;
            $span->addAttribute('http.status_code', $statusCode);
            if ($statusCode >= 400)
            {
                $span->addAttribute('error', 'true');
            }
        }

        return $response;
    }

    public static function request($url, $headers = array(), $data = array(), $type = self::GET, $options = array())
    {
        $hooks = new Hooks($url);

        $hooks->addCurlProperties($options);

        $spanOptions = self::getRequestSpanOptions($url);

        $response = self::wrapRequestInSpan(
                        'request',
                        array($url, $headers, $data, $type, $options),
                        $spanOptions
                    );

        self::pushMetric($url, $type, $data, $response);

        return $response;
    }

    public static function get($url, $headers = array(), $options = array())
    {
        $hooks = new Hooks($url);

        $hooks->addCurlProperties($options);

        $spanOptions = self::getRequestSpanOptions($url);

        $response = self::wrapRequestInSpan(
                        'get',
                        array($url, $headers, $options),
                        $spanOptions
                    );

        self::pushMetric($url, self::GET, array(), $response);

        return $response;
    }

    public static function head($url, $headers = array(), $options = array())
    {
        $hooks = new Hooks($url);

        $hooks->addCurlProperties($options);

        $spanOptions = self::getRequestSpanOptions($url);

        $response = self::wrapRequestInSpan(
                        'head',
                        array($url, $headers, $options),
                        $spanOptions
                    );

        self::pushMetric($url, self::HEAD, array(), $response);

        return $response;
    }

    public static function delete($url, $headers = array(), $options = array())
    {
        $hooks = new Hooks($url);

        $hooks->addCurlProperties($options);

        $spanOptions = self::getRequestSpanOptions($url);

        $response = self::wrapRequestInSpan(
                        'delete',
                        array($url, $headers, $options),
                        $spanOptions
                    );

        self::pushMetric($url, self::DELETE, array(), $response);

        return $response;
    }

    public static function trace($url, $headers = array(), $options = array())
    {
        $hooks = new Hooks($url);

        $hooks->addCurlProperties($options);

        $spanOptions = self::getRequestSpanOptions($url);

        $response = self::wrapRequestInSpan(
                        'trace',
                        array($url, $headers, $options),
                        $spanOptions
                    );

        self::pushMetric($url, self::TRACE, array(), $response);

        return $response;
    }

    public static function post($url, $headers = array(), $data = array(), $options = array())
    {
        $hooks = new Hooks($url);

        $hooks->addCurlProperties($options);

        $spanOptions = self::getRequestSpanOptions($url);

        $response = self::wrapRequestInSpan(
                        'post',
                        array($url, $headers, $data, $options),
                        $spanOptions
                    );

        self::pushMetric($url, self::POST, $data, $response);

        return $response;
    }

    public static function put($url, $headers = array(), $data = array(), $options = array())
    {
        $hooks = new Hooks($url);

        $hooks->addCurlProperties($options);

        $spanOptions = self::getRequestSpanOptions($url);

        $response = self::wrapRequestInSpan(
                        'put',
                        array($url, $headers, $data, $options),
                        $spanOptions
                    );

        self::pushMetric($url, self::PUT, $data, $response);

        return $response;
    }

    public static function options($url, $headers = array(), $data = array(), $options = array())
    {
        $hooks = new Hooks($url);

        $hooks->addCurlProperties($options);

        $spanOptions = self::getRequestSpanOptions($url);

        $response = self::wrapRequestInSpan(
                        'options',
                        array($url, $headers, $data, $options),
                        $spanOptions
                    );

        self::pushMetric($url, self::OPTIONS, $data, $response);

        return $response;
    }

    public static function patch($url, $headers, $data = array(), $options = array())
    {
        $hooks = new Hooks($url);

        $hooks->addCurlProperties($options);

        $spanOptions = self::getRequestSpanOptions($url);

        $response = self::wrapRequestInSpan(
                        'patch',
                        array($url, $headers, $data, $options),
                        $spanOptions
                    );

        self::pushMetric($url, self::PATCH, $data, $response);

        return $response;
    }

    protected static function pushMetric($url, $method, $data, $response)
    {
        try {
            $modifyUrl = parse_url($url);
            $dimensions = [
                Metric::LABEL_ROUTE                 => $modifyUrl['host'],
                Metric::LABEL_METHOD                => $method
            ];

            app('trace')->histogram(Metric::HTTP_OUTGOING_REQUEST_SIZE, strlen(serialize($data)), $dimensions);
            app('trace')->histogram(Metric::HTTP_OUTGOING_RESPONSE_SIZE, strlen($response->body), $dimensions);
        } catch (\Throwable $e){
            // doing nothing
        }
    }
}

<?php

namespace App\Admin;

use Trace;
use App\Trace\SpanTrace;
use App\Trace\TraceCode;
use App\Constants\Tracing;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Client as Guzzle;
use Razorpay\Api\Errors\ErrorCode;
use Razorpay\Api\Errors\BadRequestError;
use GuzzleHttp\Psr7\Request;
use OpenCensus\Trace\Propagator\ArrayHeaders;

class ApiRequestSpan
{
    protected $client;

    function __construct(Guzzle $client)
    {
        $this->client = $client;
    }

    public static function getRequestSpanOptions(string $url)
    {
        $urlInfo = parse_url($url);

        $name = $urlInfo['host'];

        if (array_key_exists('path', $urlInfo))
        {
            $name = $name . $urlInfo['path'];
        }

        $spanOptions = [
            Tracing::NAME             => $name,
            'kind'                    => Tracing::CLIENT,
            'sameProcessAsParentSpan' => false
        ];

        $attrs = [Tracing::SPAN_KIND => Tracing::CLIENT];

        if (array_key_exists(Tracing::QUERY, $urlInfo))
        {

            parse_str($urlInfo[Tracing::QUERY], $queryParams);

            $attrs += $queryParams;
        }

        $spanOptions[Tracing::ATTRIBUTES] = $attrs;

        return $spanOptions;
    }

    /**
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    public function wrapAsyncRequest($methodName, $path, $methodArgs, $defaultSpanOptions = array()): \GuzzleHttp\Promise\PromiseInterface
    {
        $span = SpanTrace::startSpan($defaultSpanOptions);
        
        // inject spanContext into trace propagation headers
        $headers = [];

        if (empty($methodArgs['headers']) === false)
        {
            $headers = $methodArgs['headers'];
        }

        $arrHeaders = new ArrayHeaders($headers);

        SpanTrace::injectContext($arrHeaders);

        $methodArgs['options']['headers'] = $arrHeaders->toArray();
    
        $methodTag = $methodName;
    
        $span->addAttribute('http.method', $methodTag);
        
        /*
         * send cookies in cookie jar format
         */
        if(isset($methodArgs['options']['cookies']) === true)
        {
            $cookieDomain = '.razorpay.com';

            if (\App::environment() !== 'production')
            {
                $cookieDomain = '.razorpay.in';
            }

            $methodArgs['options']['cookies'] = CookieJar::fromArray($methodArgs['options']['cookies'],$cookieDomain);
        }
        
        $request = new Request($methodName, $path);
       
        return $this->client->sendAsync($request, $methodArgs['options']);
    }

    public function wrapRequestInSpan($methodName, $path, $methodArgs, $defaultSpanOptions = array())
    {
        $span = SpanTrace::startSpan($defaultSpanOptions);

        $scope = SpanTrace::withSpan($span);

        // inject spanContext into trace propagation headers
        $headers = [];
        if (empty($methodArgs['headers']) === false)
        {
            $headers = $methodArgs['headers'];
        }

        $arrHeaders = new ArrayHeaders($headers);

        SpanTrace::injectContext($arrHeaders);

        $methodArgs['options']['headers'] = $arrHeaders->toArray();

        $methodTag = $methodName;

        $span->addAttribute('http.method', $methodTag);

        /*
         * send cookies in cookie jar format
         * */
        if(isset($methodArgs['options']['cookies']) === true)
        {

            $cookieDomain = '.razorpay.com';

            if (\App::environment() !== 'production')
            {
                $cookieDomain = '.razorpay.in';
            }

            $methodArgs['options']['cookies'] = CookieJar::fromArray($methodArgs['options']['cookies'],$cookieDomain);

        }

        // handle actual request
        try {
            $client = $this->client
                ->$methodName($path, $methodArgs['options']);
        }
        catch (\Throwable $e)
        {
            Trace::info(TraceCode::JAEGER_INFO, [
                'message'   => $e->getMessage(),
                'code'      => $e->getCode(),
            ]);

            $span->addAttribute('error', 'true');

            throw $e;
        }
        finally
        {
            $scope->close();
        }

        $response = json_decode($client->getBody(), true);

        if (is_null($response) === false)
        {
            // add response status as a span tags
            $httpCode = $client->getStatusCode();

            $span->addAttribute(Tracing::HTTP . '.' . Tracing::STATUS_CODE, $httpCode);

            if ($httpCode >= 400)
            {
                $span->addAttribute('error', 'true');
            }
        }

        return $client;
    }
}

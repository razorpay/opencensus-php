<?php

namespace RZP\Http\AsyncRequest;

use Requests as Req;
use RZP\Trace\Tracer;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\SpanContext;
use OpenCensus\Trace\Propagator\ArrayHeaders;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Request;
use RZP\Http\AsyncRequest\Constants;

class AsyncRequest
{

    function __construct(string $path, string $method)
    {
        $this->path = $path;
        
        $this->method = $method;

        $this->client = new Client();
    }

    public function getRequestSpanOptions(string $url)
    {
        $urlInfo = parse_url($url);
        $name = $urlInfo[Constants::HOST];

        if (array_key_exists(Constants::PATH, $urlInfo)){
            $name = $name . $urlInfo[Constants::PATH];
        }

        $spanOptions = [
            Constants::NAME => $name,
            Constants::KIND=> 'client',
            Constants::PROCESS_SPAN_TYPE => false
        ];

        $attrs = [
            Constants::SPAN_KIND => 'client',
            Constants::HTTP_METHOD => $this->method
        ];

        if (array_key_exists(Constants::QUERY, $urlInfo)){
            parse_str($urlInfo[Constants::QUERY], $queryParams);
            $attrs += $queryParams;
        }

        $spanOptions[Constants::ATTRIBUTES] = $attrs;
        return $spanOptions;
    }

    private function createRequestPromise($methodArgs)
    {
        $headers = [];

        if (isset($methodArgs[Constants::HEADERS])) {
            $headers = $methodArgs[Constants::HEADERS];
        }
        
        $arrHeaders = new ArrayHeaders($headers);

        $headers = $arrHeaders->toArray();

        $methodArgs[Constants::HEADERS] = $headers;

        $request = new Request($this->method, $this->path);
       
        return $this->client->sendAsync($request, $methodArgs);
    }


    public function requestAsync($payload): Promise
    {
        $spanOptions = $this->getRequestSpanOptions($this->path);

        $asyncPromise = Tracer::inSpan($spanOptions, function() use($payload)
        {
            return $this->createRequestPromise($payload);
        });

        return $asyncPromise;
    }

}


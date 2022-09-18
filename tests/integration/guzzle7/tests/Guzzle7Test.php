<?php

namespace integration\guzzle7\tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use OpenCensus\Trace\Exporter\NullExporter;
use OpenCensus\Trace\Integrations\Guzzle\Middleware;
use OpenCensus\Trace\Tracer;
use PHPUnit\Framework\TestCase;

class Guzzle7Test extends TestCase
{
    public function testGuzzleRequest()
    {
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        $stack->push(new Middleware());
        $client = new Client(['handler' => $stack, 'headers' => ['X-Guzzle7-Test' => 'Bar']]);
        $exporter = new NullExporter();
        $tracer = Tracer::start($exporter, [
            'skipReporting' => true,
        ]);

        $response = $client->request('GET', 'http://httpbin.org/get', ['headers' => ['X-Hello' => 'world']
        ]);

        $client->get('http://httpbin.org/get', ['headers' => ['X-Foo' => 'test']]);

        $spans = $tracer->tracer()->spans();
        $this->assertCount(3, $spans);
        $this->assertEquals('GuzzleHttp::request', $spans[1]->name());
        $this->assertEquals('GET', $spans[1]->attributes()['method']);
        $this->assertEquals('world', $spans[1]->attributes()['X-Hello']);
        $this->assertEquals('http://httpbin.org/get', $spans[1]->attributes()['uri']);
    }
}

<?php

namespace RZP\Tests\P2p\Service\Base;

use Illuminate\Support\Facades\App;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;

class P2pRequest
{
    use MakesHttpRequests;

    protected $app;

    protected $method;

    protected $uri;

    protected $data = [];

    protected $server = [];

    public function __construct(string $uri)
    {
        $this->app = App::getFacadeRoot();

        $this->uri = $uri;
    }

    public function method(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function server(array $headers): self
    {
        $this->server = array_merge($this->server, $headers);

        return $this;
    }

    public function content(array $content): self
    {
        $this->server['HTTP_CONTENT_TYPE'] = 'application/json';

        $this->data = $content;

        return $this;
    }

    public function send(): TestResponse
    {
        $response = $this->call(
                        $this->method,
                        $this->uri,
                        $this->data,
                        [],
                        [],
                        $this->server,
                        '');

        return $response;
    }

    public function trace()
    {
        return [$this->method, $this->uri, $this->data, $this->server];
    }
}

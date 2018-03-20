<?php

namespace RZP\Models\FundTransfer\Base\Initiator;

use App;
use Requests;

abstract class RequestProcessor extends NodalAccount
{
    /**
     * Holds request method
     *
     * @var string
     */
    protected $method;

    /**
     * Holds the request URL
     *
     * @var string
     */
    protected $url;

    /**
     * Holds the request headers to be sent
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Holds the request body
     *
     * @var array
     */
    protected $body = [];

    /**
     * Holds the options data for request
     *
     * @var array
     */
    protected $options = [];

    /**
     * Response trace will be recorded if this variable is set
     * and trace is recorded against this trace code
     *
     * @var null
     */
    protected $responseTraceCode = null;

    /**
     * Request trace will be recorded if this variable is set
     * and trace is recorded against this trace code
     *
     * @var null
     */
    protected $requestTraceCode = null;

    protected $config;

    public function method(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function url(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function options(array $option): self
    {
        $this->options = array_merge($this->options, $option);

        return $this;
    }

    public function headers(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    public function body(array $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function responseTraceCode(string $traceCode): self
    {
        $this->responseTraceCode = $traceCode;

        return $this;
    }

    public function requestTraceCode(string $traceCode): self
    {
        $this->requestTraceCode = $traceCode;

        return $this;
    }

    public function makeRequest(): array
    {
        $this->collectRequestData();

        $this->traceRequest();

        $response = Requests::request(
            $this->url,
            $this->headers,
            $this->body,
            $this->method,
            $this->options);

        $this->traceResponse($response);

        return $this->processResponse($response);
    }

    /**
     * Collects all the essential params for current request
     */
    private function collectRequestData()
    {
        $this
            ->url($this->requestUrl())
            ->body($this->requestBody())
            ->method($this->requestMethod())
            ->headers($this->requestHeaders())
            ->options($this->requestOptions());
    }

    /**
     * Trace response if `responseTraceCode` is set
     * Response will be traced against the `responseTraceCode` set
     *
     * @param \Requests_Response $response
     */
    private function traceResponse(\Requests_Response $response)
    {
        if($this->responseTraceCode === null)
        {
            return;
        }

        $this->trace->info(
            $this->responseTraceCode,
            [
                'response' => $response,
            ]);
    }

    /**
     * Trace request if `requestTraceCode` is set
     * Request will be traced against the `requestTraceCode` set
     */
    private function traceRequest()
    {
        if($this->requestTraceCode === null)
        {
            return;
        }

        $this->trace->info(
            $this->requestTraceCode,
            [
                'method'  => $this->method,
                'url'     => $this->url,
                'request' => $this->body,
            ]);
    }

    /**
     *
     * Should be implimented in the chiled class and should return the URL to be requested
     *
     * @return string
     */
    public abstract function requestUrl(): string;

    /**
     * Should give the request body for the current request class
     *
     * @return array
     */
    public abstract function requestBody(): array;

    /**
     * Should give the request method for current request
     *
     * @return string
     */
    public abstract function requestMethod(): string;

    /**
     * Should give the request header for current request
     *
     * @return array
     */
    public abstract function requestHeaders(): array;

    /**
     * Should give the options for current request
     *
     * @return array
     */
    public abstract function requestOptions(): array;

    /**
     * Should be implemented in the clild class to process the response of current request
     * Processing should have the status check and other required validations
     *
     * @param \Requests_Response $response
     *
     * @return array
     */
    public abstract function processResponse(\Requests_Response $response): array;
}

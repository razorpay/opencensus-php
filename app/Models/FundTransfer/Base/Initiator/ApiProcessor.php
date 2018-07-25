<?php

namespace RZP\Models\FundTransfer\Base\Initiator;

use App;
use Request;
use Requests;
use RZP\Trace\TraceCode;

abstract class ApiProcessor extends NodalAccount
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

    protected $config;

    /**
     * Holds the request body
     *
     * @var string
     */
    protected $body = null;

    /**
     * Holds the options data for request
     *
     * @var array
     */
    protected $options = [];

    /**
     * Holds the response object
     *
     * @var \Requests_Response
     */
    protected $response = null;

    /**
     * Response trace will be recorded if this variable is set
     * and trace is recorded against this trace code
     *
     * @var string
     */
    protected $responseTraceCode = TraceCode::SETTLEMENT_API_RESPONSE;

    /**
     * Request trace will be recorded if this variable is set
     * and trace is recorded against this trace code
     *
     * @var string
     */
    protected $requestTraceCode = TraceCode::SETTLEMENT_API_REQUEST;

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

    public function body(string $body): self
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

    public function getResponse()
    {
        return $this->response;
    }

    public function makeRequest(): array
    {
        $this->collectRequestData();

        $this->traceRequest();

        if ($this->config['mock'] === true)
        {
            $this->response = $this->sendMockRequest();
        }
        else
        {
            $this->response = Requests::request(
                $this->url,
                $this->headers,
                $this->body,
                $this->method,
                $this->options);
        }

        $this->traceResponse($this->response);

        return $this->processResponse($this->response);
    }

    /**
     * Collects all the essential params for current request
     */
    private function collectRequestData()
    {
        $this->url($this->requestUrl())
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
        $this->trace->info(
            $this->responseTraceCode,
            [
                'channel'       => $this->channel,
                'response_body' => $response->body,
            ]);
    }

    /**
     * Trace request if `requestTraceCode` is set
     * Request will be traced against the `requestTraceCode` set
     */
    private function traceRequest()
    {
        $this->trace->info(
            $this->requestTraceCode,
            [
                'channel' => $this->channel,
                'method'  => $this->method,
                'request' => $this->body,
            ]);
    }

    /**
     * Creates dummy response from the array received from `responseGenerator`
     *
     * @return \Requests_Response
     */
    private function sendMockRequest(): \Requests_Response
    {
        $input = Request::all();

        $content = $this->mockresponseGenerator($input);

        $response = new \Requests_Response();

        $response->body = $content;

        $response->status_code = 200;

        $response->url = $this->url;

        return $response;
    }

    /**
     * Gets the certificate file location
     * if the file doesn't exist then create a client certificate file based on configuration provided
     *
     * @return string
     */
    protected function getClientCertificate(): string
    {
        $certPath = $this->getGatewayCertDirPath();

        $certFile = $certPath . '/' . $this->getClientCertificateName();

        // Download cert file from vault if already not present and store locally
        if (file_exists($certFile) === false)
        {
            $cert = $this->config['client_certificate'];

            $cert = str_replace('\n', PHP_EOL, $cert);

            file_put_contents($certFile, $cert);
        }

        return $certFile;
    }

    /**
    * Give the certificate key file location
    * if the file doesnt exist then create a client key file based on configuration provided
    *
    * @return string
    */
    protected function getClientCertificateKey(): string
    {
        $certPath = $this->getGatewayCertDirPath();

        $certFile = $certPath . '/' . $this->getClientCertificateKeyName();

        // Download cert key file from vault if already not present and store locally
        if (file_exists($certFile) === false)
        {
            $key = $this->config['client_certificate_key'];

            $key = str_replace('\n', PHP_EOL, $key);

            file_put_contents($certFile, $key);
        }

        return $certFile;
    }

    /**
    * give the client certificate file name
    *
    * @return string
    */
    protected function getClientCertificateName(): string
    {
        return $this->config['certificate_name'];
    }

    /**
     * Gives the certificate directory
     *
     * @return string
     */
    protected function getGatewayCertDirPath(): string
    {
        return $this->config['certificate_path'];
    }

    /**
    * Gives the certificate key file name
    *
    * @return string
    */
    protected function getClientCertificateKeyName(): string
    {
        return $this->config['certificate_key_name'];
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
     * @return string
     */
    public abstract function requestBody(): string;

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
     * Generate the mock response for the given class.
     *
     * @param array $input config params
     *
     * @return string
     */
    protected abstract function mockResponseGenerator(array $input): string;

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

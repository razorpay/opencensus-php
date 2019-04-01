<?php

namespace RZP\Tests\P2p\Service\Base;

use JsonSchema;
use RZP\Exception\BaseException;
use RZP\Tests\P2p\Service\Base\Traits;
use Illuminate\Foundation\Testing\TestResponse;

class P2pHelper
{
    use Traits\ExceptionTrait;
    /**
     * @var $fixtures Fixtures\Fixtures
     */
    protected $fixtures;

    /**
     * @var $exceptionHandler MockExceptionHandler
     */
    protected $exceptionHandler;

    protected $requestHandlers = [];
    protected $responseCallbacks = [];

    protected $shouldValidateJsonSchema = false;
    protected $validationJsonSchemaPath = null;

    protected $expectFailureInResponse = false;

    /**
     * Initiating auth with public auth for TestMerchant
     * @var array
     */
    protected $serverHeaders = [
        'PHP_AUTH_USER' => 'rzp_test_TheTestAuthKey',
        'PHP_AUTH_PW'   => ''
    ];

    /**
     * For all API routes which starts with customer, its set true
     * @var bool
     */
    protected $isCustomerInContext;

    /**
     * For all API routes which runs on device auth, its set true
     * @var bool
     */
    protected $isDeviceInContext;

    /**
     * Set fixtures,
     * Initiate Auth for Device 1
     * Resets response callbacks
     *
     * P2pHelper constructor.
     */
    public function __construct(Fixtures\Fixtures $fixtures, MockExceptionHandler $exceptionHandler)
    {
        $this->fixtures = $fixtures;

        $this->exceptionHandler = $exceptionHandler;

        $this->resetContexts();

        $this->resetResponseCallbacks([[$this, 'defaultResponseCallback']]);
    }

    /**
     * Enable or disable Customer Context
     *
     * @param bool $context
     * @return P2pHelper
     */
    public function setCustomerInContext(bool $context): self
    {
        $this->isCustomerInContext = $context;

        return $this;
    }

    /**
     * Enable or disable Device Context
     *
     * @param bool $context
     * @return P2pHelper
     */
    public function setDeviceInContext(bool $context): self
    {
        $this->isDeviceInContext = $context;

        return $this;
    }

    /**
     * Reset contexts to their initial value
     */
    public function resetContexts()
    {
        $this->setCustomerInContext(true);
        $this->setDeviceInContext(true);
    }

    /**
     * Enable or disable schema validation
     *
     * @param bool $enabled
     * @return P2pHelper
     */
    public function withSchemaValidated(bool $enabled = true): self
    {
        $this->shouldValidateJsonSchema = $enabled;

        return $this;
    }

    /**
     * Set failure expectation in response
     *
     * @param bool $failure
     * @return P2pHelper
     */
    public function expectFailureInResponse(bool $failure = true)
    {
        $this->expectFailureInResponse = $failure;

        return $this;
    }

    /**
     * Basic API contract assertions
     *
     * @param TestResponse $response
     */
    protected function defaultResponseCallback(TestResponse $response)
    {
        if ($response->isSuccessful() === true)
        {
            $response->assertHeader('X-Razorpay-Request-Id');
            $response->assertHeader('Content-Type', 'application/json');

            return;
        }

        if ($this->expectFailureInResponse === true)
        {
            return;
        }

        $this->throwTestingException('Failure in request', $response->json());
    }

    /**
     * Reset the default response callback
     *
     * @param array $with
     * @return P2pHelper
     */
    public function resetResponseCallbacks($with = []): self
    {
        $this->responseCallbacks = $with;

        return $this;
    }

    /**
     * Add multiple callback for assertions
     *
     * @param callable $callback
     * @return P2pHelper
     */
    public function registerResponseCallback(callable $callback): self
    {
         $this->responseCallbacks[] = $callback;

         return $this;
    }

    /**
     * Reset the default request handler
     *
     * @param array $with
     * @return P2pHelper
     */
    public function resetRequestHandler($with = []): self
    {
        $this->requestHandlers = $with;

        return $this;
    }

    /**
     * Add multiple handler for request
     *
     * @param callable $callback
     * @return P2pHelper
     */
    public function registerRequestHandler(callable $callback): self
    {
         $this->requestHandlers[] = $callback;

         return $this;
    }

    public function withFailureResponse(callable $callback): self
    {
        $this->expectFailureInResponse = true;

        $this->exceptionHandler->setThrowExceptionInTesting(false);

        $this->registerResponseCallback($callback);

        return $this;
    }

    /**
     * Creates the request with URI and Auth
     *
     * @param string $uri Without Customer Prefix
     * @param array $parameter
     * @return P2pRequest
     */
    protected function request(string $uri, array $parameter = []): P2pRequest
    {
        $request = new P2pRequest($this->makeUri($uri, $parameter));

        $request->server($this->makeServer());

        return $request;
    }

    /**
     * Set the content with array_replace_recursive with ove
     *
     * @param P2pRequest $request
     * @param array $content
     * @param array $default
     * @return P2pRequest
     */
    protected function content(
        P2pRequest $request,
        array $content = [],
        array $override = []): P2pRequest
    {
        $content = array_filter(array_replace_recursive($content, $override));

        return $request->content($content);
    }

    /**
     * Make a Get Request
     *
     * @param P2pRequest $request
     * @return array
     */
    protected function get(P2pRequest $request): array
    {
        return $this->send($request->method('get'));
    }

    /**
     * Make a Post Request
     *
     * @param P2pRequest $request
     * @return array
     */
    protected function post(P2pRequest $request): array
    {
        return $this->send($request->method('post'));
    }

    /**
     * Make a Delete Request
     *
     * @param P2pRequest $request
     * @return array
     */
    protected function delete(P2pRequest $request): array
    {
        return $this->send($request->method('delete'));
    }

    /**
     * Send the request with callbacks wrapped
     *
     * @param P2pRequest $request
     * @return array
     */
    protected function send(P2pRequest $request): array
    {
        $this->runRequestHandlers($request);

        if (env('P2P_LOG_REQUESTS')) info('_LOGGER_ REQUEST', $request->trace());

        $response = $request->send();

        if (env('P2P_LOG_REQUESTS')) info('_LOGGER_ RESPONSE', $response->json());

        $this->runResponseCallbacks($response);

        $this->validateResponseJsonSchema(json_decode($response->content()));

        return $response->json();
    }

    protected function runRequestHandlers(P2pRequest $request)
    {
        foreach ($this->requestHandlers as $callback)
        {
            $callback($request);
        }
    }

    /**
     * Run all registered response callbacks
     *
     * @param TestResponse $response
     * @return array
     */
    protected function runResponseCallbacks(TestResponse $response)
    {
        foreach ($this->responseCallbacks as $callback)
        {
            $callback($response);
        }
    }

    /**
     * Validates the response with Json Schema
     *
     * @param string $json
     */
    protected function validateResponseJsonSchema(\stdClass $data)
    {
        if ($this->shouldValidateJsonSchema === false)
        {
            return;
        }

        if (($this instanceof DeviceHelper) or
            ($this instanceof BankAccountHelper) or
            ($this instanceof VpaHelper))
        {
            $suffix = 'processed';

            if (isset($data->type) and in_array($data->type, ['sdk', 'sms', 'post'], true))
            {
                $suffix = 'next';
            }

            $jsonPath = app_path('Http/Controllers/P2p/JsonSchema/' .
                $this->validationJsonSchemaPath . '.response.' . $suffix . '.json');
        }
        else
        {
            $jsonPath = app_path('Http/Controllers/P2p/JsonSchema/' .
                $this->validationJsonSchemaPath . '.json');
        }

        if (file_exists($jsonPath) === false)
        {
            $this->throwTestingException('Json schema file does not exists', [$jsonPath]);
        }

        $validator = new JsonSchema\Validator;

        $validator->validate($data, (object) ['$ref' => 'file://' . realpath($jsonPath)]);

        if ($validator->isValid() === true)
        {
            return;
        }

        $errors = [];
        foreach ($validator->getErrors() as $error)
        {
            $errors[$error['property']][] = $error['message'];
        }

        $this->throwTestingException('Json schema validation failed', $errors);
    }

    protected function makeUri(string $uri, array $parameters)
    {
        $url = parse_url($uri);

        if (empty($url['scheme']) === false)
        {
            return $uri;
        }

        $prefix = 'v1/upi/';

        if ($this->isCustomerInContext === true)
        {
            $prefix .= 'customer/';
        }

        return $prefix . sprintf($uri, ...$parameters);
    }

    protected function makeServer()
    {
        $servers = $this->serverHeaders;

        if ($this->isDeviceInContext === true)
        {
            $servers['PHP_AUTH_PW'] = $this->fixtures->device->getAuthToken();
        }

        $servers['HTTP_X_RAZORPAY_VPA_HANDLE'] = $this->fixtures->handle->getCode();

        return $servers;
    }
}

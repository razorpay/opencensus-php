<?php

namespace RZP\Tests\P2p\Service\Base;

use JsonSchema;
use Illuminate\Foundation\Testing\TestResponse;

class P2pHelper
{
    use ExceptionTrait;
    /**
     * @var $fixtures Fixtures\Fixtures
     */
    protected $fixtures;

    protected $responseCallbacks = [];

    protected $shouldValidateJsonSchema = false;
    protected $validationJsonSchemaPath = null;

    protected $expectFailureInResponse = false;

    /**
     * For all APIs which start with customer id
     * Only Customer APIs do not have customer context
     * @var bool
     */
    protected $isCustomerInContext = true;

    /**
     * P2pHelper constructor.
     */
    public function __construct(Fixtures\Fixtures $fixtures)
    {
        $this->fixtures = $fixtures;

        $this->resetResponseCallbacks([[$this, 'defaultResponseCallback']]);
    }

    /**
     * Enable or disable schema validation
     *
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
    public function expectFailureInResponse(bool $failure = true): self
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
    protected function resetResponseCallbacks($with = []): self
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
    protected function registerResponseCallback(callable $callback): self
    {
         $this->responseCallbacks[] = $callback;

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

        $request->server([
            'PHP_AUTH_USER' => 'rzp_test_TheTestAuthKey',
            'PHP_AUTH_PW'   => 'TheKeySecretForTests',
        ]);

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
        // TODO: Remove Before Merging
        info('_LOGGER_ REQUEST', $request->trace());

        $response = $request->send();

        // TODO: Remove Before Merging
        info('_LOGGER_ RESPONSE', [$response->content()]);

        $this->runResponseCallbacks($response);

        $this->validateResponseJsonSchema($response->content());

        return $response->json();
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
    protected function validateResponseJsonSchema(string $json)
    {
        if ($this->shouldValidateJsonSchema === false)
        {
            return;
        }

        $jsonPath = app_path('Http/Controllers/P2p/JsonSchema/' . $this->validationJsonSchemaPath . '.json');

        if (file_exists($jsonPath) === false)
        {
            $this->throwTestingException('Json schema file does not exists', [$jsonPath]);
        }

        $data = json_decode($json);

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
        $prefix = 'p2p/v1/';

        if ($this->isCustomerInContext === true)
        {
            $prefix .= 'customers/cust_%s/';
            $parameters = array_merge([Constants::LOCAL_CUSTOMER], $parameters);
        }

        return vsprintf($prefix . $uri , $parameters);
    }
}

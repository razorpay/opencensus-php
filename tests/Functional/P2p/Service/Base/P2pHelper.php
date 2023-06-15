<?php

namespace RZP\Tests\P2p\Service\Base;

use Illuminate\Testing\TestResponse;
use JsonSchema;
use RZP\Exception\BaseException;
use RZP\Tests\P2p\Service\Base\Traits;

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
     * For all API routes which has merchant key, its set true
     * @var bool
     */
    protected $isMerchantInContext;

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
     * For all API routes which has merchant key and password, its set to false
     * @var bool
     */
    protected $isMerchantOnAuth;

    /**
     * A valid scenario can we added to in request, which will be passed as header
     * @var Scenario
     */
    protected $scenarioInContext;

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

    /******************************* Common Functions ******************************/

    public function callback(string $gateway, array $options = [])
    {
        // This API work on direct auth
        $this->setMerchantInContext(false);
        $this->setCustomerInContext(false);
        $this->setDeviceInContext(false);

        $request = $this->request('callback/%s', [$gateway]);

        $this->resetContexts();

        $request->server($options['server'] ?? []);

        $request->json($options['content']);

        return $this->post($request);
    }


    /******************************* Turbo Functions ******************************/

    public function turboCallback(string $gateway, array $options = [])
    {
        $this->shouldValidateJsonSchema = false;

        // This API work on direct auth
        $this->setMerchantInContext(false);
        $this->setCustomerInContext(false);
        $this->setDeviceInContext(false);

        $request = $this->request('turbo/%s/callback', [$gateway]);

        $this->resetContexts();

        $request->server($options['server'] ?? []);

        $request->json($options['content']);

        return $this->post($request);
    }

    /**
     * Enable or disable Merchant Context
     *
     * @param bool $context
     * @return P2pHelper
     */
    public function setMerchantInContext(bool $context): self
    {
        $this->isMerchantInContext = $context;

        return $this;
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
     * Enable or disable Merchant auth
     *
     * @param bool $auth
     * @return P2pHelper
     */
    public function setMerchantOnAuth(bool $auth): self
    {
        $this->isMerchantOnAuth = $auth;

        return $this;
    }

    /**
     * Scenario documentation is kept separately
     */
    public function setScenarioInContext(
        string $id = null,
        string $sub = null,
        string $contact = null,
        string $stan = null): self
    {
        $scenario = ($id === null) ? null : new Scenario($id, $sub, $contact, $stan);

        $this->scenarioInContext = $scenario;

        // If a scenario is passed to actually set it
        if ($scenario instanceof Scenario)
        {
            $callback = $scenario->getScenarioCallback();

            $wrapper = function(TestResponse $response) use ($callback, $id)
            {
                // Run for the scenario only
                if (($this->scenarioInContext instanceof Scenario) and
                    ($this->scenarioInContext->getId() === $id))
                {
                    $callback($response);
                }
            };

            if ($scenario->isSuccess())
            {
                $this->registerResponseCallback($wrapper);
            }
            else
            {
                // For failure scenario we are disabling the validation by force
                $this->withSchemaValidated(false);

                $this->withFailureResponse($wrapper);
            }
        }

        return $this;
    }

    /**
     * Only to be called once set
     * @return Scenario
     */
    public function getScenarioInContext(): Scenario
    {
        return $this->scenarioInContext;
    }

    /**
     * Reset contexts to their initial value
     */
    public function resetContexts()
    {
        $this->setMerchantInContext(true);
        $this->setCustomerInContext(true);
        $this->setDeviceInContext(true);
        $this->setScenarioInContext();
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
            if ($this->expectFailureInResponse === true)
            {
                $this->throwTestingException('Expecting a failure', $response->json());
            }

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

        $this->registerResponseCallback(function(TestResponse $response) use ($callback)
        {
            if ($this->expectFailureInResponse === true)
            {
                return $callback($response);
            }
        });

        return $this;
    }

    /**
     * Creates the request with URI and Auth
     *
     * @param string $uri Without Customer Prefix
     * @param array $parameter
     * @return P2pRequest
     */
    protected function request(string $uri, array $parameter = [] , bool $directUrl = false): P2pRequest
    {
        $request = null;

        if($directUrl == true)
        {
            $request = new P2pRequest($this->makeDirectUri($uri, $parameter));
        }
        else{
            $request = new P2pRequest($this->makeUri($uri, $parameter));
        }

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
        $content = $this->filterContent(array_replace_recursive($content, $override), function($value)
        {
            return $value !== null;
        });

        return $request->data($content);
    }

    protected function filterContent(array $array, callable $callback)
    {
        foreach ($array as &$item)
        {
            if (is_array($item) === true)
            {
                $item = $this->filterContent($item, $callback);
            }
        }

        return array_filter($array, $callback);
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
    protected function post(P2pRequest $request , bool $isDirectCall = false): array
    {
        return $this->send($request->method('post') , $isDirectCall);
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
    protected function send(P2pRequest $request , bool $isDirectCall): array
    {
        $this->runRequestHandlers($request);

        if (env('P2P_LOG_REQUESTS')) info('_LOGGER_ REQUEST', $request->trace());

        $response = $request->send();

        if (env('P2P_LOG_REQUESTS')) info('_LOGGER_ RESPONSE', $response->json());

        if($isDirectCall == false)
        {
            $this->runResponseCallbacks($response);

            $this->validateResponseJsonSchema(json_decode($response->content()));
        }
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

            if (isset($data->type) and in_array($data->type, ['sdk', 'sms', 'redirect', 'poll'], true))
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

        $this->throwTestingException('Json schema validation failed' . $jsonPath, $errors);
    }

    protected function makeUri(string $uri, array $parameters)
    {
        $url = parse_url($uri);

        if (empty($url['scheme']) === false)
        {
            return $uri;
        }

        $prefix = 'v1/upi/';

        if ($this->isMerchantOnAuth === true)
        {
            $prefix .= 'merchant/';
        }
        else if ($this->isCustomerInContext === true)
        {
            $prefix .= 'customer/';
        }

        return $prefix . sprintf($uri, ...$parameters);
    }

    protected function makeDirectUri(string $uri, array $parameters)
    {
        $url = parse_url($uri);

        $prefix = 'v1/';

        return $prefix . sprintf($uri, ...$parameters);
    }

    protected function makeServer()
    {
        $servers = [];

        if ($this->isMerchantInContext === true)
        {
            $servers = $this->serverHeaders;

            // We only expect vpa handle in request for Public and Device Auth, where merchant is in context
            $servers['HTTP_X_RAZORPAY_VPA_HANDLE'] = $this->fixtures->handle->getCode();
        }

        if ($this->isMerchantOnAuth === true)
        {
            $servers['PHP_AUTH_PW'] = 'TheKeySecretForTests';
        }
        else if ($this->isDeviceInContext === true)
        {
            $servers['PHP_AUTH_PW'] = $this->fixtures->device->getAuthToken();
        }

        if ($this->scenarioInContext instanceof Scenario)
        {
            $servers['HTTP_X_RAZORPAY_P2P_REQUEST_ID'] = $this->scenarioInContext->toRequestId();
        }

        return $servers;
    }
}

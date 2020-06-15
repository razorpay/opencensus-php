<?php

namespace RZP\Services;


use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use RZP\Http\Request\Requests;

class TerminalsService
{
    protected $app;

    protected $config;

    protected $trace;

    protected $baseUrl;

    protected $request;

    const X_RAZORPAY_TASKID = 'X-Razorpay-TaskId';

    const GATEWAY           = 'gateway';
    const MERCHANT_ID       = 'merchant_id';
    const IDENTIFIERS       = 'identifiers';
    const CURRENCY          = 'currency';
    const URL               = 'url';
    const CONTENT           = 'content';
    const CONTENT_TYPE      = 'content_type';
    const EXCEPTION         = 'exception';
    const STATUS_CODE       = 'status_code';
    const PATH              = 'path';
    const METHOD            = 'method';
    const RESPONSE          = 'response';
    const DATA              = 'data';
    const TIMEOUT           = 'timeout';
    const CONNECT_TIMEOUT   = 'connect_timeout';
    const OPTIONS           = 'options';

    const DEFAULT_TIMEOUT   = 0.1;

    const INITIATE_ONBOARDING                  = 'initiate_onboarding';
    const CREATE_TERMINAL                      = 'create_terminal';
    const FETCH_TERMINAL_BY_ID                 = 'fetch_terminal_by_id';
    const DELETE_TERMINAL_BY_ID                = 'delete_terminal_by_id';
    const FETCH_TERMINALS_FOR_MERCHANT         = 'fetch_terminals_for_merchant';
    const ADD_MERCHANT_TO_TERMINAL             = 'add_merchant_to_terminal';
    const REMOVE_MERCHANT_FROM_TERMINAL        = 'remove_merchant_from_terminal';
    const FETCH_MERCHANT_TERMINAL_BY_ID        = 'fetch_merchant_terminal_by_id';
    const FETCH_TERMINALS_FOR_MERCHANT_GATEWAY = 'fetch_terminals_for_merchant_gateway';
    const TERMINAL_ONBOARD_CALLBACK            = 'terminal_onboard_callback';
    const SYNC_DELETED_TERMINALS               = 'sync_deleted_terminals';

    // terminals service error descriptions
    const MERCHANT_HAS_ALREADY_COMPLETED_PAYPAL_ONBOARDING         = 'Merchant has already completed PayPal onboarding';
    const PAYPAL_ONBOARDING_NOT_ALLOWED_FOR_YOUR_ACCOUNT           = 'PayPal Onboarding is not allowed for your account.';
    const DUPLICATE_TERMINAL_EXIST                                 = "Duplicate Terminal Exist";

    // terminals service error descriptions mapped with exception that needs to be raised by api
    const TERMINALS_API_ERROR_CODE_MAPPING     =    [
        self::MERCHANT_HAS_ALREADY_COMPLETED_PAYPAL_ONBOARDING      =>  ErrorCode::BAD_REQUEST_TERMINAL_ONBOARDING_ALREADY_REQUESTED,
        self::PAYPAL_ONBOARDING_NOT_ALLOWED_FOR_YOUR_ACCOUNT        =>  ErrorCode::BAD_REQUEST_PAYPAL_ONBOARDING_NOT_ALLOWED,
        self::DUPLICATE_TERMINAL_EXIST                              =>  ErrorCode::BAD_REQUEST_TERMINALS_SERVICE_DUPLICATE_TERMINAL_EXISTS,
    ];

    const PARAMS = [
        self::CREATE_TERMINAL       =>   [
            self::PATH   => 'v1/terminals',
            self::METHOD => Requests::POST,
        ],
        self::FETCH_TERMINAL_BY_ID  =>   [
            self::PATH   => 'v1/terminals/%s',
            self::METHOD => Requests::GET,
        ],
        self::DELETE_TERMINAL_BY_ID => [
            self::PATH   => 'v1/terminals/%s',
            self::METHOD => Requests::DELETE,
        ],
        self::INITIATE_ONBOARDING      =>  [
            self::PATH      =>  'v2/terminals',
            self::METHOD    =>  Requests::POST,
            self::OPTIONS => [
                self::TIMEOUT         => 7, // 7 seconds
                self::CONNECT_TIMEOUT => 7, // 7 seconds
            ],
        ],
        self::FETCH_TERMINALS_FOR_MERCHANT  => [
            self::PATH   => 'v1/merchants/%s/terminals',
            self::METHOD => Requests::GET,
        ],
        self::FETCH_TERMINALS_FOR_MERCHANT_GATEWAY  => [
            self::PATH   => 'v1/merchants/%s/terminals?gateway=%s',
            self::METHOD => Requests::GET,
        ],
        self::ADD_MERCHANT_TO_TERMINAL => [
            self::PATH   => 'v1/terminal/submerchant',
            self::METHOD => Requests::POST,
        ],
        self::REMOVE_MERCHANT_FROM_TERMINAL => [
            self::PATH   => 'v1/terminal/submerchant',
            self::METHOD => Requests::DELETE,
        ],
        self::FETCH_MERCHANT_TERMINAL_BY_ID => [
            self::PATH   => 'v2/terminal/submerchant',
            self::METHOD => Requests::GET,
        ],
        self::TERMINAL_ONBOARD_CALLBACK => [
            self::PATH   => 'v2/terminal/onboard/%s/callback',
            self::METHOD => Requests::POST,
            self::OPTIONS => [
                self::TIMEOUT         => 5, // 5 seconds
                self::CONNECT_TIMEOUT => 5, // 5 seconds
            ],
        ],
        self::SYNC_DELETED_TERMINALS => [
            self::PATH   => 'v2/terminal/sync/deleted',
            self::METHOD => Requests::POST,
        ],
    ];

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $this->app['trace'];

        $this->request = $app['request'];
    }

    public function migrateTerminal(Terminal\Entity $terminal): array
    {
        $content = json_encode($terminal->toArrayWithPassword());

        $params = self::PARAMS[self::CREATE_TERMINAL];

        $response = $this->sendRequest($params[self::PATH], $content, $params[self::METHOD]);

        return $this->parseAndReturnResponse($response)['data'] ?? [];
    }

    public function fetchTerminalById(string $terminalId): array
    {
        $params = self::PARAMS[self::FETCH_TERMINAL_BY_ID];

        $path = sprintf($params[self::PATH], $terminalId);

        $response = $this->sendRequest($path, '', $params[self::METHOD]);

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    public function getTerminalsByMerchantId(string $merchantId)
    {
        $params = self::PARAMS[self::FETCH_TERMINALS_FOR_MERCHANT];

        $path = sprintf($params[self::PATH], $merchantId);

        $response = $this->sendRequest($path, '', $params[self::METHOD]);

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    public function deleteTerminalById(string $terminalId): array
    {
        $params = self::PARAMS[self::DELETE_TERMINAL_BY_ID];

        $path = sprintf($params[self::PATH], $terminalId);

        $response = $this->sendRequest($path, '', $params[self::METHOD]);

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    public function addMerchantToTerminal(Terminal\Entity $terminal, Merchant\Entity $merchant) : array
    {
        $params = self::PARAMS[self::ADD_MERCHANT_TO_TERMINAL];

        $content = [
            Terminal\Entity::TERMINAL_ID => $terminal->getId(),
            Merchant\Entity::MERCHANT_ID => $merchant->getId(),
        ];

        $response = $this->sendRequest($params[self::PATH], json_encode($content), $params[self::METHOD]);

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    public function proxyTerminalService($input, $method, $path) : array
    {
        $params = self::PARAMS[self::ADD_MERCHANT_TO_TERMINAL];

        $response = $this->sendRequest($params[self::PATH], json_encode($input), $method);

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    public function removeMerchantFromTerminal(Terminal\Entity $terminal, Merchant\Entity $merchant) : array
    {
        $params = self::PARAMS[self::REMOVE_MERCHANT_FROM_TERMINAL];

        $content = [
            Terminal\Entity::TERMINAL_ID => $terminal->getId(),
            Merchant\Entity::MERCHANT_ID => $merchant->getId(),
        ];

        $response = $this->sendRequest($params[self::PATH], $content, $params[self::METHOD]);

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    public function fetchMerchantTerminalById(string $terminalId, string $merchantId)
    {
        $params = self::PARAMS[self::FETCH_MERCHANT_TERMINAL_BY_ID];

        $path = sprintf($params[self::PATH], $terminalId, $merchantId);

        $content = [
            Terminal\Entity::TERMINAL_ID => $terminalId,
            Merchant\Entity::MERCHANT_ID => $merchantId,
        ];

        $response = $this->sendRequest($path, $content, $params[self::METHOD]);

        return $this->parseAndReturnResponse($response)[self::DATA][0] ?? [];
    }

    public function initiateOnboarding(string $merchantId, string $gateway, $identifiers = null, array $currency = []): array
    {
        $params = self::PARAMS[self::INITIATE_ONBOARDING];

        $path = $params[self::PATH];

        $content = [
            self::MERCHANT_ID   =>  $merchantId,
            self::GATEWAY       =>  $gateway,
            self::CURRENCY      =>  $currency,
            self::IDENTIFIERS   =>  $identifiers,
        ];

        $content = json_encode($content);

        $response = $this->sendRequest($path, $content, $params[self::METHOD], $params[self::OPTIONS]);

        return $this->parseAndReturnResponse($response)[self::DATA];
    }

    public function getTerminalsByMerchantIdAndGateway(string $merchantId, string $gateway)
    {
        $params = self::PARAMS[self::FETCH_TERMINALS_FOR_MERCHANT_GATEWAY];

        $path = sprintf($params[self::PATH], $merchantId, $gateway);

        $response = $this->sendRequest($path, '', $params[self::METHOD]);

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    public function terminalOnboardCallback(string $gateway, array $input)
    {
        $params = self::PARAMS[self::TERMINAL_ONBOARD_CALLBACK];

        $path = sprintf($params[self::PATH], $gateway);

        $response = $this->sendRequest($path, json_encode($input), $params[self::METHOD], $params[self::OPTIONS]);

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    public function syncDeletedTerminalsOnTerminalService(array $input)
    {
        $params = self::PARAMS[self::SYNC_DELETED_TERMINALS];

        $path = $params[self::PATH];

        $response = $this->sendRequest($path, json_encode($input));

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    protected function sendRequest(string $path, $content = '', string $method = Requests::POST, array $addditionalOptions = []): \Requests_Response
    {
        $url = $this->getBaseUrl() . $path;

        $headers = $this->getHeaders();

        $options = $this->getOptions($addditionalOptions);

        $data = [
            self::URL       => $url,
            self::METHOD    => $method,
        ];

        if (isset($content[Terminal\Entity::TERMINAL_ID]) === true)
        {
            $data[Terminal\Entity::TERMINAL_ID] = $content[Terminal\Entity::TERMINAL_ID];
        }
        if (isset($content[Terminal\Entity::MERCHANT_ID]) === true)
        {
            $data[Terminal\Entity::MERCHANT_ID] = $content[Terminal\Entity::MERCHANT_ID];
        }

        try
        {
            $this->trace->info(TraceCode::TERMINALS_SERVICE_REQUEST, $data);

            $response = $this->makeRequest($url, $headers, $content, $method, $options);

            $this->trace->info(TraceCode::TERMINALS_SERVICE_RESPONSE,
                [
                   self::STATUS_CODE => $response->status_code,
                ]);

            $parsedResponse = $this->parseAndReturnResponse($response);

            $errorDescription = isset($parsedResponse['error']['description']) ? $parsedResponse['error']['description'] : null;

            if ($response->status_code >= 500)
            {
                    throw new Exception\IntegrationException('Terminals service request failed with status code : ' . $response->status_code,
                        ErrorCode::SERVER_ERROR_TERMINALS_SERVICE_INTEGRATION_ERROR,
                        [
                            self::RESPONSE      => $parsedResponse,
                            self::STATUS_CODE   => $response->status_code,
                        ]);
            }

            if ($response->status_code >= 400)
            {
                $data = [
                    self::RESPONSE    => $parsedResponse,
                    self::STATUS_CODE => $response->status_code,
                ];

                if (array_key_exists($errorDescription, self::TERMINALS_API_ERROR_CODE_MAPPING) === true)
                {
                    throw new Exception\BadRequestException(
                        self::TERMINALS_API_ERROR_CODE_MAPPING[$errorDescription], null, $data, $errorDescription);
                }

                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TERMINALS_SERVICE_ERROR, null, $data, $errorDescription);
            }

            return $response;
        }
        catch (\Exception $exception)
        {
            $data = [
                self::EXCEPTION => $exception->getMessage(),
                self::URL       => $url,
                'data'          => $exception->getData(),
            ];

            $this->trace->error(TraceCode::TERMINALS_SERVICE_INTEGRATION_ERROR, $data);

            throw $exception;
        }
    }

    protected function makeRequest($url, $headers, $content, $method, $options)
    {
        return Requests::request($url, $headers, $content, $method, $options);
    }

    protected function parseAndReturnResponse(\Requests_Response $response): array
    {
        $responseArray = json_decode($response->body, true);

        if ($responseArray === null)
        {
            return [];
        }

        return $responseArray;
    }

    protected function getBaseUrl()
    {
        $urlConfig = 'applications.terminals_service.' . $this->getMode() . '.url';

        return $this->app['config']->get($urlConfig);
    }

    protected function getHeaders()
    {
        return [
            self::CONTENT_TYPE      => 'application/json',
            self::X_RAZORPAY_TASKID => $this->request->getTaskId()
        ];
    }

    protected function getOptions(array $additionalOptions = [])
    {
        $auth = [
            'api_user',
            $this->getPassword(),

        ];

        $defaultOptions =  [
            'auth'            => $auth,
            'timeout'         => self::DEFAULT_TIMEOUT, // 100 milliseconds
            'connect_timeout' => self::DEFAULT_TIMEOUT, // 100 milliseconds
            'show_trace'      => true,
        ];

        $options =  array_merge($defaultOptions, $additionalOptions);

        return $options;
    }

    protected function getPassword()
    {
        $passwordConfig = 'applications.terminals_service.' . $this->getMode() . '.password';

        return $this->app['config']->get($passwordConfig);
    }

    protected function getMode()
    {
        return $this->app['rzp.mode'];
    }
}

<?php

namespace RZP\Services;


use GuzzleHttp\Client;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base\PublicEntity;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use  RZP\Models\Base\Service;
use RZP\Http\Request\Requests;
use RZP\Constants\Environment;
use RZP\Models\Admin\Group\Core as core;
use RZP\Models\Admin\Admin\Service as AdminService;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Exception\BadRequestValidationFailureException;

class TerminalsService
{
    protected $app;

    protected $config;

    protected $trace;

    protected $baseUrl;

    protected $request;

    const X_RAZORPAY_TASKID         = 'X-Razorpay-TaskId';
    const X_RZP_TESTCASE_ID         = 'X-RZP-TESTCASE-ID';
    const X_DASHBOARD_MERCHANT_ID   = 'X-Dashboard-Merchant-Id';


    const GATEWAY           = 'gateway';
    const GATEWAY_ACQUIRER  = 'gateway_acquirer';
    const MERCHANT_ID       = 'merchant_id';
    const IDENTIFIERS       = 'identifiers';
    const FEATURES          = 'features';
    const SECRETS           = 'secrets';
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

    // razorx flags
    const RAZORX_FLAG_MERCHANT_INSTRUMENT_REQUEST = 'instrument_request_merchant_dashboard';

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
                self::TIMEOUT         => 20, // 20 seconds
                self::CONNECT_TIMEOUT => 20, // 20 seconds
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
        $content = json_encode($terminal->toArrayWithPassword(false));

        $params = self::PARAMS[self::CREATE_TERMINAL];

        $response = $this->sendRequest($params[self::PATH], $content, $params[self::METHOD]);

        return $this->parseAndReturnResponse($response)['data'] ?? [];
    }

    public function fetchTerminalById(string $terminalId): array
    {
        $params = self::PARAMS[self::FETCH_TERMINAL_BY_ID];

        $path = sprintf($params[self::PATH], $terminalId);

        $options = [self::TIMEOUT=> 0.5];

        $response = $this->sendRequest($path, '', $params[self::METHOD], $options);

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

    public function proxyTerminalService($input, $method, $path, $options = [], $headers = []) : array
    {
        if ($method !== "DELETE")
        {

            if ($input === [] || $input == '')
            {
                $input = '';
            }
            else
            {
                $input = json_encode($input);
            }
        }

        $fetchTerminalsPath = "v1/merchants/terminals";

        if (($path === $fetchTerminalsPath) and (isset($options[self::TIMEOUT]) === false))
        {
            $options[self::TIMEOUT] = 0.5;
            $options[self::CONNECT_TIMEOUT] = 0.5;
        }

        // For merchant dashboard requests : RaaS
        if ((strpos($path, "/mid/provider") !== false) || ((strpos($path, "optimizer/merchant/mid/methods") !== false)))
        {
            $mid = $this->app['basicauth']->getMerchant()->getId();

            $path =  str_replace_first('/mid/', "/" . $mid . "/", $path);
        }

        $response = $this->sendRequest($path, $input, $method, $options, $headers);

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    public function proxyTerminalServiceFormRequest($input, $method, $path, $options = [], $headers = [])
    {
        $response = $this->sendFormRequest($path, $input, $method, $options, $headers);

        return json_decode($response->getBody()->getContents());
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

    public function initiateOnboarding(string $merchantId, string $gateway, $identifiers = null, $features = null, array $currency = [], array $otherInputs = []): array
    {
        $params = self::PARAMS[self::INITIATE_ONBOARDING];

        $path = $params[self::PATH];

        $content = [
            self::MERCHANT_ID   =>  $merchantId,
            self::GATEWAY       =>  $gateway,
            self::CURRENCY      =>  $currency,
            self::IDENTIFIERS   =>  $identifiers,
            self::FEATURES      =>  $features,
        ];

        // for paysecure
        if (isset($otherInputs[self::GATEWAY_ACQUIRER]) === true)
        {
            $content[self::GATEWAY_ACQUIRER] = $otherInputs[self::GATEWAY_ACQUIRER];
        }
        if (isset($otherInputs[self::SECRETS]) === true)
        {
            $content[self::SECRETS] = $otherInputs[self::SECRETS];
        }

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

    public function getMerchantInstruments(array $input, array $headers): array
    {
        $merchantIds = $input['merchant_ids'];

       if ($this->areMerchantIdsAccessible($merchantIds))
       {
           $response = $this->proxyTerminalService(
               $input,
               \Requests::POST,
               'v2/composite_instrument_request',
               ['data_format' => 'body'],
               $headers
           );
           return $response;
       }
       else
       {
           $this->trace->error(TraceCode::TERMINALS_SERVICE_MERCHANT_INSTRUMENTS_REQUEST_FAILED, [
               'merchant_ids' => $merchantIds
           ]);
           return [];
       }

    }

    private function areMerchantIdsAccessible(array $merchantIds):bool
    {
        $admin = $this->app['basicauth']->getAdmin();

        $merchant = (new Merchant\Entity);

        foreach ($merchantIds as $merchantId)
        {
            $merchant->setId($merchantId);

            if (!(new core)->groupCheck($admin, $merchant))
            {
                return false;
            }

        }

        return count($merchantIds) > 0;

    }


    public function reRequestInternalInstrumentRequestsOnActivationFormSubmit(string $merchantId)
    {
        try
        {
            if ($this->isMerchantRampedForInstrumentRequests($merchantId) === false)
            {
                return;
            }

            $input = [
                'status'  => 'requested',
            ];

            $query = 'v2/internal_instrument_request?status=action_required&merchant_ids=' . $merchantId;

            $headers = $this->getMerchantHeadersForInstrumentRequest($merchantId);

            $headers['activation_form_submit'] = true;


            $response = $this->proxyTerminalService($input, Requests::PATCH, $query, [], $headers);

            $this->trace->info(TraceCode::TERMINALS_SERVICE_MERCHANT_INSTRUMENT_RE_REQUEST_RESPONSE, $response);

        }
        catch (\Throwable $throwable)
        {
            $data = [
                Merchant\Entity::MERCHANT_ID => $merchantId,
                'message'                    => $throwable->getMessage(),
                'code'                       => $throwable->getCode(),
            ];

            $this->trace->error(TraceCode::TERMINALS_SERVICE_MERCHANT_INSTRUMENT_RE_REQUEST_FAILED, $data);
        }
    }

    public function requestDefaultMerchantInstruments(string $merchantId)
    {
        try
        {
            // check whether merchant is in db
            (new Merchant\Repository)->findOrFail($merchantId);

            if ($this->isMerchantRampedForInstrumentRequests($merchantId) === false)
            {
                return;
            }

            $input = [
                Merchant\Entity::MERCHANT_ID => $merchantId,
            ];

            $headers = $this->getMerchantHeadersForInstrumentRequest($merchantId);

            $response = $this->proxyTerminalService($input, Requests::POST, 'v2/default_merchant_instrument_requests', ['timeout' => 1], $headers);

            $this->trace->info(TraceCode::TERMINALS_SERVICE_MERCHANT_DEFAULT_INSTRUMENTS_REQUEST_RESPONSE, $response);
        }
        catch (\Throwable $throwable)
        {
            $data = [
                Merchant\Entity::MERCHANT_ID => $merchantId,
                'message' => $throwable->getMessage(),
                'code' => $throwable->getCode(),
            ];

            $this->trace->error(TraceCode::TERMINALS_SERVICE_MERCHANT_DEFAULT_INSTRUMENTS_REQUEST_FAILED, $data);
        }

    }

    protected function sendRequest(string $path, $content = '', string $method = Requests::POST, array $addditionalOptions = [],
                                   array $additionalHeaders = []): \Requests_Response
    {
        $url = $this->getBaseUrl() . $path;

        $headers = $this->getHeaders($additionalHeaders);

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
        if (isset($content[Terminal\Entity::ID]) === true)
        {
            $data[Terminal\Entity::ID] = $content[Terminal\Entity::ID];
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

                if ((is_array($errorDescription) === false) and
                    (array_key_exists($errorDescription, self::TERMINALS_API_ERROR_CODE_MAPPING) === true))
                {
                    throw new Exception\BadRequestException(
                        self::TERMINALS_API_ERROR_CODE_MAPPING[$errorDescription], null, $data, $errorDescription);
                }

                if (is_array($errorDescription) === true)
                {
                    $errorDescription = implode_assoc_array($errorDescription);
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
                'data'          => $exception,
            ];

            $this->trace->error(TraceCode::TERMINALS_SERVICE_INTEGRATION_ERROR, $data);

            throw $exception;
        }
    }

    protected function sendFormRequest($url, $content, $method, $options, $headers)
    {
        $mode = $this->app['rzp.mode'];

        $tsConfigs = $this->app['config']['applications']['terminals_service'];

        $baseUrl = $tsConfigs[$mode]['url'];

        $multipart = $this->getRequestMultipart($content);

        $client   = new Client([
            'base_uri'        => $baseUrl,
            'connect_timeout' => $options['timeout'],
            'headers'         => $headers,
        ]);

        $response = $client->request($method, $url, [
            'multipart' => $multipart,
            'auth'      => ['api_user', $tsConfigs[$mode]['password']]
        ]);

        return $response;
    }

    protected function makeRequest($url, $headers, $content, $method, $options)
    {
        return Requests::request($url, $headers, $content, $method, $options);
    }

    protected function parseAndReturnResponse(\Requests_Response $response)
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

    protected function getTimeout()
    {
        $timeoutConfig = 'applications.terminals_service.timeout';

        return $this->app['config']->get($timeoutConfig);
    }

    protected function getHeaders(array $additionalHeaders)
    {
        $defaultHeaders = [
            self::CONTENT_TYPE      => 'application/json',
            self::X_RAZORPAY_TASKID => $this->request->getTaskId()
        ];

        if ($this->app->environment('production') === false)
        {
            $testCaseId = $this->app['request']->header(self::X_RZP_TESTCASE_ID);

            if (empty($testCaseId) === false)
            {
                $defaultHeaders[self::X_RZP_TESTCASE_ID] = $testCaseId;
            }
        }

        return array_merge($defaultHeaders, $additionalHeaders);
    }

    protected function getOptions(array $additionalOptions = [])
    {
        $auth = [
            'api_user',
            $this->getPassword(),

        ];

        $defaultOptions =  [
            'auth'            => $auth,
            'timeout'         => self::getTimeout(),
            'connect_timeout' => self::getTimeout(),
            'show_trace'      => true,
        ];

        $env = $this->app->environment();

        $options =  array_merge($defaultOptions, $additionalOptions);

        if(Environment::isEnvironmentQA($env) === true)
        {
            $options['timeout'] = self::getTimeout();
            $options['connect_timeout'] = self::getTimeout();
        }

        return $options;
    }

    protected function getPassword()
    {
        $passwordConfig = 'applications.terminals_service.' . $this->getMode() . '.password';

        return $this->app['config']->get($passwordConfig);
    }

    protected function getMode()
    {

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;
        return $mode;
    }

    protected function isMerchantRampedForInstrumentRequests(string $merchantId) : bool
    {
        $response = $this->app->razorx->getTreatment($merchantId, self::RAZORX_FLAG_MERCHANT_INSTRUMENT_REQUEST, $this->getMode());

        $this->trace->info(TraceCode::TERMINALS_SERVICE_MERCHANT_INSTRUMENT_RAZORX_RESPONSE, [
            'variant' => $response
        ]);

        return $response === 'on';
    }

    protected function getMerchantHeadersForInstrumentRequest(string $merchantId) : array
    {
        return [
            self::X_DASHBOARD_MERCHANT_ID => $merchantId,
        ];
    }

    protected function getRequestMultipart($input)
    {
        $multipart = [
            [
                'name'     => 'data',
                'contents' => $input['data'],
            ]
        ];

        unset($input['data']);

        foreach ($input as $key => $value)
        {
            $ext = strtolower($value->getClientOriginalExtension());

            $storageFileName = pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME);

            $movedFile = $value->move(storage_path('files/filestore'), $storageFileName . '.' . $ext);

            $multipart[] = [
                'name'     => $key,
                'contents' => fopen($movedFile->getPathname(), 'r')
            ];
        }

        return $multipart;
    }
}

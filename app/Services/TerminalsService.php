<?php

namespace RZP\Services;

use Requests;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use RZP\Exception\IntegrationException;

class TerminalsService
{
    protected $app;

    protected $config;

    protected $trace;

    protected $baseUrl;


    const URL               = 'url';
    const CONTENT           = 'content';
    const CONTENT_TYPE      = 'content_type';
    const EXCEPTION         = 'exception';
    const STATUS_CODE       = 'status_code';
    const PATH              = 'path';
    const METHOD            = 'method';
    const RESPONSE          = 'response';
    const DATA              = 'data';


    const CREATE_TERMINAL                      = 'create_terminal';
    const FETCH_TERMINAL_BY_ID                 = 'fetch_terminal_by_id';
    const DELETE_TERMINAL_BY_ID                = 'delete_terminal_by_id';
    const FETCH_TERMINALS_FOR_MERCHANT         = 'fetch_terminals_for_merchant';
    const ADD_MERCHANT_TO_TERMINAL             = 'add_merchant_to_terminal';
    const REMOVE_MERCHANT_FROM_TERMINAL        = 'remove_merchant_from_terminal';
    const FETCH_MERCHANT_TERMINAL_BY_ID        = 'fetch_merchant_terminal_by_id';
    const TERMINAL_ONBOARD_CALLBACK            = 'terminal_onboard_callback';

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
        self::FETCH_TERMINALS_FOR_MERCHANT  => [
            self::PATH   => 'v1/merchants/%s/terminals',
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
        ]
    ];

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $this->app['trace'];
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

    public function terminalOnboardCallback(string $gateway, array $input)
    {
        $params = self::PARAMS[self::TERMINAL_ONBOARD_CALLBACK];

        $path = sprintf($params[self::PATH], $gateway);

        $response = $this->sendRequest($path, $input, $params[self::METHOD]);

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    protected function sendRequest(string $path, $content = '', string $method = Requests::POST): \Requests_Response
    {
        $url = $this->getBaseUrl() . $path;

        $headers = $this->getHeaders();

        $options = $this->getOptions();

        $data = [
            self::URL       => $url,
            self::METHOD    => $method,
        ];

        if (isset($content[Terminal\Entity::TERMINAL_ID]) === true)
        {
            $data[Terminal\Entity::TERMINAL_ID] = $content[Terminal\Entity::TERMINAL_ID];
        }

        try
        {
            $this->trace->info(TraceCode::TERMINALS_SERVICE_REQUEST, $data);

            $response = Requests::request(
                $url,
                $headers,
                $content,
                $method,
                $options
            );

            $this->trace->info(TraceCode::TERMINALS_SERVICE_RESPONSE,
                [
                   self::STATUS_CODE => $response->status_code,
                ]);

            if ($response->status_code >= 400)
            {
                throw new IntegrationException('Terminals service request failed with status code : ' . $response->status_code,
                ErrorCode::SERVER_ERROR_TERMINALS_SERVICE_INTEGRATION_ERROR,
                [
                    self::RESPONSE => $this->parseAndReturnResponse($response)]
                );
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
        ];
    }

    protected function getOptions()
    {
        $auth = [
            'api_user',
            $this->getPassword(),

        ];

        return [
            'auth' => $auth
        ];
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

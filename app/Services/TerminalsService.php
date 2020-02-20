<?php

namespace RZP\Services;

use Requests;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Exception\IntegrationException;

class TerminalsService
{
    protected $app;

    protected $config;

    protected $mode;

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


    const CREATE_TERMINAL         = 'create_terminal';
    const FETCH_TERMINAL_BY_ID    = 'fetch_terminal_by_id';
    const DELETE_TERMINAL_BY_ID   = 'delete_terminal_by_id';

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
    ];

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $this->app['trace'];

        $this->mode = $this->app['rzp.mode'];
    }

    public function migrateTerminal(Terminal\Entity $terminal): array
    {
        $content = json_encode($terminal->toArrayWithPassword());

        $params = self::PARAMS[self::CREATE_TERMINAL];

        $response = $this->sendRequest($params[self::PATH], $content, $params[self::METHOD]);

        return $this->parseAndReturnResponse($response)['data'];
    }

    public function fetchTerminalById(string $terminalId): array
    {
        $params = self::PARAMS[self::FETCH_TERMINAL_BY_ID];

        $path = sprintf($params[self::PATH], $terminalId);

        $response = $this->sendRequest($path, '', $params[self::METHOD]);

        return $this->parseAndReturnResponse($response)[self::DATA];
    }

    public function deleteTerminalById(string $terminalId): array
    {
        $params = self::PARAMS[self::DELETE_TERMINAL_BY_ID];

        $path = sprintf($params[self::PATH], $terminalId);

        $response = $this->sendRequest($path, '', $params[self::METHOD]);

        return $this->parseAndReturnResponse($response)[self::DATA];
    }

    protected function sendRequest(string $path, $content = '', string $method = Requests::POST): \Requests_Response
    {
        $url = $this->getBaseUrl($this->mode) . $path;

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

    protected function getBaseUrl(string $mode)
    {
        $urlConfig = 'applications.terminals_service.' . $mode . '.url';

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
        $passwordConfig = 'applications.terminals_service.' . $this->mode . '.password';

        return $this->app['config']->get($passwordConfig);

    }
}

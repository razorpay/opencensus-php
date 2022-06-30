<?php

namespace RZP\Services;

use App;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Exception\IntegrationException;
use RZP\Http\Controllers\CareProxyController;

class CareServiceClient
{
    protected $app;
    protected $config;

    const CONTENT_TYPE                     = 'Content-Type';
    const AUTHORIZATION                    = 'Authorization';
    const X_REQUEST_ID                     = 'X-Request-Id';
    const TIMEOUT                          = 'timeout';
    const DEFAULT_TIMEOUT_DURATION_SECONDS = 20;


    //Click to Call inputs fields
    const DETAILS       = 'details';
    const CONTACT       = 'contact';
    const TICKET_ID     = 'ticket_id';
    const TICKET        = 'ticket';
    const OTP           = 'otp';
    const SUBJECT       = 'subject';
    const ID            = 'id';
    const CREATED_AT    = 'created_at';

    public function __construct($app = null)
    {
        if ($app === null)
        {
            $app = App::getFacadeRoot();
        }

        $this->app = $app;

        $this->setConfig();
    }

    public function fetch($type, $id)
    {
        $input = [
            'id'    => $id,
            'type'  => $type,
        ];

        $input = $this->addAdminDetails($input);

        // this is due to 1 level nesting of same "type" parameter on account of using any of proto fields
        $entity = $this->adminProxyRequest('/twirp/rzp.care.admin.v1.AdminService/GetById', $input)[$type];

        return $this->processFetchEntity($entity);
    }

    public function fetchMultiple($type, $params)
    {
        $input = [
            'type'      => $type,
            'params'     => $params
        ];

        $input = $this->addAdminDetails($input);

        // this is due to 2 level nesting of same "type" parameter on account of using any of + repeated proto fields
        $entities = $this->adminProxyRequest('/twirp/rzp.care.admin.v1.AdminService/FetchByParams', $input)[$type][$type] ??  [];


        $response = [];

        foreach ($entities as $entity)
        {
            $response[] = $this->processFetchEntity($entity);
        }

        return $response;
    }

    public function dashboardProxyRequest($path, $input)
    {
        $input = $this->addMerchantDetails($input);

        if ($path == CareProxyController::CREATE_INSTANT_CALLBACK)
        {
            $this->app['trace']->info(TraceCode::CLICK_TO_CALL_REQUEST, [
                'input' => $this->getRedactedInputForClickToCall($input)
            ]);
        }

        return $this->sendRequestAndProcessResponse($this->getBaseUrl() .$path, Requests::POST, $input);
    }

    public function getRedactedInputForClickToCall($input): array
    {
        if (array_key_exists(self::DETAILS, $input))
        {
            $input[self::DETAILS][self::CONTACT] = substr($input[self::DETAILS][self::CONTACT], -4);

            if (array_key_exists(self::OTP, $input[self::DETAILS]) === true)
            {
                unset($input[self::DETAILS][self::OTP]);
            }

            $redactedTicket = [
                self::TICKET_ID     => $input[self::DETAILS][self::TICKET][self::TICKET_ID],
                self::ID            => $input[self::DETAILS][self::TICKET][self::ID],
                self::SUBJECT       => $input[self::DETAILS][self::TICKET][self::SUBJECT],
                self::CREATED_AT    => $input[self::DETAILS][self::TICKET][self::CREATED_AT],
            ];

            unset($input[self::DETAILS][self::TICKET]);

            $input[self::DETAILS][self::TICKET] = $redactedTicket;
        }

        return $input;
    }

    public function cronProxyRequest($path, $input)
    {
        return $this->sendRequestAndProcessResponse($this->getBaseUrl() .$path, Requests::POST, $input);
    }

    public function darkProxyRequest($path, $input)
    {
        $this->app['trace']->info(TraceCode::CARE_DARK_PATH, [
            'path'       => $path,
        ]);

        return $this->sendRequestAndProcessResponse($this->getDarkBaseUrl() .$path, Requests::POST, $input);
    }

    public function myOperatorWebhookProxyRequest($path, $input)
    {
        $this->app['trace']->info(TraceCode::MYOPERATOR_WEBHOOK, [
            'path'       => $path,
        ]);

        return $this->sendRequestAndProcessResponse($this->getBaseUrl() .$path, Requests::POST, $input);
    }

    public function adminProxyRequest($path, $input)
    {
        $input = $this->addAdminDetails($input);

        return $this->sendRequestAndProcessResponse($this->getBaseUrl() .$path, Requests::POST, $input);
    }

    public function chatProxyRequest($path, $input)
    {
        return $this->sendRequestAndProcessResponse($this->getBaseUrl() .$path, Requests::POST, $input);
    }

    protected function sendRequestAndProcessResponse($path, $method, $content)
    {
        $this->app['trace']->info(TraceCode::CARE_SERVICE_REQUEST, [
            'path'   => $path,
            'method' => $method,
        ]);

        $response = $this->sendRequest($path, $method, $content);

        $this->app['trace']->info(TraceCode::CARE_SERVICE_RESPONSE, [
            'status_code' => $response->status_code,
        ]);

        return $this->processResponse($response);
    }

    public function sendRequest($url, $method, $content, $headers = [], $options = [])
    {
        $headers = array_merge($headers, $this->getHeaders());

        $options = array_merge($options, $this->getOptions());

        if (empty($content) === true)
        {
            $content = '{}';
        } else
        {
            $content = json_encode($content);
        }

        return Requests::request($url, $headers, $content, $method, $options);
    }

    protected function processResponse(\Requests_Response $response): array
    {
        if ($response->status_code >= 500)
        {
            $this->app['trace']->info(TraceCode::CARE_SERVICE_ERROR, [
                'response_body' => $response->body,
            ]);

            throw new IntegrationException('care_service integration exception',
                ErrorCode::SERVER_ERROR);
        }

        $parsedResponse = $this->parseResponse($response);

        if ($response->status_code >= 400)
        {
            $description = $parsedResponse['msg'] ?? '';

            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR,
                null,
                $parsedResponse,
                $description);
        }

        return $parsedResponse;
    }

    protected function setConfig()
    {
        $configPath = 'applications.care';

        $this->config = $this->app['config']->get($configPath);
    }

    protected function getBaseUrl()
    {
        return $this->config['host'];
    }

    protected function getDarkBaseUrl()
    {
        return $this->config['dark-host'];
    }

    protected function getHeaders()
    {
        return [
            self::CONTENT_TYPE  => 'application/json',
            self::AUTHORIZATION => $this->getAuthorizationHeader(),
            self::X_REQUEST_ID  => $this->app['request']->getTaskId(),
        ];
    }

    protected function getAuthorizationHeader()
    {
        return 'Basic ' . base64_encode('api:' . $this->config['password']);
    }

    protected function parseResponse(\Requests_Response $response)
    {
        $responseArray = json_decode($response->body, true);

        if ($responseArray === null)
        {
            return [];
        }

        return $responseArray;
    }

    protected function addMerchantDetails($input)
    {
        $user = $this->app['basicauth']->getUser();

        $input['merchant'] = [
            'id'        => $this->app['basicauth']->getMerchantId(),
        ];

        if (empty($user) === false && empty($user->getId()) === false)
        {
            $input['merchant']['user_id']  = $user->getId();
        }

        return $input;
    }

    protected function addAdminDetails($input)
    {
        $input['admin'] = [
            'id' => $this->app['basicauth']->getAdmin()->getId(),
        ];

        return $input;
    }

    protected function getOptions()
    {
        return [
            self::TIMEOUT => self::DEFAULT_TIMEOUT_DURATION_SECONDS,
        ];
    }

    protected function processFetchEntity($entity)
    {
        // twirp+protobuf serializes int fields to string. but this breaks display on dashboard for entity fetch
        // hence manually casting these 2 fields till a workaround is found
        if (isset($entity['created_at']) === true)
        {
            $entity['created_at'] = (int)$entity['created_at'];
        }

        if (isset($entity['updated_at']) === true)
        {
            $entity['updated_at'] = (int)$entity['updated_at'];
        }

        return $entity;
    }
}

<?php

namespace RZP\Services;

use Request;
use Throwable;
use Requests_Session;
use Requests_Response;

use RZP\Error\ErrorCode;
use Illuminate\Support\Str;
use RZP\Exception\ServerErrorException;
use RZP\Exception\BadRequestValidationFailureException;

class Stork
{
    const WEBHOOK = 'webhook';

    /**
     * If actual http requests should be made.
     * Dual write is mocked in unit tests.
     * @var boolean
     */
    public $mock;

    /**
     * Name of owning service for requests to stork.
     * @var string
     */
    public $service;

    /**
     * @var Requests_Session
     */
    public $request;

    /**
     * This method is implements supporting listing requests for admin
     * dashboard via stork external service.
     * @param  string $entity - Name of entity e.g. webhooks, messages.
     * @param  array  $input  - Request query/input.
     * @return array
     */
    public function fetchMultiple(string $entity, array $input): array
    {
        $this->init(app('rzp.mode'));

        switch ($entity)
        {
            case self::WEBHOOK:
                $path ='/twirp/rzp.stork.webhook.v1.WebhookAPI/List';
                $input['service'] = $this->service;
                break;
            default:
                throw new BadRequestValidationFailureException('Invalid entity name');
        }

        $res = $this->request($path, $input);
        $res = json_decode($res->body, true) ?: [];

        return $this->formatListResponse($entity, $res);
    }

    /**
     * This method is implements supporting get requests for admin
     * dashboard via stork external service.
     * @param  string $entity - Name of entity e.g. webhooks, messages.
     * @param  string $id     - Entity id.
     * @param  array  $input  - Request query/input.
     * @return array
     */
    public function fetch(string $entity, string $id, array $input): array
    {
        $this->init(app('rzp.mode'));

        switch ($entity)
        {
            case self::WEBHOOK:
                $path ='/twirp/rzp.stork.webhook.v1.WebhookAPI/Get';
                $input['webhook_id'] = $id;
                $input['service'] = $this->service;
                break;
            default:
                throw new BadRequestValidationFailureException('Invalid entity name');
        }

        $res = $this->request($path, $input);
        $res = json_decode($res->body, true);

        return $this->formatGetResponse($entity, $res);
    }

    public function init(string $mode)
    {
        $config = config('stork');

        $this->mock = $config['mock'];
        $this->service = $config['service_prefix'] . $config['auth'][$mode]['user'];
        $this->request = new Requests_Session(
            $config['url'],
            // Common headers for requests.
            [
                'X-Request-ID' => Request::getTaskId(),
                'Content-Type' => 'application/json',
            ],
            [],
            // Options and authentication for requests.
            [
                'connect_timeout' => 0.35, // Request to stork gets timed out after this
                'auth' => [$config['auth'][$mode]['user'], $config['auth'][$mode]['pass']],
            ]);
    }

    public function request(string $path, array $payload): Requests_Response
    {
        // Just for tests!
        if ($this->mock === true)
        {
            return new Requests_Response;
        }

        $res = null;
        $exception = null;

        try
        {
            $res = $this->request->post($path, [], empty($payload) ? '{}' : json_encode($payload));
        }
        catch (Throwable $e)
        {
            $exception = $e;

            $res = $this->retryStorkRequest($exception, $path, $payload, $res);
        }

        return $res;
    }

    protected function retryStorkRequest($exception, string $path, array $payload, $res)
    {
        if (($exception !== null) and ($exception instanceof \Requests_Exception))
        {
           if (Str::contains($exception->getMessage(), "Operation timed out"))
           {
               try
               {
                   $res = $this->request->post($path, [], empty($payload) ? '{}' : json_encode($payload));
               }
               catch (Throwable $e)
               {
                   $exception = $e;
               }

               $this->throwStorkException($exception, $res, $path);

               return $res;
           }
        }
    }

    protected function throwStorkException($exception, $res, $path)
    {
        if (($exception !== null) or ($res->success !== true))
        {
            throw new ServerErrorException(
                "Failed to complete request",
                ErrorCode::SERVER_ERROR_STORK_FAILURE,
                ['req_path' => $path] + ($res ? ['resp_status_code' => $res->status_code, 'resp_body' => $res->body] : []),
                $exception);
        }
    }

    protected function formatListResponse(string $entity, array $res): array
    {
        switch ($entity)
        {
            case self::WEBHOOK:
                $items = array_map(function ($v) { return $this->formatWebhook($v); }, $res['webhooks']);
                break;
            default:
                $items = [];
                break;
        }

        return [
            'entity' => 'collection',
            'count'  => count($items),
            'items'  => $items,
        ];
    }

    protected function formatGetResponse(string $entity, array $res): array
    {
        switch ($entity)
        {
            case self::WEBHOOK:
                return $this->formatWebhook($res['webhook']);
            default:
                return [];
        }
    }

    protected function formatWebhook(array $res): array
    {
        return array_except($res, ['secret']);
    }
}

<?php

namespace RZP\Services\RzpKms;

use GuzzleHttp\RequestOptions;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Support\Facades\App;


class KeyManagementService
{
    const CONTENT_TYPE        = 'content-type';
    const CONTENT_TYPE_JSON   = 'application/json';
    const X_TASK_ID           = 'X-Razorpay-TaskId';
    const X_MERCHANT_ID       = 'X-Merchant-ID';
    const X_INTERNAL_APP      = 'X-Internal-App';

    const KEY_MANAGEMENT_SERVICE = 'KeyManagementService';

    protected $client;

    protected $options = [];

    protected $trace;

    protected $config;

    protected $mode;

    protected $app;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.key_management_service');

        $this->client = new Guzzle([
            'base_uri' => $this->config['url'],
            'auth'     => [
                $this->config['username'],
                $this->config['secret'],
            ]]);
    }

    public function sendRequest($path, $method, $payload=[])
    {
        $url = $this->config['url'] . $path;

        $this->options = [
            'headers' => $this->getRequestHeaders(),
        ];

        if (isset($payload))
        {
            if (isset($payload['ord_id'])) {
                $payload['ord_id'] = preg_replace('/^org_/', '', $payload['ord_id']);
            }

            if ($method === Requests::GET)
            {
                $url = $url . '?' . http_build_query($payload);
            }
            else
            {
                $this->options[RequestOptions::JSON] = $payload;
            }
        }


        $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_REQUEST, [
            'url'           => $url,
            'service'       => self::KEY_MANAGEMENT_SERVICE,
            'payload'       => $payload,
            'headers'       => $this->options['headers'],
        ]);

        try
        {
            $response = $this->client->request($method, $url, $this->options);

            return $this->formatResponse($response);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::KEY_MANAGEMENT_SERVICE_INTEGRATION_ERROR, [
                'error_message' => $e->getMessage(),
                'url'  => $url,
            ]);

            throw $e;
        }
    }

    private function getRequestHeaders()
    {
        return [
            self::CONTENT_TYPE      => self::CONTENT_TYPE_JSON,
            self::X_TASK_ID         => $this->app['request']->getTaskId(),
            self::X_MERCHANT_ID     => $this->app['basicauth']->getMerchantId() ?? '',
            self::X_INTERNAL_APP    => $this->app['basicauth']->getInternalApp() ?? '',
        ];
    }

    private function formatResponse($response)
    {
        if ($response->status_code >= 500) {

            throw new Exception\ServerErrorException('Error completing the request',
                ErrorCode::SERVER_ERROR);

        } else if ($response->status_code >= 400) {

            $error = json_decode($response->body);
            $errorDescription = $error->error->description;

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null, null, $errorDescription);
        }

        if ($response->body === "null" or $response->body === '') {
            throw new Exception\ServerErrorException('Error completing the request',
                ErrorCode::SERVER_ERROR);
        }

        $responseArray = json_decode($response->getBody(), true);

        $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_RESPONSE, [
            'response'  => $responseArray,
            'service'   => self::KEY_MANAGEMENT_SERVICE,
        ]);

        return $responseArray;
    }
}

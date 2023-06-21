<?php

namespace RZP\Services;

use GuzzleHttp\RequestOptions;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Support\Facades\App;


class PaymentsCrossBorderClient
{
    const CONTENT_TYPE        = 'content-type';
    const CONTENT_TYPE_JSON   = 'application/json';
    const X_TASK_ID           = 'X-Razorpay-TaskId';
    const X_MERCHANT_ID       = 'X-Merchant-ID';
    const X_INTERNAL_APP      = 'X-Internal-App';

    const PAYMENTS_CROSS_BORDER = 'PaymentsCrossBorder';

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

        $this->config = $app['config']->get('applications.payments_cross_border_service');

        $this->client = new Guzzle([
            'base_uri' => $this->config['url'][$this->mode],
            'auth'     => [
                $this->config['username'],
                $this->config['password'],
            ]]);
    }

    public function makeRequest($path, $method, $payload=[])
    {
        $url = $this->config['url'][$this->mode] . $path;

        $this->options = [
            'headers' => $this->getRequestHeaders(),
        ];
        if (isset($payload))
        {
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
            'service'       => self::PAYMENTS_CROSS_BORDER,
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
            $this->trace->error(TraceCode::PAYMENTS_CROSS_BORDER_INTEGRATION_ERROR, [
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
        $responseArray = json_decode($response->getBody(), true);

        $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_RESPONSE, [
            'response'  => $responseArray,
            'service'   => self::PAYMENTS_CROSS_BORDER,
        ]);

        return $responseArray;
    }
}

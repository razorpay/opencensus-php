<?php

namespace RZP\Services;

use Request;
use GuzzleHttp\RequestOptions;
use RZP\Constants\Mode;
use RZP\Http\RequestHeader;
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
    //get document url
    const GET_DOCUMENTS = 'v1/documents';
    const CONFIGURE_DCS = 'v1/configure-dcs';
    const GET_CONFIGURE_DCS = 'v1/configure-dcs/%s';

    const GET = 'GET';
    const POST = 'POST';

    const GET_LRS_QUOTE = 'v1/lrs_quote';

    const UPDATE_PAYMENT_STATUS = 'v1/payment_status/{order_id}';

    const PAYMENTS_CROSS_BORDER_URLS = [
        "GET_DOCUMENTS" => self::GET_DOCUMENTS,
        "CONFIGURE_DCS" => self::CONFIGURE_DCS,
        "GET_CONFIGURE_DCS" => self::GET_CONFIGURE_DCS,
        "GET_LRS_QUOTE" => self::GET_LRS_QUOTE,
        "UPDATE_PAYMENT_STATUS" => self::UPDATE_PAYMENT_STATUS,
    ];

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
        $headers = [
            self::CONTENT_TYPE      => self::CONTENT_TYPE_JSON,
            self::X_TASK_ID         => $this->app['request']->getTaskId(),
            self::X_MERCHANT_ID     => $this->app['basicauth']->getMerchantId() ?? '',
            self::X_INTERNAL_APP    => $this->app['basicauth']->getInternalApp() ?? '',
        ];

        if(!empty(Request::header(RequestHeader::DEV_SERVE_USER))){
            $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);
        }

        return $headers;
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

    public function getDocuments($input)
    {
        $url = self::PAYMENTS_CROSS_BORDER_URLS['GET_DOCUMENTS'];

        try {
            return $this->makeRequest($url, self::GET, $input);
        } catch (\Throwable $e) {
            $this->trace->info(TraceCode::PAYMENTS_CROSS_BORDER_DOCUMENT_FETCH_ERROR,[
                'error' => $e,
            ]);

            throw $e;
        }
    }

    public function getLRSQuote($input)
    {
        $url = self::PAYMENTS_CROSS_BORDER_URLS['GET_LRS_QUOTE'];

        try {
            return $this->makeRequest($url, self::GET, $input);
        } catch (\Throwable $e) {
            $this->trace->info(TraceCode::PAYMENTS_CROSS_BORDER_LRS_QUOTE_FETCH_ERROR,[
                'error' => $e,
            ]);

            throw $e;
        }
    }

    public function updatePaymentStatus($input)
    {
        $url = self::PAYMENTS_CROSS_BORDER_URLS['UPDATE_PAYMENT_STATUS'];
        $url = str_replace('{order_id}', $input['order_id'], $url);
        unset($input['order_id']);
        try {
            return $this->makeRequest($url, 'POST', $input);
        } catch (\Throwable $e) {
            $this->trace->info(TraceCode::PAYMENTS_CROSS_BORDER_PAYMENT_STATUS_UPDATE_ERROR,[
                'error' => $e,
            ]);

            throw $e;
        }
    }


    public function postDCSConfiguration($input)
    {
        $url = self::PAYMENTS_CROSS_BORDER_URLS['CONFIGURE_DCS'];

        try {
            return $this->makeRequest($url, self::POST, $input);
        } catch (\Throwable $e) {
            $this->trace->info(TraceCode::PAYMENTS_CROSS_BORDER_POST_DCS_CONFIG_ERROR,[
                'input' => $input,
                'error' => $e,
            ]);
        }
        return ["success" => false];
    }

    public function getDCSConfiguration($merchantId)
    {
        $url = sprintf(self::PAYMENTS_CROSS_BORDER_URLS['GET_CONFIGURE_DCS'], $merchantId);

        try {
            return $this->makeRequest($url, self::GET);
        } catch (\Throwable $e) {
            $this->trace->info(TraceCode::PAYMENTS_CROSS_BORDER_GET_DCS_CONFIG_ERROR,[
                'merchant_id' => $merchantId,
                'error' => $e,
            ]);
        }
        return ["success" => false];
    }
}

<?php

namespace RZP\Services;

use Requests;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Drip
{
    // Drip Actions
    const CREATED   = 'created';
    const ACTIVATED = 'activated';

    // Drip Urls
    const SUBSCRIBERS = 'subscribers';

    const CONTENT_TYPE = 'application/vnd.api+json';

    // Drip action to bool map
    const ACTIVATED_ACTION_MAP = [
        self::CREATED   => false,
        self::ACTIVATED => true,
    ];

    // drip url maps
    const DRIP_URL_MAP = [
        self::SUBSCRIBERS => '/subscribers',
    ];

    public function __construct($app)
    {
        $this->config = $app['config']->get('applications.drip');

        $this->baseUrl = $this->config['url'];

        $this->mode = $app['rzp.mode'];

        $this->token = $this->config['token'];

        $this->accountId = $this->config['accountId'];

        $this->trace = $app['trace'];
    }

    public function sendDripMerchantInfo($action, $merchant)
    {
        switch ($action)
        {
            case self::CREATED:
                $this->sendDripMerchantCreated($merchant);

            case self::ACTIVATED:
                $this->sendDripMerchantActivated($merchant);
        }
    }

    public function sendDripMerchantCreated($merchant)
    {
        $action = self::ACTIVATED_ACTION_MAP[self::CREATED];

        $data = $this->createDripSubscribersArray($action, $merchant);

        $response = $this->sendRequest(self::DRIP_URL_MAP[self::SUBSCRIBERS], $data, 'post');
    }

    public function sendDripMerchantActivated($merchant)
    {
        $action = self::ACTIVATED_ACTION_MAP[self::ACTIVATED];

        $data = $this->createDripSubscribersArray($action, $merchant);

        $response = $this->sendRequest(self::DRIP_URL_MAP[self::SUBSCRIBERS], $data, 'post');
    }

    protected function sendRequest($url, $data, $method)
    {
        $url = $this->baseUrl . $this->accountId . $url;

        $content = json_encode($data);

        $headers['Content-Type'] = self::CONTENT_TYPE;

        $options['auth'] = [$this->token, ""];

        $request = [
            'url'     => $url,
            'method'  => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $content
        ];

        $this->trace->info(TraceCode::DRIP_REQUEST, ['request' => $request]);

        try
        {
            $response = Requests::$method(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['options']);
        }
        catch(\Requests_Exception $e)
        {
            throw $e;
        }

        $this->trace->info(TraceCode::DRIP_RESPONSE, ['response' => $response->body]);

        return json_decode($response->body, true);
    }

    protected function createDripSubscribersArray($action, $merchant)
    {
        $data = [
            'subscribers' => [
                [
                    'email' => $merchant->getEmail(),
                    'custom_fields' => [
                        'activated' => $action
                    ],
                ],
            ],
        ];

        return $data;
    }

    protected function getRequestArray($content, $relativeUrl)
    {
        $request = [
            'url'     => $this->getUrl($relativeUrl),
            'content' => $content
        ];

        return $request;
    }

    protected function getUrl($relativeUrl)
    {
        return $this->baseUrl . '/' . $this->accountId . '/' . $relativeUrl;
    }
}

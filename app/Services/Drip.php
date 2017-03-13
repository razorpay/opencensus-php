<?php

namespace RZP\Services;

use RZP\Exception;
use RZP\Jobs\RequestJob;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Illuminate\Foundation\Bus\DispatchesJobs;

class Drip
{
    use DispatchesJobs;

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

    // Drip Url maps
    const DRIP_URL_MAP = [
        self::SUBSCRIBERS => '/subscribers',
    ];

    public function __construct($app)
    {
        $this->config = $app['config']->get('applications.drip');

        $this->baseUrl = $this->config['url'];

        $this->token = $this->config['token'];

        $this->accountId = $this->config['accountId'];
    }

    public function sendDripMerchantInfo($action, $merchant)
    {
        switch ($action)
        {
            case self::ACTIVATED:
                $this->sendDripMerchantActivatedOrNot(self::ACTIVATED, $merchant);
                break;

            default:
                $this->sendDripMerchantActivatedOrNot(self::CREATED, $merchant);
                break;
        }
    }

    public function sendDripMerchantActivatedOrNot($activated, $merchant)
    {
        $action = self::ACTIVATED_ACTION_MAP[$activated];

        $data = $this->createDripSubscribersArray($action, $merchant);

        $this->sendRequest(self::DRIP_URL_MAP[self::SUBSCRIBERS], $data, 'post');
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

        //
        // Dispatching the job into the queue
        //
        $job = new RequestJob($request);

        $this->dispatch($job);
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
}

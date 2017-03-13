<?php

namespace RZP\Services;

use RZP\Exception;
use RZP\Jobs\RequestJob;
use RZP\Error\ErrorCode;
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
            case self::CREATED:
                $this->sendDripMerchantActivatedOrNot(self::CREATED, $merchant);
                break;

            case self::ACTIVATED:
                $this->sendDripMerchantActivatedOrNot(self::ACTIVATED, $merchant);
                break;

            default:
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_DRIP_ACTION);
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

        //
        // Dispatching the job into the queue
        //
        $job = new RequestJob($url, $method, self::CONTENT_TYPE, $this->token, $content);

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

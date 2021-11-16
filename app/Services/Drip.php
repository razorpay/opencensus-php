<?php

namespace RZP\Services;

use RZP\Exception;
use RZP\Jobs\RequestJob;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

class Drip
{
    protected $config;
    protected $baseUrl;
    protected $token;
    protected $accountId;
    protected $trace;

    // Drip Actions
    const CREATED       = 'created';
    const ACTIVATED     = 'activated';
    const KEY_GENERATED = 'keyGenerated';

    // Drip Urls
    const SUBSCRIBERS = 'subscribers';

    const CONTENT_TYPE = 'application/vnd.api+json';

    // Drip action to bool map
    const ACTION_MAP = [
        self::CREATED       => false,
        self::ACTIVATED     => true,
        self::KEY_GENERATED => true,
    ];

    const ACTION_KEY_MAP = [
        self::CREATED       => self::ACTIVATED,
        self::ACTIVATED     => self::ACTIVATED,
        self::KEY_GENERATED => self::KEY_GENERATED,
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

        $this->trace = $app['trace'];
    }

    public function sendDripMerchantInfo(Merchant\Entity $merchant, string $action)
    {
        switch ($action)
        {
            //
            // When the merchant is created we tell drip that he is not activated
            //
            case self::CREATED:
                $this->sendDripMerchantAction($merchant, self::CREATED);
                break;

            case self::ACTIVATED:
                $this->sendDripMerchantAction($merchant, self::ACTIVATED);
                break;

            case self::KEY_GENERATED:
                $this->sendDripMerchantAction($merchant, self::KEY_GENERATED);
                break;

            default:
                throw new Exception\LogicException(
                    'BAD_REQUEST_INVALID_DRIP_ACTION',
                    null,
                    [
                        'action'      => $action,
                        'merchant_id' => $merchant->getId(),
                    ]);
        }
    }

    public function sendDripMerchantAction(Merchant\Entity $merchant, string $action)
    {
        $key = self::ACTION_KEY_MAP[$action];

        $value = self::ACTION_MAP[$action];

        $data = $this->createDripSubscribersArray($merchant, $key, $value);

        $this->sendRequest(self::DRIP_URL_MAP[self::SUBSCRIBERS], $data, 'post');
    }

    protected function sendRequest(string $url, array $data, string $method)
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

        RequestJob::dispatch($request);
    }

    protected function createDripSubscribersArray(Merchant\Entity $merchant, string $key, bool $value)
    {
        $data = [
            'subscribers' => [
                [
                    'email' => $merchant->getEmail() ?? null,
                    'custom_fields' => [
                        $key => $value
                    ],
                ],
            ],
        ];

        return $data;
    }
}

<?php

namespace RZP\PushNotifications\Base;

use App;
use Razorpay\Trace\Logger;
use RZP\Constants\Mode;
use RZP\Constants\Product;

class Stork
{
    protected $app;

    /**
     * @var string
     */
    protected $mode;

    /**
     * Product value is used to figure out service value to use for stork communication.
     * @var string
     */
    protected $product;

    /**
     * @var \RZP\Services\Stork
     */
    protected $service;

    /**
     * @var Logger
     */
    protected $trace;

    const SEND_PUSH_NOTIFICATION_ROUTE = '/twirp/rzp.stork.message.v1.MessageAPI/Create';

    public function __construct(string $mode = Mode::LIVE, string $product = Product::PRIMARY)
    {

        $this->app     = App::getFacadeRoot();
        $this->mode    = $mode;
        $this->product = $product;
        $this->service = app('stork_service');
        $this->trace   = app('trace');

        $this->service->init($this->mode, $this->product);
    }

    public function sendPushNotification(array $payload): array
    {
        $payload['service'] = $this->service->service;

        $payload['owner_id']   = (empty($payload['owner_id']) === true)   ? '10000000000000' : $payload['owner_id'];
        $payload['owner_type'] = (empty($payload['owner_type']) === true) ? 'merchant'       : $payload['owner_type'];

        $params = [
            'message' => $payload
        ];
        $res = $this->service->requestAndGetParsedBody(self::SEND_PUSH_NOTIFICATION_ROUTE, $params);

        return $res;
    }
}

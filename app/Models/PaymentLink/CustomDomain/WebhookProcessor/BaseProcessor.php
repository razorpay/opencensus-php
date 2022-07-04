<?php

namespace RZP\Models\PaymentLink\CustomDomain\WebhookProcessor;

use Razorpay\Trace\Logger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

abstract class BaseProcessor
{
    /**
     * @var \Illuminate\Support\Facades\App
     */
    protected $app;

    /**
     * @var Logger
     */
    protected $trace;

    /**
     * @var \Illuminate\Support\Collection
     */
    private $data;

    function __construct(array $webhookData)
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->data = collect($webhookData);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getData(): Collection
    {
        return $this->data;
    }
}

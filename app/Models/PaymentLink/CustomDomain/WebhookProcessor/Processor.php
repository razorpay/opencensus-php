<?php

namespace RZP\Models\PaymentLink\CustomDomain\WebhookProcessor;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\App;

class Processor implements IWebhookHandler
{
    const EVENTY_KEY = "event";

    /**
     * @var \Illuminate\Support\Facades\App
     */
    protected $app;

    /**
     * @var Logger
     */
    protected $trace;

    /**
     * @var IProcessor
     */
    private $handler;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];
    }

    /**
     * @param array $data
     *
     * @return \RZP\Models\PaymentLink\CustomDomain\WebhookProcessor\IWebhookHandler
     */
    public function process(array $data): IWebhookHandler
    {
        $event = $data[self::EVENTY_KEY];

        $explodes = explode(".", $event);

        $namespace = __NAMESPACE__;

        for ($i=0; $i<count($explodes);$i++)
        {
            $explodes[$i] = Str::studly(Str::lower($explodes[$i]));
        }

        $className = implode("", $explodes);
        $className .= "Processor";

        // all classes will under name space RZP\Models\PaymentLink\CustomDomain\WebhookProcessor\<Handler>
        $fqcn = $namespace . "\\" . $className;

        try
        {
            $this->setHandler(new $fqcn($data));
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::CDS_WEBHOOK_PROCESSING_ERROR, [
                "data"          => $data,
                "error_message" => $e->getMessage(),
            ]);
        }

        return $this;
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        try
        {
            $this->getHandler()->handle();
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::CDS_WEBHOOK_PROCESSING_ERROR, [
                "error_message" => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return \RZP\Models\PaymentLink\CustomDomain\WebhookProcessor\IProcessor
     */
    public function getHandler(): IProcessor
    {
        return $this->handler;
    }

    /**
     * @param \RZP\Models\PaymentLink\CustomDomain\WebhookProcessor\IProcessor $handler
     *
     * @return void
     */
    public function setHandler(IProcessor $handler): void
    {
        $this->handler = $handler;
    }
}

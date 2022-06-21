<?php

namespace RZP\Models\AMPEmail;
use App;
use Request;
use RZP\Trace\TraceCode;
use Cache;
use Razorpay\Trace\Logger as Trace;
abstract class MailService
{

    const REQUEST_TIMEOUT = 4000;

    const REQUEST_CONNECT_TIMEOUT = 2000;

    private $config;

    /**
     * @var Trace
     */
    protected $trace;

    protected $app;

    protected $client;

    protected $vendor;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = app('trace');
    }

    public abstract function triggerEmail(EmailRequest $request): ?EmailResponse;

    public function getVendorName(): string
    {
        return $this->vendor;
    }

    public static function getInstance()
    {
        return new MailModoService();
    }
}

<?php

namespace RZP\Jobs\Barricade;

use App;
use Razorpay\Trace\Logger as Trace;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;

class PaymentPayload extends Job
{
    const QUEUE_NAME_KEY  = 'barricade_verify';

    protected $params;

    protected $id;

    protected $queueConfigKey = self::QUEUE_NAME_KEY;

    public $timeout = 5400;

    public function __construct($mode, $paymentID, $data)
    {
        $this->params = $data;

        $this->id = $paymentID;

        parent::__construct($mode);
    }

    public function handle()
    {
        parent::handle();
    }
}

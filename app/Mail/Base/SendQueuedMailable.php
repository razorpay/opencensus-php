<?php

namespace RZP\Mail\Base;

use App;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Mail\SendQueuedMailable as BaseSendQueuedMailable;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\HyperTrace;
use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;

class SendQueuedMailable extends BaseSendQueuedMailable
{
    /**
     * For queued mails, we want to generate new request id and use the task id
     * of api request sending the mail. Hence overriding the handle method to do this
     */
    public function handle(MailFactory $factory)
    {
        Tracer::inSpan(['name' => HyperTrace::MAILABLE_HANDLE], function () use($factory) {

            $app = App::getFacadeRoot();

            $app['request']->generateId();

            // For queued mails pick the task id from the mailable payload
            $app['request']->setTaskId($this->mailable->taskId);

            $trace = $app['trace'];

            $repo = $app['repo'];

            // Task Id needs to be set in trace
            $trace->processor('web')->setTaskId($this->mailable->taskId);

            // Sets application and db mode if $mode is set
            if ($this->mailable->mode !== null) {
                $app['basicauth']->setModeAndDbConnection($this->mailable->mode);
            }

            // Since we are using SQS for mail queueing, it is possible to lose the merchant context in basic auth
            // Hence, if the merchant is not set, we can set it from the mailable->mid
            $this->setMerchantInAuth();

            // Sets originProduct, to tag logs and exceptions for X
            if ($this->mailable->originProduct !== null) {
                $app['basicauth']->setProduct($this->mailable->originProduct);
            }

            $repo->resetConnectionAttributes();

            parent::handle($factory);
        });
    }

    protected function setMerchantInAuth()
    {
        $app = App::getFacadeRoot();

        if (empty($this->mailable->mid) === false)
        {
            try
            {
                $app['basicauth']->setMerchantById($this->mailable->mid);
            }
            catch(\Throwable $e)
            {
                $app['trace']->traceException($e,
                    Trace::ERROR,
                    TraceCode::MERCHANT_ID_ABSENT_IN_REQUEST,
                    [
                        'mid' => $this->mailable->mid,
                        'reason' => 'mid missing or setMerchantById failed in basic auth'
                    ]);
            }
        }
    }
}

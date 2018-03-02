<?php

namespace RZP\Mail\Base;

use App;
use Illuminate\Mail\SendQueuedMailable as BaseSendQueuedMailable;
use Illuminate\Contracts\Mail\Mailer as MailerContract;

class SendQueuedMailable extends BaseSendQueuedMailable
{
    /**
     * For queued mails, we want to generate new request id and use the task id
     * of api request sending the mail. Hence overriding the handle method to do this
     */
    public function handle(MailerContract $mailer)
    {
        $app = App::getFacadeRoot();

        $app['request']->generateId();

        // For queued mails pick the task id from the mailable payload
        $app['request']->setTaskId($this->mailable->taskId);

        $trace = $app['trace'];

        // Task Id needs to be set in trace
        $trace->processor('web')->setTaskId($this->mailable->taskId);

        parent::handle($mailer);
    }
}

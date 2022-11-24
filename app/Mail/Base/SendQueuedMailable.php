<?php

namespace RZP\Mail\Base;

use App;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Mail\SendQueuedMailable as BaseSendQueuedMailable;

class SendQueuedMailable extends BaseSendQueuedMailable
{
    /**
     * For queued mails, we want to generate new request id and use the task id
     * of api request sending the mail. Hence overriding the handle method to do this
     */
    public function handle(MailFactory $factory)
    {
        $app = App::getFacadeRoot();

        $app['request']->generateId();

        // For queued mails pick the task id from the mailable payload
        $app['request']->setTaskId($this->mailable->taskId);

        $trace = $app['trace'];

        $repo = $app['repo'];

        // Task Id needs to be set in trace
        $trace->processor('web')->setTaskId($this->mailable->taskId);

        // Sets application and db mode if $mode is set
        if ($this->mailable->mode !== null)
        {
            $app['basicauth']->setModeAndDbConnection($this->mailable->mode);
        }

        // Sets originProduct, to tag logs and exceptions for X
        if ($this->mailable->originProduct !== null)
        {
            $app['basicauth']->setProduct($this->mailable->originProduct);
        }

        $repo->resetConnectionAttributes();

        parent::handle($factory);
    }
}

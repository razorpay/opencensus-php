<?php

namespace RZP\Mail;

use RZP\Mail\Base\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Testing\Fakes\MailFake as BaseMailFake;

class MailFake extends BaseMailFake
{
    protected $config = [];

    /**
     * Mocks the sending of a mail using mailable. Used for mocking the Mail facade
     * to assert if a mail was sent
     *
     * @param  Mailable object $mailable
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return mixed
     */
    public function send($mailable, array $data = [], $callback = null)
    {
        if ((($mailable instanceof Mailable) === false) or ($this->isEmailEnabledForOrg($mailable) === false))
        {
            return;
        }

        $mailable->build();

        if ($mailable instanceof ShouldQueue)
        {
            return $this->queue($mailable, $data, $callback);
        }

        $this->mailables[] = $mailable;
    }

    /**
     * Queue a new e-mail message for sending.
     *
     * @param  string|array  $view
     * @param  string|null  $queue
     * @return mixed
     */
    public function queue($mailable, $queue = null)
    {
        if ((($mailable instanceof Mailable) === false) or ($this->isEmailEnabledForOrg($mailable) === false))
        {
            return;
        }

        $mailable->build();

        $this->queuedMailables[] = $mailable;
    }

    public function setFakeConfig()
    {
        $this->config[\RZP\Mail\Payment\Authorized::class] = false;
    }

    protected function isEmailEnabledForOrg($mailable): bool
    {
        $class = get_class($mailable);

        if ((array_key_exists($class, $this->config)) and ($this->config[$class] === false))
        {
            return false;
        }

        return true;
    }
}

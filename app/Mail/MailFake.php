<?php

namespace RZP\Mail;

use RZP\Mail\Base\Mailable;
use Illuminate\Support\Testing\Fakes\MailFake as BaseMailFake;

class MailFake extends BaseMailFake
{
    /**
     * Mocks the sending of a mail using mailable. Used for mocking the Mail facade
     * to assert if a mail was sent
     *
     * @param  Mailable obkect $mailable
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return void
     */
    public function send($mailable, array $data = [], $callback = null)
    {
        if (($mailable instanceof Mailable) === false)
        {
            return;
        }

        $mailable->build();

        $this->mailables[] = $mailable;
    }
}

<?php

namespace RZP\Mail;

use RZP\Mail\Base\Mailable;
use Illuminate\Support\Testing\Fakes\MailFake as BaseMailFake;

class MailFake extends BaseMailFake
{
    /**
     * Send a new message using a view.
     *
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return void
     */
    public function send($view, array $data = [], $callback = null)
    {
        if (($view instanceof Mailable) === false)
        {
            return;
        }

        $view->build();

        $this->mailables[] = $view;
    }
}

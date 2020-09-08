<?php

namespace RZP\Mail;

use Illuminate\Support\Facades\Mail as BaseFacade;

/**
 * This facade class extends the Illuminate Mail as we want to replace
 * the MailFake class with our own instance as the Illuminate MailFake doesn't
 * call the build method on Mailables
 */
class Facade extends BaseFacade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @return void
     */
    public static function fake()
    {
        static::swap(new MailFake);
    }
}

<?php

namespace RZP\Mail\Base;

use ReflectionClass;

use Illuminate\Queue\SerializesModels as LaravelSerializesModels;

/**
 * We are overriding the SerializesModels trait to set the db connection
 * as per test or live mode property set in the mailable class. This is needed
 * as otherwise entity objects in mailable class are desiarilzed by fetching
 * data using default db connection
 */
trait SerializesModels
{
    use LaravelSerializesModels;

    /**
     * Restore the model after serialization.
     *
     * @return void
     */
    public function __wakeup()
    {
        \Database\DefaultConnection::set($this->mode);

       LaravelSerializesModels::__wakeup();
    }
}

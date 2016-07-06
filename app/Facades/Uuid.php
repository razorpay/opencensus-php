<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Razorpay\Services\Uuid\Generator
 */
class Uuid extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'uuid.generator';
    }
}
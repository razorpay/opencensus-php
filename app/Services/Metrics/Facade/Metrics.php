<?php

namespace RZP\Services\Metrics\Facade;

use Illuminate\Support\Facades\Facade;

class Metrics extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'metrics';
    }
}

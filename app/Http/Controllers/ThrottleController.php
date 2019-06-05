<?php

namespace RZP\Http\Controllers;

use RZP\Services\Throttle;

class ThrottleController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = Throttle\Service::class;
}

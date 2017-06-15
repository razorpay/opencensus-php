<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Trace\TraceCode;
use RZP\Models\VirtualAccount;

class VirtualAccountController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = VirtualAccount\Service::class;
}

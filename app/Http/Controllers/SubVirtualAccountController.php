<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\SubVirtualAccount;

/**
 * Class SubVirtualAccountController
 *
 * @package RZP\Http\Controllers
 */
class SubVirtualAccountController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = SubVirtualAccount\Service::class;
}

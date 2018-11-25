<?php

namespace RZP\Http\Controllers;

use ApiResponse;

use RZP\Models\Payee;

/**
 * Class PayeeController
 *
 * @package RZP\Http\Controllers
 */
class PayeeController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = Payee\Service::class;
}

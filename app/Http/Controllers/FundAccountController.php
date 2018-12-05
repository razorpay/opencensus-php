<?php

namespace RZP\Http\Controllers;

use RZP\Models\FundAccount;

/**
 * Class FundAccountController
 *
 * @package RZP\Http\Controllers
 */
class FundAccountController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = FundAccount\Service::class;
}

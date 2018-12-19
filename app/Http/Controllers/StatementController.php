<?php

namespace RZP\Http\Controllers;

use RZP\Models\Transaction;

/**
 * Class StatementController
 *
 * @package RZP\Http\Controllers
 */
class StatementController extends Controller
{
    protected $service = Transaction\Statement\Service::class;

    use Traits\HasCrudMethods;
}

<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Transaction;

/**
 * Class StatementController
 *
 * @package RZP\Http\Controllers
 */
class StatementController extends Controller
{
    protected $service = Transaction\Statement\Service::class;

    public function fetchMultiple()
    {
        $input = Request::all();

        $response = $this->service()->fetchMultiple($input);

        return ApiResponse::json($response);
    }

    public function fetch($id)
    {
        $response = $this->service()->fetch($id);

        return ApiResponse::json($response);
    }
}

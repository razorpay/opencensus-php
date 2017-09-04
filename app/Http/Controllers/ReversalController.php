<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class ReversalController extends Controller
{
    public function getReversal(string $id)
    {
        $reversal = $this->service()->fetch($id);

        return ApiResponse::json($reversal);
    }

    public function getReversals()
    {
        $input = Request::all();

        $reversals = $this->service()->fetchMultiple($input);

        return ApiResponse::json($reversals);
    }
}

<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

use RZP\Models\Reversal;

class ReversalController extends Controller
{
    protected $service = Reversal\Service::class;

    public function __construct()
    {
        parent::__construct();
    }

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

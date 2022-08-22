<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

class ReversalController extends Controller
{
    public function getReversal(string $id)
    {
        $input = Request::all();

        $reversal = $this->service()->fetch($id, $input);

        return ApiResponse::json($reversal);
    }

    public function getReversals()
    {
        $input = Request::all();

        $reversals = $this->service()->fetchMultiple($input);

        return ApiResponse::json($reversals);
    }

    public function getLinkedAccountReversals()
    {
        $input = Request::all();

        $reversals = $this->service()->fetchLinkedAccountReversals($input);

        return ApiResponse::json($reversals);
    }

    public function getLinkedAccountReversal(string $id)
    {
        $reversal = $this->service()->fetchLinkedAccountReversal($id);

        return ApiResponse::json($reversal);
    }

    public function createReversalEntryForPayoutService()
    {
        $input = Request::all();

        $response = $this->service()->createReversalEntryForPayoutService($input);

        return ApiResponse::json($response);
    }

    public function reverseCreditsViaPayoutService()
    {
        $input = Request::all();

        $data = $this->service()->reverseCreditsViaPayoutService($input);

        return ApiResponse::json($data);
    }

}

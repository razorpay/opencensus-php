<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class SubscriptionRegistrationController extends Controller
{
    public function fetchTokens()
    {
        $input = Request::all();

        $data = $this->service()->fetchTokens($input);

        return ApiResponse::json($data);
    }

    public function listAuthLinks()
    {
        $input = Request::all();

        $data = $this->service()->listAuthLinks($input);

        return ApiResponse::json($data);
    }

    public function createAuthLinks()
    {
        $input = Request::all();

        $data = $this->service()->createAuthLinks($input);

        return ApiResponse::json($data);
    }

    public function fetchAuthLink(string $id)
    {
        $input = Request::all();

        $invoice = $this->service()->fetchAuthLink($id, $input);

        return ApiResponse::json($invoice);
    }

    public function fetchSingleToken(String $id)
    {
        $input = Request::all();

        $invoice = $this->service()->fetchSingleToken($id, $input);

        return ApiResponse::json($invoice);
    }

    public function deleteSingleToken(String $id)
    {
        $invoice = $this->service()->deleteSingleToken($id);

        return ApiResponse::json($invoice);
    }
}
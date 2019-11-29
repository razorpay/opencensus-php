<?php

namespace RZP\Http\Controllers;

class PayoutLinkController extends Controller
{
    use Traits\HasCrudMethods;

    public function update(string $id)
    {
        return ApiResponse::json('Not Supported');
    }

    public function delete(string $id)
    {
        return ApiResponse::json('Not Supported');
    }
}
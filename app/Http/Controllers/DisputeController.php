<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class DisputeController extends Controller
{
    use Traits\HasCrudMethods;

    public function create(string $paymentId)
    {
        $input = Request::all();

        $data = $this->service()->create($input, $paymentId);

        return ApiResponse::json($data);
    }
}

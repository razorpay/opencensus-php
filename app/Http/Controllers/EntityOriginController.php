<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class EntityOriginController extends Controller
{
    use Traits\HasCrudMethods;

    public function fetch()
    {
        $input = Request::all();

        $response = $this->service()->fetch($input);

        return ApiResponse::json($response);
    }
}

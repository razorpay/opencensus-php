<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class CommissionController extends Controller
{
    public function list()
    {
        $input = Request::all();

        $data  = $this->service()->list($input);

        return ApiResponse::json($data);
    }

    public function get(string $id)
    {
        $input = Request::all();

        $entity = $this->service()->fetch($id, $input);

        return ApiResponse::json($entity);
    }
}

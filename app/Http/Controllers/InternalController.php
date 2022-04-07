<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class InternalController extends Controller
{

    public function create()
    {
        $input = Request::all();

        $entity = $this->service()->create($input);

        return ApiResponse::json($entity);
    }

    public function reconcile(string $id)
    {
        $input = Request::all();

        $entity = $this->service()->reconcile($id, $input);

        return ApiResponse::json($entity);
    }

    public function fail(string $id)
    {
        $entity = $this->service()->fail($id);

        return ApiResponse::json($entity);
    }

    public function receive(string $id)
    {
        $input = Request::all();

        $entity = $this->service()->receive($id, $input);

        return ApiResponse::json($entity);
    }
}

<?php


namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class RolesController extends Controller
{
    public function listRolesForMerchant()
    {
        $input = Request::all();

        $response = $this->service()->listRolesForMerchant($input);

        return ApiResponse::json($response);
    }

    public function create()
    {
        $input = Request::all();

        $response = $this->service()->create($input);

        return ApiResponse::json($response);

    }

    public function edit($id)
    {
        $input = Request::all();

        $response = $this->service()->edit($id, $input);

        return ApiResponse::json($response);

    }

    public function getRole(string $id)
    {
        $input = Request::all();

        $data = $this->service()->fetch($id, $input);

        return ApiResponse::json($data);
    }

    public function deleteRole($id)
    {
        $data = $this->service()->deleteRole($id);

        return ApiResponse::json($data);
    }
}

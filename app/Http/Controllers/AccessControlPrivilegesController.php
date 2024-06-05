<?php


namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class AccessControlPrivilegesController extends Controller
{
    public function listPrivileges()
    {
        $response = $this->service()->listDashboardPrivileges();

        return ApiResponse::json($response);
    }

    public function addNewPrivilegeAndItsDependencies()
    {
        $input = Request::all();

        $response = $this->service()->addNewPrivilegeAndItsDependencies($input);

        return ApiResponse::json($response);
    }
}

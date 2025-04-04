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

    public function addPrivilegeOnAuthz()
    {
        $input = Request::all();

        $response = $this->service()->addPrivilegeOnAuthz($input);

        return ApiResponse::json($response);
    }

    public function updatePrivilegeOnAuthz($id)
    {
        $input = Request::all();

        $response = $this->service()->updatePrivilegeOnAuthz($id, $input);

        return ApiResponse::json($response);
    }

    public function addPrivilegeRoleMappingOnAuthz()
    {
        $input = Request::all();

        $response = $this->service()->addPrivilegeRoleMappingOnAuthz($input);

        return ApiResponse::json($response);
    }

    public function updatePrivilegeRoleMappingOnAuthz($id)
    {
        $input = Request::all();

        $response = $this->service()->updatePrivilegeRoleMappingOnAuthz($id, $input);

        return ApiResponse::json($response);
    }
}

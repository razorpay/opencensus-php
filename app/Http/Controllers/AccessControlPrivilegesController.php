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
}

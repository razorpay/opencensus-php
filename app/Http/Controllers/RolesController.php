<?php


namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\RoleAccessPolicyMap\Service as RoleAccessPolicyMapService;

class RolesController extends Controller
{
    public function listRolesForMerchant()
    {
        $input = Request::all();

        $response = $this->service()->listRolesForMerchant($input);

        return ApiResponse::json($response);
    }

    public function listRolesMap()
    {
        $input = Request::all();

        $response = $this->service()->listRolesMap($input);

        return ApiResponse::json($response);
    }

    public function listRolesMapForAdmin()
    {
        $input = Request::all();

        $response = $this->service()->listRolesMapForAdmin($input);

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

    public function getSelfRole()
    {
        $input = Request::all();

        $data = $this->service()->fetchSelfRole();

        return ApiResponse::json($data);
    }

    public function deleteRole($id)
    {
        $data = $this->service()->deleteRole($id);

        return ApiResponse::json($data);
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function fetchAuthZRolesByRoleId(string $id)
    {
        $response = [];

        $response["role_id"] = $id;
        $response["authz_roles"] = $this->service()->getAuthzRolesUsingExperiment($id);

        if (empty($response['authz_roles']) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_AUTHZ_ROLES_NOT_FOUND);
        }
        return ApiResponse::json($response);
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function fixRoleAccessPolicyMap()
    {
        $input = Request::all();

        $response = (new RoleAccessPolicyMapService())->fixRoleAccessPolicyMap($input);

        return ApiResponse::json($response);
    }

    // Used to migrate data from API to Authz in case of CAC migration experiment scale up
    public function migrateToAuthz()
    {
        $input = Request::all();

        $response = $this->service()->migrateToAuthz($input);

        return ApiResponse::json($response);
    }

    // Used to migrate data from Authz to API in case of CAC migration experiment scale down
    public function migrateToApi()
    {
        $input = Request::all();

        $response = $this->service()->migrateToApi($input);

        return ApiResponse::json($response);
    }

    public function updateRoleAccessPolicyMap()
    {
        $input = Request::all();

        $response = (new RoleAccessPolicyMapService())->updateRoleAccessPolicyMap($input);

        return ApiResponse::json($response);
    }
}

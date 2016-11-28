<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Admin\Org;

class OrganisationController extends Controller
{
    public function postOrganisation(Org\Service $orgService)
    {
        $input = Request::all();

        $data = $orgService->create($input);

        return ApiResponse::json($data);
    }

    public function getOrganisation(Org\Service $orgService, $id)
    {
        $data = $orgService->get($id);

        return ApiResponse::json($data);
    }

    public function getOrganisationByHostname(Org\Service $orgService, $hostname)
    {
        $data = $orgService->fetchByHostname($hostname);

        return ApiResponse::json($data);
    }

    public function getOrganisations(Org\Service $orgService)
    {
        $input = Request::all();

        $data = $orgService->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function putOrganisation(Org\Service $orgService, string $id)
    {
        $input = Request::all();

        $data = $orgService->edit($id, $input);

        return ApiResponse::json($data);
    }

    public function deleteOrganisation(Org\Service $orgService, string $id)
    {
        $data = $orgService->delete($id);

        return ApiResponse::json($data);
    }
}
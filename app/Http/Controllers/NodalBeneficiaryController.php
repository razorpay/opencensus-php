<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class NodalBeneficiaryController extends Controller
{
    public function update()
    {
        $input = Request::all();

        $entity = $this->service()->update($input);

        return ApiResponse::json($entity);
    }

    public function createOrUpdateNodalBeneficiary()
    {
        $input = Request::all();

        $response = $this->service()->createOrUpdateNodalBeneficiary($input);

        return ApiResponse::json($response);
    }

    public function fetchNodalBeneficiaryCode()
    {
        $input = Request::all();

        $response = $this->service()->fetchNodalBeneficiaryCode($input);

        return ApiResponse::json($response);
    }

}
